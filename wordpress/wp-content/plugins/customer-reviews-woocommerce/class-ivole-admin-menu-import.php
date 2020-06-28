<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Import_Admin_Menu' ) ):

class Ivole_Import_Admin_Menu {

    /**
     * @var string URL to admin reviews import page
     */
    protected $page_url;

    /**
     * @var string The slug identifying this menu
     */
    protected $menu_slug;

    public function __construct() {
        $this->menu_slug = 'ivole-reviews-import';

        $this->page_url = add_query_arg( array(
            'page' => $this->menu_slug
        ), admin_url( 'admin.php' ) );

        add_action( 'admin_menu', array( $this, 'register_import_menu' ), 11 );
        add_action( 'admin_init', array( $this, 'handle_template_download' ) );
        add_action( 'admin_print_scripts', array( $this, 'print_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
    }

    public function register_import_menu() {
        add_submenu_page(
            'ivole-reviews',
            __( 'Import', IVOLE_TEXT_DOMAIN ),
            __( 'Import', IVOLE_TEXT_DOMAIN ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'display_import_admin_page' )
        );
    }

    public function display_import_admin_page() {
        $download_template_url = add_query_arg( array(
            'action'   => 'ivole-download-import-template',
            '_wpnonce' => wp_create_nonce( 'download_csv_template' )
        ), $this->page_url );

        $max_upload_size = size_format(
            wp_max_upload_size()
        );

        $check_loopback = $this->can_perform_loopback();

        ?>
          <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div class="ivole-import-container">
                <h2><?php echo _e( 'Import Reviews from CSV File', IVOLE_TEXT_DOMAIN ); ?></h2>
                <p><?php echo _e( 'A utility to import reviews from a CSV file. Use it in three steps. Start with downloading the template for entry of reviews. Enter reviews to be imported in the template and save it. Make sure to provide valid product IDs that exist on your WooCommerce site. To import general shop reviews (not related to any particular product), use -1 as a product ID. Finally, upload the template and run import.', IVOLE_TEXT_DOMAIN ); ?></p>
                <div id="ivole-import-upload-steps">
                    <div class="ivole-import-step">
                        <h3 class="ivole-step-title"><?php _e( 'Step 1: Download template', IVOLE_TEXT_DOMAIN ); ?></h3>
                        <a href="<?php echo esc_url( $download_template_url ); ?>" target="_blank">
                            <div class="button button-secondary"><?php _e( 'Download', IVOLE_TEXT_DOMAIN ); ?></div>
                        </a>
                    </div>

                    <div class="ivole-import-step">
                        <h3 class="ivole-step-title"><?php _e( 'Step 2: Enter reviews into the template', IVOLE_TEXT_DOMAIN ); ?></h3>
                    </div>

                    <div class="ivole-import-step">
                        <h3 class="ivole-step-title"><?php _e( 'Step 3: Upload template with your reviews', IVOLE_TEXT_DOMAIN ); ?></h3>
                        <p id="ivole-import-status"></p>
                        <?php
                        if ( 'good' !== $check_loopback->status ):
                        ?>
                          <div id="ivole-import-loopback" style="background-color:#FFA07A;padding:7px;"><?php echo $check_loopback->message; ?></div>
                        <?php
                        else:
                        ?>
                        <div id="ivole-import-filelist">
                            <?php _e( 'No file selected', IVOLE_TEXT_DOMAIN ); ?>
                        </div>
                        <div id="ivole-upload-container">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tbody>
                                    <tr>
                                        <td>
                                            <button type="button" id="ivole-select-button"><?php _e( 'Choose File', IVOLE_TEXT_DOMAIN ); ?></button><br/>
                                            <small>
                                            <?php
                                            printf(
                                                __( 'Maximum size: %s', IVOLE_TEXT_DOMAIN ),
                                                $max_upload_size
                                            );
                                            ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-primary" id="ivole-upload-button"><?php _e( 'Upload', IVOLE_TEXT_DOMAIN ); ?></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <?php
                        endif;
                        ?>
                    </div>
                </div>
                <div id="ivole-import-progress">
                    <h2 id="ivole-import-text"><?php _e( 'Import is in progress', IVOLE_TEXT_DOMAIN ); ?></h2>
                    <progress id="ivole-progress-bar" max="100" value="0"></progress>
                    <div>
                        <button id="ivole-import-cancel" class="button button-secondary"><?php _e( 'Cancel', IVOLE_TEXT_DOMAIN ); ?></button>
                    </div>
                </div>
                <div id="ivole-import-results">
                    <h3 id="ivole-import-result-status"><?php _e( 'Upload Completed', IVOLE_TEXT_DOMAIN ); ?></h3>
                    <p id="ivole-import-result-started"></p>
                    <p id="ivole-import-result-finished"></p>
                    <p id="ivole-import-result-imported"></p>
                    <p id="ivole-import-result-skipped"></p>
                    <p id="ivole-import-result-errors"></p>
                    <div id="ivole-import-result-details" style="display:none;">
                        <h4><?php _e( 'Details:', IVOLE_TEXT_DOMAIN ); ?></h4>
                    </div>
                    <br>
                    <a href="" class="button button-secondary"><?php _e( 'New Upload', IVOLE_TEXT_DOMAIN ); ?></a>
                </div>
            </div>
          </div>
        <?php
    }

    /**
     * Generates a CSV template file and sends it to the browser
     */
    public function handle_template_download() {
        if( isset( $_GET['action'] ) && $_GET['action'] === 'ivole-download-import-template' ) {
            // Ensure a valid nonce has been provided
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'download_csv_template' ) ) {
                wp_die( sprintf( __( 'Failed to download template: invalid nonce. <a href="%s">Return to settings</a>', IVOLE_TEXT_DOMAIN ), $this->page_url ) );
            }

            $template_data = array(
                array(
                    'review_content',
                    'review_score',
                    'date',
                    'product_id',
                    'display_name',
                    'email'
                ),
                array(
                    __( 'This product is great!', IVOLE_TEXT_DOMAIN ),
                    '5',
                    '2018-07-01 15:30:05',
                    12,
                    __( 'Example Customer', IVOLE_TEXT_DOMAIN ),
                    'example.customer@mail.com'
                ),
                array(
                    __( 'This product is not so great.', IVOLE_TEXT_DOMAIN ),
                    '1',
                    '2017-04-15 09:54:32',
                    22,
                    __( 'Sample Customer', IVOLE_TEXT_DOMAIN ),
                    'sample.customer@mail.com'
                ),
                array(
                    __( 'This is a shop review. Note that the product_id is -1. Customer service is good!', IVOLE_TEXT_DOMAIN ),
                    '4',
                    '2017-04-18 10:24:43',
                    -1,
                    __( 'Sample Customer', IVOLE_TEXT_DOMAIN ),
                    'sample.customer@mail.com'
                )
            );

            $stdout = fopen( 'php://output', 'w' );
            $length = 0;

            foreach ( $template_data as $row ) {
                $length += fputcsv( $stdout, $row );
            }

            header( 'Content-Description: File Transfer' );
            header( 'Content-Type: application/octet-stream' );
            header( 'Content-Disposition: attachment; filename="review-import-template.csv"' );
            header( 'Content-Transfer-Encoding: binary' );
            header( 'Connection: Keep-Alive' );
            header( 'Expires: 0' );
            header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header( 'Pragma: public' );
            header( 'Content-Length: ' . $length );
            fclose( $stdout );
            exit;
        }
    }

    public function print_scripts() {
        if ( $this->is_this_page() ) {
            ?>
            <style>
            .ivole-import-container {
                color: #555555;
            }

            .ivole-import-container .ivole-import-step {
                padding-bottom: 15px;
            }

            .ivole-import-container .ivole-import-step .ivole-step-title {
                font-weight: normal;
            }

            #ivole-import-status {
                display: none;
            }

            #ivole-import-status.status-error {
                color: red;
            }

            #ivole-upload-container table td {
                vertical-align: top;
                padding: 5px 20px 0px 0px;
            }

            #ivole-import-progress {
                max-width: 700px;
                margin: 40px auto;
                display: none;
                text-align: center;
            }

            #ivole-import-progress h2 {
                text-align: center;
                font-weight: normal;
            }

            #ivole-import-progress progress {
                width: 100%;
                height: 42px;
                margin: 0 auto 24px;
                display: block;
                -webkit-appearance: none;
                background: #ffffff;
                border: 2px solid #eee;
                border-radius: 4px;
                padding: 0;
                box-shadow: 0 1px 0px 0 rgba(255, 255, 255, 0.2);
            }

            #ivole-import-progress progress::-webkit-progress-bar {
                background: transparent none;
                border: 0;
                border-radius: 4px;
                padding: 0;
                box-shadow: none;
            }

            #ivole-import-progress progress::-webkit-progress-value {
                border-radius: 3px;
                box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.4);
                background: #A46497;
                background: linear-gradient( top, #A46497, #66405F ), #A46497;
                transition: width 1s ease;
            }

            #ivole-import-progress progress::-moz-progress-bar {
                border-radius: 3px;
                box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.4);
                background: #A46497;
                background: linear-gradient( top, #A46497, #66405F ), #A46497;
                transition: width 1s ease;
            }

            #ivole-import-progress progress::-ms-fill {
                border-radius: 3px;
                box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.4);
                background: #A46497;
                background: linear-gradient( to bottom, #A46497, #66405F ), #A46497;
                transition: width 1s ease;
            }

            #ivole-import-results {
                display: none;
            }

            #ivole-import-results p {
                font-size: 15px;
            }

            #ivole-import-cancel {
                font-size: 15px;
                line-height: 32px;
                height: 34px;
                padding: 0 20px 1px;
            }
            </style>
            <?php
        }
    }

    public function include_scripts() {
        if ( $this->is_this_page() ) {
            wp_register_script( 'ivole-admin-import', plugins_url( 'js/admin-import.js', __FILE__ ), [ 'wp-plupload', 'media', 'jquery' ] );

            wp_localize_script( 'ivole-admin-import', 'ivoleImporterStrings', array(
                'uploading'        => __( 'Upload progress: %s%', IVOLE_TEXT_DOMAIN ),
                'importing'        => __( 'Import is in progress (%s/%s completed)', IVOLE_TEXT_DOMAIN ),
                'filelist_empty'   => __( 'No file selected', IVOLE_TEXT_DOMAIN ),
                'cancelling'       => __( 'Cancelling', IVOLE_TEXT_DOMAIN ),
                'cancel'           => __( 'Cancel', IVOLE_TEXT_DOMAIN ),
                'upload_cancelled' => __( 'Upload Cancelled', IVOLE_TEXT_DOMAIN ),
                'upload_failed'    => __( 'Upload Failed', IVOLE_TEXT_DOMAIN ),
                'result_started'   => __( 'Started: %s', IVOLE_TEXT_DOMAIN ),
                'result_finished'  => __( 'Finished: %s', IVOLE_TEXT_DOMAIN ),
                'result_cancelled' => __( 'Cancelled: %s', IVOLE_TEXT_DOMAIN ),
                'result_imported'  => __( '%d review(s) successfully uploaded', IVOLE_TEXT_DOMAIN ),
                'result_skipped'   => __( '%d duplicate review(s) skipped', IVOLE_TEXT_DOMAIN ),
                'result_errors'    => __( '%d error(s)', IVOLE_TEXT_DOMAIN )
            ) );

            wp_enqueue_media();
            wp_enqueue_script( 'ivole-admin-import' );
        }
    }

    public function is_this_page() {
        return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
    }

    private function can_perform_loopback() {
      $cookies = wp_unslash( $_COOKIE );
  		$timeout = 10;
  		$headers = array(
  			'Cache-Control' => 'no-cache',
  		);

  		// Include Basic auth in loopback requests.
  		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
  			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
  		}

  		$url = admin_url();

  		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

  		if ( is_wp_error( $r ) ) {
  			return (object) array(
  				'status'  => 'critical',
  				'message' => sprintf(
  					'%s<br>%s',
  					__( 'The loopback request to your site failed. This means that import of reviews will not be working as expected. If you would like to use the import utility, please contact your hosting provider and request them to enable loopback requests for your site.', IVOLE_TEXT_DOMAIN ),
  					sprintf(
  						// translators: 1: The HTTP response code. 2: The error message returned.
  						__( 'Error: [%1$s] %2$s', IVOLE_TEXT_DOMAIN ),
  						wp_remote_retrieve_response_code( $r ),
  						$r->get_error_message()
  					)
  				),
  			);
  		}

  		if ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
  			return (object) array(
  				'status'  => 'recommended',
  				'message' => sprintf(
  					// translators: %d: The HTTP response code returned.
  					__( 'The loopback request returned an unexpected http status code, %d. This means that import of reviews will not be working as expected. If you would like to use the import utility, please contact your hosting provider and request them to enable loopback requests for your site.', IVOLE_TEXT_DOMAIN ),
  					wp_remote_retrieve_response_code( $r )
  				),
  			);
  		}

  		return (object) array(
  			'status'  => 'good',
  			'message' => __( 'The loopback request to your site completed successfully.' ),
  		);
  	}
}

endif;
