<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Manual' ) ) :

	require_once('class-ivole-email.php');

	class Ivole_Manual {
	  public function __construct() {
			if( 'no' === get_option( 'ivole_enable_manual', 'no' ) ) {
				return;
			}
			add_filter( 'woocommerce_admin_order_actions', array( $this, 'manual_sending' ), 20, 2 );
			add_action( 'admin_head', array( $this, 'add_custom_order_status_actions_button_css' ) );
			add_action( 'wp_ajax_ivole_manual_review_reminder', array( $this, 'manual_review_reminder' ) );
			// new column
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 20 );
			add_action( 'manage_shop_order_posts_custom_column' , array( $this, 'custom_orders_list_column_content' ), 10, 2 );
			//
			add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 20, 2 );

			add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
	  }

		public function manual_sending( $actions, $order ) {
			$order_status = get_option( 'ivole_order_status', 'completed' );
			$order_status = 'wc-' === substr( $order_status, 0, 3 ) ? substr( $order_status, 3 ) : $order_status;
			// Display the button for all orders that have a 'completed' status
	    if ( $order->has_status( array( $order_status ) ) ) {
        // Get Order ID (compatibility all WC versions)
        $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
				if( 'no' !== get_post_meta( $order_id, '_ivole_cr_consent', true ) ) {
					// Set the action button
					if( get_post_meta( $order_id, '_ivole_cr_cron', true ) || ( '' === get_post_meta( $order_id, '_ivole_review_reminder', true ) && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) ) {
						$actions['ivole'] = array(
		            'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=ivole_manual_review_reminder&order_id=' . $order_id ), 'ivole-manual-review-reminder', 'ivole_nonce' ),
		            'name'      => __( 'Sync the order with CR Cron', IVOLE_TEXT_DOMAIN ),
		            'action'    => "view ivole-order ivole-order-cr ivole-o-" . $order_id, // keep "view" class for a clean button CSS
		        );
					} else {
						$actions['ivole'] = array(
		            'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=ivole_manual_review_reminder&order_id=' . $order_id ), 'ivole-manual-review-reminder', 'ivole_nonce' ),
		            'name'      => __( 'Send review reminder now', IVOLE_TEXT_DOMAIN ),
		            'action'    => "view ivole-order ivole-o-" . $order_id, // keep "view" class for a clean button CSS
		        );
					}
				}
	    }
	    return $actions;
		}

		public function add_custom_order_status_actions_button_css() {
			echo '<style>.view.ivole-order.ivole-order-cr::after { font-family: woocommerce !important; content: "\e031" !important; } .widefat .column-ivole-review-reminder {width: 100px;} ' .
				'.view.ivole-order::after { font-family: woocommerce !important; content: "\e02d" !important; } .widefat .column-ivole-review-reminder {width: 100px;}</style>';
		}

		public function manual_review_reminder() {
			$order_id = intval( $_POST['order_id'] );

			//qTranslate integration
			$lang = get_post_meta( $order_id, '_user_language', true );
			$old_lang = '';
			if( $lang ) {
				global $q_config;
				$old_lang = $q_config['language'];
				$q_config['language'] = $lang;

				//WPML integration
				if ( has_filter( 'wpml_current_language' ) ) {
					$old_lang = apply_filters( 'wpml_current_language', NULL );
					do_action( 'wpml_switch_language', $lang );
				}
			}

			$schedule = false;
			if( get_post_meta( $order_id, '_ivole_cr_cron', true ) || ( '' === get_post_meta( $order_id, '_ivole_review_reminder', true ) && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) ) {
				//reminder should be sent via CR Cron
				$schedule = true;
			}

			$e = new Ivole_Email( $order_id );
			$result = $e->trigger2( $order_id, null, $schedule );

			//qTranslate integration
			if( $lang ) {
				$q_config['language'] = $old_lang;

				//WPML integration
				if ( has_filter( 'wpml_current_language' ) ) {
					do_action( 'wpml_switch_language', $old_lang );
				}
			}

			$msg = '';
			if( !$schedule ) {
				//necessary for reminders sent via WP Cron
				$status = get_post_meta( $order_id, '_ivole_review_reminder', true );
				if( '' === $status ) {
					$msg = __( 'No reminders sent', IVOLE_TEXT_DOMAIN );
				} else {
					$status = intval( $status );
					if( $status > 0) {
						$msg = $status . __( ' reminder(s) sent', IVOLE_TEXT_DOMAIN );
					} else {
						$msg = __( 'No reminders sent yet', IVOLE_TEXT_DOMAIN );
					}
				}
			}

			if( is_array( $result ) && count( $result)  > 1 && 2 === $result[0] ) {
				wp_send_json( array( 'code' => 2, 'message' => $result[1], 'order_id' => $order_id ) );
			} elseif( is_array( $result ) && count( $result)  > 1 && 7 === $result[0] ) {
				wp_send_json( array( 'code' => 7, 'message' => $result[1], 'order_id' => $order_id ) );
			} elseif( is_array( $result ) && count( $result)  > 1 && 9 === $result[0] ) {
				wp_send_json( array( 'code' => 9, 'message' => $result[1], 'order_id' => $order_id ) );
			} elseif( 0 === $result ) {
				//unschedule automatic review reminder if manual sending was successful (for reminders sent via WP Cron)
				if( !$schedule ) {
					$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $order_id ) );
					//error_log('timestamp:' . $timestamp );
					if( $timestamp ) {
						wp_unschedule_event( $timestamp, 'ivole_send_reminder', array( $order_id ) );
					}
				} else {
					$msg = __( 'Successfully synced with CR Cron', IVOLE_TEXT_DOMAIN );
				}
				wp_send_json( array( 'code' => 0, 'message' => $msg, 'order_id' => $order_id ) );
			} elseif( 1 === $result ) {
				wp_send_json( array( 'code' => 1, 'message' => $msg, 'order_id' => $order_id ) );
			} elseif( 3 === $result ) {
				wp_send_json( array( 'code' => 3, 'message' => __( 'Error: maximum number of reminders per order is limited to one.', IVOLE_TEXT_DOMAIN ), 'order_id' => $order_id ) );
			} elseif( 4 === $result ) {
				wp_send_json( array( 'code' => 4, 'message' => __( 'Error: the order does not contain any products for which review reminders are enabled in the settings.', IVOLE_TEXT_DOMAIN ), 'order_id' => $order_id ) );
			} elseif( 5 === $result ) {
				wp_send_json( array( 'code' => 5, 'message' => __( 'Error: the order was placed by a customer who doesn\'t have a role for which review reminders are enabled.', IVOLE_TEXT_DOMAIN ), 'order_id' => $order_id ) );
			} elseif( 6 === $result ) {
				wp_send_json( array( 'code' => 6, 'message' => __( 'Error: could not save the secret key to DB. Please try again.', IVOLE_TEXT_DOMAIN ), 'order_id' => $order_id ) );
			} elseif( 10 === $result ) {
				wp_send_json( array( 'code' => 10, 'message' => __( 'Error: the customer\'s email is invalid.', IVOLE_TEXT_DOMAIN ), 'order_id' => $order_id ) );
			}
			wp_send_json( array( 'code' => 98, 'message' => $msg, 'order_id' => $order_id ) );
		}

		public function custom_shop_order_column($columns) {
	    $columns['ivole-review-reminder'] = __( 'Review Reminder', IVOLE_TEXT_DOMAIN );
	    return $columns;
		}

		public function custom_orders_list_column_content( $column, $post_id ) {
	    if( 'ivole-review-reminder' === $column ) {
				//count reviews that an order has received
				$args = array(
					'meta_key' => 'ivole_order',
					'meta_value' => $post_id
				);
				$reviews = get_comments( $args );
				$reviews_count = count( $reviews );
				$reviews_text = '';
				if( $reviews_count > 0 ) {
					$reviews_text = ';<br> ' . sprintf( __( '%d review(s) received', IVOLE_TEXT_DOMAIN ), $reviews_count );
				}
				//
				$no_cr_consent = 'no' === get_post_meta( $post_id, '_ivole_cr_consent', true ) ? true : false;
				$cr_cron = get_post_meta( $post_id, '_ivole_cr_cron', true );
				//check if a review was sent via CR Cron
				if( $cr_cron ) {
					echo __( 'A review reminder was scheduled via CR Cron', IVOLE_TEXT_DOMAIN ) . $reviews_text;
				} else {
					//a review has not been sent via CR Cron
					$reminder = get_post_meta( $post_id, '_ivole_review_reminder', true );
					if( '' === $reminder && 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) {
						//no review reminder has been scheduled via WP Cron and CR Cron is the current setting
						if( $no_cr_consent ) {
							echo __( 'No customer consent received', IVOLE_TEXT_DOMAIN );
						} else {
							echo __( 'No reminders sent yet', IVOLE_TEXT_DOMAIN );
						}
					} else {
						$tmp_flag = true;
						if( !$reminder ) {
							//no review reminder has been sent via WP Cron and WP Cron is the current setting
							if( $no_cr_consent ) {
								echo __( 'No customer consent received', IVOLE_TEXT_DOMAIN );
							} else {
								echo __( '', IVOLE_TEXT_DOMAIN );
							}
							$tmp_flag = false;
						} else {
							//a review reminder has been sent via WP Cron
							$reminder = intval( $reminder );
							if( $reminder > 0) {
								echo sprintf( __( '%d reminder(s) sent', IVOLE_TEXT_DOMAIN ), $reminder ) . $reviews_text;
							} else {
								if( $no_cr_consent ) {
									echo __( 'No customer consent received', IVOLE_TEXT_DOMAIN );
								} else {
									echo __( 'No reminders sent', IVOLE_TEXT_DOMAIN ) . $reviews_text;
								}
							}
						}
						$timestamp = wp_next_scheduled( 'ivole_send_reminder', array( $post_id ) );
						if( $timestamp ) {
							if( $tmp_flag ) {
								echo ';<br> ';
							}
							if( $timestamp >= 0 ) {
								echo __( 'A reminder is scheduled for ', IVOLE_TEXT_DOMAIN ) . date_i18n( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $timestamp ) . '.';
							} else {
								echo __( 'WP Cron error', IVOLE_TEXT_DOMAIN );
							}
						}
					}
				}
	    }
		}

		public function default_hidden_columns( $hidden, $screen ) {
			if ( isset( $screen->id ) && 'edit-shop_order' === $screen->id ) {
				//error_log( "Actions" );
				array_splice( $hidden, array_search( 'wc_actions', $hidden ), 1 );
			}
			return $hidden;
		}

		public function include_scripts( $hook ) {
			if ( 'edit.php' == $hook ) {
				wp_enqueue_script( 'ivole-manual-review-reminder', plugins_url( 'js/admin-manual.js', __FILE__ ), array(), false, false );
			}
		}
	}

endif;

?>
