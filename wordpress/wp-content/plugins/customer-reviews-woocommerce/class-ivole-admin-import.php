<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('Ivole_Admin_Import')):

    class Ivole_Admin_Import
    {
        protected static $background_importer;

        public static $columns = array(
            'review_content',
            'review_score',
            'date',
            'product_id',
            'display_name',
            'email'
        );

        public function __construct()
        {
            add_action('init', array( 'Ivole_Admin_Import', 'init_background_importer' ));
            add_action('wp_ajax_ivole_import_upload_csv', array( $this, 'handle_upload' ));
            add_action('wp_ajax_ivole_check_import_progress', array( $this, 'check_import_progress' ));
            add_action('wp_ajax_ivole_cancel_import', array( $this, 'cancel_import' ));
        }

        /**
         * Receives a CSV file via ajax, validates it and counts reviews
         */
        public function handle_upload()
        {
            if (! current_user_can('upload_files')) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message' => __('Permission denied', IVOLE_TEXT_DOMAIN)
                        ),
                    )
                );
                wp_die();
            }

            if (! isset($_FILES['file']) || ! is_array($_FILES['file'])) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message' => __('No file was uploaded', IVOLE_TEXT_DOMAIN)
                        ),
                    )
                );
                wp_die();
            }

            if ( extension_loaded( 'fileinfo' ) ) {
                $finfo = finfo_open( FILEINFO_MIME_TYPE );
            		$real_mime = finfo_file( $finfo, $_FILES['file']['tmp_name'] );
            		finfo_close( $finfo );
                if ( !in_array( $real_mime, array( 'text/plain', 'text/csv', 'text/x-csv', 'application/vnd.ms-excel', 'application/csv', 'application/x-csv' ) ) ) {
                  echo wp_json_encode(
                      array(
                          'success' => false,
                          'data'    => array(
                              'message'  => __('The uploaded file is not a valid CSV file', IVOLE_TEXT_DOMAIN),
                              'filename' => $_FILES['file']['name'],
                          )
                      )
                  );
                  wp_die();
                }
            }

            $file_data = wp_handle_upload($_FILES['file'], array(
                'action' => 'ivole_import_upload_csv', 'test_type' => false
            ));

            if (isset($file_data['error'])) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => $file_data['error'],
                            'filename' => $_FILES['file']['name'],
                        )
                    )
                );
                wp_die();
            }

            $file_stats = $this->validate_csv_file($file_data['file']);

            if (is_wp_error($file_stats)) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => $file_stats->get_error_message(),
                            'filename' => $_FILES['file']['name'],
                        )
                    )
                );
                wp_die();
            }

            $progress_id = 'import_progress_' . uniqid();
            $progress = array(
                'status'  => 'importing',
                'started' => current_time('timestamp'),
                'reviews' => array(
                    'total'    => $file_stats['num_reviews'],
                    'imported' => 0,
                    'skipped'  => 0,
                    'errors'   => 0
                )
            );

            set_transient($progress_id, $progress, WEEK_IN_SECONDS);

            $batch = array(
                'file'        => $file_data['file'],
                'offset'      => $file_stats['offset'],
                'progress_id' => $progress_id,
                'line'        => 2
            );

            self::$background_importer->data($batch);

            $cookies = array();
            foreach ($_COOKIE as $name => $value) {
                if ('PHPSESSID' === $name) {
                    continue;
                }
                $cookies[] = new WP_Http_Cookie(array(
                    'name'  => $name,
                    'value' => $value,
                ));
            }

            self::$background_importer->post_args = array(
                'timeout'   => 10,
                'blocking'  => true,
                'body'      => $batch,
                'cookies'   => $cookies,
                'sslverify' => apply_filters('https_local_ssl_verify', false),
            );

            // We need to check to ensure that basic auth isn't blocking the background process
            $response = self::$background_importer->save()->dispatch();
            $status = wp_remote_retrieve_response_code($response);

            if ($status === 401) {
                echo wp_json_encode(
                    array(
                        'success' => false,
                        'data'    => array(
                            'message'  => __("Failed to start background importer, please disable Basic Auth and retry", IVOLE_TEXT_DOMAIN),
                            'filename' => $_FILES['file']['name'],
                        )
                    )
                );
                wp_die();
            }

            echo wp_json_encode(
                array(
                    'success' => true,
                    'data'    => array(
                        'file_path'   => $file_data['file'],
                        'num_rows'    => $file_stats['num_reviews'],
                        'progress_id' => $progress_id
                    )
                )
            );
            wp_die();
        }

        /**
         * Ensures the first line of the csv is formatted correctly and returns
         * the number of reviews in the file and the offset for the first review
         */
        protected function validate_csv_file($file_path)
        {
            if (! is_readable($file_path)) {
                return new WP_Error('failed_read_file', __('Cannot read CSV file', IVOLE_TEXT_DOMAIN));
            }

            ini_set( 'auto_detect_line_endings' , true );
            $file = fopen($file_path, 'r');
            $columns = fgetcsv($file);

            // check for Byte Order Mark present in UTF8 files
            $bom = pack("CCC", 0xef, 0xbb, 0xbf);
            $columns_correct = true;
            if ( !is_array($columns) || count( self::$columns ) !== count( $columns ) ) {
                $columns_correct = false;
            } else {
                for ($i = 0; $i< count( self::$columns ); $i++) {
                    //if there is BOM, remove it before comparison of column names
                    if (0 == strncmp($columns[$i], $bom, 3)) {
                        $columns[$i] = substr($columns[$i], 3);
                    }
                    if( self::$columns[$i] !== $columns[$i] ) {
                      $columns_correct = false;
                      break;
                    }
                }
            }

            if ( !$columns_correct ) {
                fclose($file);
                ini_set( 'auto_detect_line_endings', false );
                return new WP_Error('malformed_columns', __('The CSV file contains invalid or missing column headings, please refer to the template in step 1', IVOLE_TEXT_DOMAIN));
            }

            $offset = ftell($file);

            $num_reviews = 0;
            while (($row = fgetcsv($file)) !== false) {
                $num_reviews++;
            }

            fclose($file);
            ini_set( 'auto_detect_line_endings', false );

            if ($num_reviews < 1) {
                return new WP_Error('no_reviews', __('The CSV file contains no reviews', IVOLE_TEXT_DOMAIN));
            }

            return array(
                'offset'      => $offset,
                'num_reviews' => $num_reviews
            );
        }

        public function check_import_progress()
        {
            $progress_id = $_POST['progress_id'];
            $progress = get_transient($progress_id);

            wp_send_json($progress, 200);
            wp_die();
        }

        public function cancel_import()
        {
            $progress_id = $_POST['progress_id'];

            set_transient('cancel' . $progress_id, true, WEEK_IN_SECONDS);

            self::$background_importer->maybe_handle();

            $progress = get_transient($progress_id);
            wp_send_json($progress, 200);
            wp_die();
        }

        /**
         * Initialize the background importer process
         */
        public static function init_background_importer()
        {
            if (! class_exists('Ivole_Background_Importer')) {
                //include_once dirname(__FILE__) . '/class-ivole-background-importer.php';
                require_once('class-ivole-background-importer.php');
            }

            self::$background_importer = new Ivole_Background_Importer();
        }

        public static function get_columns()
        {
            return self::$columns;
        }
    }

endif;
