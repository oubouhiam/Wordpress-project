<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Background_Process', false ) ) {
    if( file_exists( WC_ABSPATH . 'includes/abstracts/class-wc-background-process.php' ) ) {
      include_once WC_ABSPATH . 'includes/abstracts/class-wc-background-process.php';
    } else {
      include_once dirname( __FILE__ ) . '/class-wc-background-process.php';
    }
}

class Ivole_Background_Importer extends WC_Background_Process {

    private $line = 0;

    public function __construct() {
        $this->prefix = 'wp_' . get_current_blog_id();
        $this->action = 'ivole_importer';

        parent::__construct();
    }

    /**
     * Validate and import reviews
     */
    protected function task( $reviews ) {
        global $wpdb;

        $results = array(
            'imported'   => 0,
            'skipped'    => 0,
            'errors'     => 0,
            'error_list'     => array(),
            'duplicate_list' => array()
        );

        $columns = Ivole_Admin_Import::get_columns();

        $review_content_index = array_search( 'review_content', $columns );
        $review_score_index   = array_search( 'review_score', $columns );
        $date_index           = array_search( 'date', $columns );
        $product_id_index     = array_search( 'product_id', $columns );
        $display_name_index   = array_search( 'display_name', $columns );
        $email_index          = array_search( 'email', $columns );

        $hashes = array();
        $product_ids = array();
        $num_reviews = count( $reviews );
        $shop_page_id = wc_get_page_id( 'shop' );

        // Ensure mandatory fields are provided
        foreach ( $reviews as $index => $review ) {
            $line_number = $this->line - ( $num_reviews - $index );

            $filtered = array_filter( $review );
            if ( empty( $filtered ) ) {
                unset( $reviews[$index] );
                $results['errors']++;
                $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: no data for this review.', IVOLE_TEXT_DOMAIN ), $line_number );
                continue;
            }

            $review_score = intval( $review[$review_score_index] );
            if ( $review_score < 1 || $review_score > 5 ) {
                unset( $reviews[$index] );
                $results['errors']++;
                $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: review score = %2$d. Review scores must be between 1 and 5.', IVOLE_TEXT_DOMAIN ), $line_number, $review_score );
                continue;
            }

            $product_id = intval( $review[$product_id_index] );
            if ( $product_id < 1 && -1 !== $product_id ) {
                unset( $reviews[$index] );
                $results['errors']++;
                $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: product_id must be a positive number or \'-1\' for shop reviews.', IVOLE_TEXT_DOMAIN ), $line_number );
                continue;
            }
            if( -1 !== $product_id ) {
              $ppp = wc_get_product( $product_id );
              if( !$ppp || ($ppp && wp_get_post_parent_id( $product_id ) > 0 ) ) {
                  unset( $reviews[$index] );
                  $results['errors']++;
                  $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: product with ID = %2$d doesn\'t exist in this WooCommerce store.', IVOLE_TEXT_DOMAIN ), $line_number, $product_id );
                  continue;
              }
            } else {
              $product_id = $shop_page_id;
            }

            $display_name = $review[$display_name_index];
            if ( empty( $display_name ) ) {
                unset( $reviews[$index] );
                $results['errors']++;
                $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: display name cannot be empty.', IVOLE_TEXT_DOMAIN ), $line_number );
                continue;
            }

            $email = $review[$email_index];
            $email = trim( $email );
            if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                unset( $reviews[$index] );
                $results['errors']++;
                $results['error_list'][] = sprintf( __( 'Line %1$d >> Error: %2$s is not a valid email address.', IVOLE_TEXT_DOMAIN ), $line_number, $email );
                continue;
            }

            // Quick way to check for duplicates within $reviews array
            $hash = md5( $review[$review_content_index] . '|' . $review_score . '|' . $product_id . '|' . $email );

            if ( in_array( $hash, $hashes ) ) {
                unset( $reviews[$index] );
                $results['skipped']++;
                $results['duplicate_list'][] = sprintf( __( 'Line %1$d >> Error: Duplicate review within CSV file.', IVOLE_TEXT_DOMAIN ), $line_number );
                continue;
            }

            $hashes[] = $hash;

            if ( ! in_array( $product_id, $product_ids ) ) {
                $product_ids[] = $product_id;
            }
        }

        if ( count( $product_ids ) > 0 ) {
          $existing_reviews = $wpdb->get_results(
              "SELECT com.*, meta.meta_value AS rating
              FROM {$wpdb->comments} AS com LEFT JOIN {$wpdb->commentmeta} AS meta
              ON com.comment_ID = meta.comment_id AND meta.meta_key = 'rating'
              WHERE com.comment_post_ID IN(" . implode( ',', $product_ids ) . ")",
              ARRAY_A
          );

          if ( ! is_array( $existing_reviews ) ) {
              $existing_reviews = array();
          }
        } else {
          $existing_reviews = array();
        }

        $existing_reviews = array_reduce( $existing_reviews, function( $existing, $review ) {
            if ( ! isset( $existing[$review['comment_post_ID']] ) ) {
                $existing[$review['comment_post_ID']] = array();
            }

            $existing[$review['comment_post_ID']][] = $review;

            return $existing;
        }, [] );

        $timezone_string = get_option( 'timezone_string' );
        $timezone_string = empty( $timezone_string ) ? 'gmt': $timezone_string;
        $site_timezone = new DateTimeZone( $timezone_string );
        $gmt_timezone  = new DateTimeZone( 'gmt' );
        // Check for duplicates and add review
        foreach ( $reviews as $index => $review ) {
            $line_number = $this->line - ( $num_reviews - $index );

            $product_id = intval( $review[$product_id_index] );
            if( -1 === $product_id ) {
              $product_id = $shop_page_id;
            }

            if ( ! empty( $review[$date_index] ) ) {
                $date_string = str_ireplace( 'UTC', 'GMT', $review[$date_index] );

                try {
                    if ( strpos( $date_string, 'GMT' ) !== false ) {
                        $date = new DateTime( $date_string );
                    } else {
                        $date = new DateTime( $date_string, $site_timezone );
                    }
                } catch ( Exception $exception ) {
                    $date = new DateTime( 'now', $site_timezone );
                }

            } else {
                $date = new DateTime( 'now', $site_timezone );
            }

            $review_date = $date->format( 'Y-m-d H:i:s' );
            $date->setTimezone( $gmt_timezone );
            $review_date_gmt = $date->format( 'Y-m-d H:i:s' );

            if ( isset( $existing_reviews[$product_id] ) ) {

                foreach ( $existing_reviews[$product_id] as $review_of_product ) {
                    if ( $review[$review_content_index] == $review_of_product['comment_content']
                        && $review[$email_index] == $review_of_product['comment_author_email']
                        && $review[$review_score_index] == intval( $review_of_product['rating'] ) ) {
                        $results['skipped']++;
                        $results['duplicate_list'][] = sprintf( __( 'Line %1$d >> Error: Duplicate review.', IVOLE_TEXT_DOMAIN ), $line_number );
                        continue 2;
                    }

                }

            }

            //make sure that the display name is in UTF-8 encoding
            if( !mb_check_encoding( $review[$display_name_index], 'UTF-8' ) ) {
              //if it is not, try to convert the encoding to UTF-8
              if( mb_check_encoding( $review[$display_name_index], 'Windows-1252' ) ) {
                $review[$display_name_index] = mb_convert_encoding( $review[$display_name_index], 'UTF-8', 'Windows-1252' );
              } elseif ( mb_check_encoding( $review[$display_name_index], 'Windows-1251' ) ) {
                $review[$display_name_index] = mb_convert_encoding( $review[$display_name_index], 'UTF-8', 'Windows-1251' );
              }
            }

            //make sure that the review content is in UTF-8 encoding
            if( !mb_check_encoding( $review[$review_content_index], 'UTF-8' ) ) {
              //if it is not, try to convert the encoding to UTF-8
              if( mb_check_encoding( $review[$review_content_index], 'Windows-1252' ) ) {
                $review[$review_content_index] = mb_convert_encoding( $review[$review_content_index], 'UTF-8', 'Windows-1252' );
              } elseif ( mb_check_encoding( $review[$review_content_index], 'Windows-1251' ) ) {
                $review[$review_content_index] = mb_convert_encoding( $review[$review_content_index], 'UTF-8', 'Windows-1251' );
              }
            }

            $tmp_comment_content = '';
            // sanitize_textarea_field function is defined only in WordPress 4.7+
            if( function_exists( 'sanitize_textarea_field' ) ) {
              $tmp_comment_content = sanitize_textarea_field( $review[$review_content_index] );
            } else {
              $tmp_comment_content = sanitize_text_field( $review[$review_content_index] );
            }

            $review_id = wp_insert_comment(
                array(
                    'comment_author'       => sanitize_text_field( $review[$display_name_index] ),
                    'comment_author_email' => $review[$email_index],
                    'comment_content'      => $tmp_comment_content,
                    'comment_post_ID'      => $product_id,
                    'comment_date'         => $review_date,
                    'comment_date_gmt'     => $review_date_gmt,
                    'comment_type'         => 'review',
                    'comment_meta'         => array(
                        'rating' => $review[$review_score_index]
                    )
                )
            );

            if ( $review_id ) {
                wp_update_comment_count_now( $product_id );
                $results['imported']++;
            } else {
                $results['errors']++;
            }
        }

        return $results;
    }

    protected function get_post_args() {
        if ( property_exists( $this, 'post_args' ) ) {
            return $this->post_args;
        }

        // Pass cookies through with the request so nonces function.
        $cookies = array();

        foreach ( $_COOKIE as $name => $value ) {
            if ( 'PHPSESSID' === $name ) {
                continue;
            }
            $cookies[] = new WP_Http_Cookie( array(
                'name'  => $name,
                'value' => $value,
            ) );
        }

        return array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'body'      => $this->data,
            'cookies'   => $cookies,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
        );
    }

    protected function handle() {
        global $wpdb;

        $this->lock_process();
        ini_set( 'auto_detect_line_endings', true );

        do {
            // One batch represents one CSV import job
            $batch = $this->get_batch();

            if ( empty( $batch->data ) ) {
                break;
            }

            $progress = get_transient( $batch->data['progress_id'] );

            if ( ! $progress ) {
                $progress = array();
            }

            $cancelled = get_transient( 'cancel' . $batch->data['progress_id'] );
            if ( $cancelled ) {
                $this->delete( $batch->key );
                $progress['status'] = 'cancelled';
                $progress['finished'] = current_time( 'timestamp' );
                set_transient( $batch->data['progress_id'], $progress );
                @unlink( $batch->data['file'] );
                continue;
            }

            // Set line from batch data
            $this->line = $batch->data['line'];

            $file_offset = $batch->data['offset'];
            $file = fopen( $batch->data['file'], 'r' );

            if ( $file === false || empty( $progress ) ) {
                // Import failed
                $this->delete( $batch->key );
                $progress['status'] = 'failed';
                $progress['finished'] = current_time( 'timestamp' );
                set_transient( $batch->data['progress_id'], $progress );
                continue;
            }

            $cancelled = get_transient( 'cancel' . $batch->data['progress_id'] );
            if ( $cancelled ) {
                $this->delete( $batch->key );
                $progress['status'] = 'cancelled';
                $progress['finished'] = current_time( 'timestamp' );
                set_transient( $batch->data['progress_id'], $progress );
                @unlink( $batch->data['file'] );
                continue;
            }

            $cancel_query = $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                '_transient_cancel' . $batch->data['progress_id']
            );

            fseek( $file, $file_offset );

            /**
             * Review buffer will fill up with 5 reviews and then attempt to
             * import them. This is to limit the amount of database interactions
             * while still providing regular progress information.
             */
            $review_buffer = array();
            $review_buffer_size = 3;
            /**
             * Normally using feof in the iteration condition is a bug,
             * but memory/time constraints will prevent hanging in this situation.
             */
            while ( ! feof( $file ) ) {
                $review_data = fgetcsv( $file );

                if ( $review_data !== false ) {
                    $review_buffer[] = $review_data;
                }

                $current_buffer_size = count( $review_buffer );
                if ( $current_buffer_size >= $review_buffer_size || $review_data === false || feof( $file ) ) {
                    $this->line += $current_buffer_size;
                    $import = $this->task( $review_buffer );

                    $wpdb->flush();
                    $cancelled = $wpdb->get_var( $cancel_query );
                    if ( $cancelled ) {
                        $this->delete( $batch->key );
                        $progress['status'] = 'cancelled';
                        $progress['finished'] = current_time( 'timestamp' );
                        set_transient( $batch->data['progress_id'], $progress );
                        @unlink( $batch->data['file'] );
                        continue 2;
                    }

                    /*
                     * It is important that file offset is only updated after the import has been completed
                     * as it is possible that the process will abort before attempting to import reviews in buffer.
                     */
                    $file_offset = ftell( $file );

                    $progress['reviews']['imported'] += $import['imported'];
                    $progress['reviews']['errors']   += $import['errors'];
                    $progress['reviews']['skipped']  += $import['skipped'];

                    if ( isset( $progress['reviews']['error_list'] ) && is_array( $progress['reviews']['error_list'] ) ) {
                        $progress['reviews']['error_list'] = array_merge( $progress['reviews']['error_list'], $import['error_list'] );
                    } else {
                        $progress['reviews']['error_list'] = $import['error_list'];
                    }

                    if( isset( $progress['reviews']['duplicate_list'] ) && is_array( $progress['reviews']['duplicate_list'] ) ) {
                        $progress['reviews']['duplicate_list'] = array_merge( $progress['reviews']['duplicate_list'], $import['duplicate_list'] );
                    } else {
                        $progress['reviews']['duplicate_list'] = $import['duplicate_list'];
                    }

                    set_transient( $batch->data['progress_id'], $progress );

                    $review_buffer = array();
                }

                if ( $this->time_exceeded() || $this->memory_exceeded() || $review_data === false ) {
                    break;
                }
            }

            if ( feof( $file ) ) {
                // Import is complete
                $this->delete( $batch->key );
                $progress['status'] = 'complete';
                $progress['finished'] = current_time( 'timestamp' );
                set_transient( $batch->data['progress_id'], $progress );
                @unlink( $batch->data['file'] );
            } else {
                $batch->data['offset'] = $file_offset;
                $batch->data['line'] = $this->line;

                $this->update( $batch->key, $batch->data );
            }

            fclose( $file );
        } while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

        ini_set( 'auto_detect_line_endings', false );
        $this->unlock_process();

        if ( ! $this->is_queue_empty() ) {
            $this->dispatch();
        } else {
            $this->complete();
        }
    }

}
