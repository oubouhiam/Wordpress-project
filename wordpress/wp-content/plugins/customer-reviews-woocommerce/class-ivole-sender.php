<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Sender' ) ) :

	require_once('class-ivole-email.php');

	class Ivole_Sender {
	  public function __construct() {
			$order_status = get_option( 'ivole_order_status', 'completed' );
			$order_status = 'wc-' === substr( $order_status, 0, 3 ) ? substr( $order_status, 3 ) : $order_status;
			// Triggers for completed orders
			add_action( 'woocommerce_order_status_' . $order_status, array( $this, 'sender_trigger' ), 20, 1 );
			add_action( 'ivole_send_reminder', array( $this, 'sender_action' ), 10, 1 );
			//add_action( 'woocommerce_order_status_pending', array( $this, 'new_order_trigger' ), 20, 1 );
			//add_action( 'woocommerce_order_status_on-hold', array( $this, 'new_order_trigger' ), 20, 1 );
			//add_action( 'woocommerce_order_status_processing', array( $this, 'new_order_trigger' ), 20, 1 );
	  }

		public function sender_trigger( $order_id ) {
			// check if reminders are enabled
			$reminders_enabled = get_option( 'ivole_enable', 'no' );
			if( $reminders_enabled === 'no' ) {
				//error_log('not enabled');
				return;
			}
			if( $order_id ) {
				// compatibility with WooCommerce Subscriptions plugin
				// do not send review reminders for renewal orders of the same subscription
				if( function_exists( 'wcs_order_contains_renewal' ) ) {
					if( wcs_order_contains_renewal( $order_id ) ) {
						// this is a renewal order, don't send a review reminder
						return;
					}
				}
				$order = new WC_Order( $order_id );
				// check if the order contains at least one product for which reminders are enabled (if there is filtering by categories)
				$enabled_for = get_option( 'ivole_enable_for', 'all' );
				if( $enabled_for === 'categories' ) {
					$enabled_categories = get_option( 'ivole_enabled_categories', array() );
					$items = $order->get_items();
					$skip = true;
					foreach ( $items as $item_id => $item ) {
						if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
							$categories = get_the_terms( $item['product_id'], 'product_cat' );
							foreach ( $categories as $category_id => $category ) {
								if( in_array( $category->term_id, $enabled_categories ) ) {
									$skip = false;
									break;
								}
							}
						}
					}
					if( $skip ) {
						// there is no products from enabled categories in the order, skip sending
						//error_log('categories');
						return;
					}
				}
				if( 'roles' === get_option( 'ivole_enable_for_role', 'all' ) ) {
					//skip sending if there are exclusions based on user roles
					if( method_exists( $order, 'get_user' ) ) {
						$user = $order->get_user();
						if( isset( $user ) && !empty( $user ) ) {
							$roles = $user->roles;
							$enabled_roles = get_option( 'ivole_enabled_roles', array() );
							$intersection = array_intersect( $enabled_roles, $roles );
							if( count( $intersection ) < 1 ) {
								//the customer does not have roles for which review reminders are enabled
								return;
							}
						}
					}
				}
				if( 'no' === get_post_meta( $order_id, '_ivole_cr_consent', true ) ) {
					//skip sending because no customer consent was received
					return;
				}

				$delay = get_option( 'ivole_delay', 5 );
				//if (1) no reminders was previously scheduled via WP Cron and CR Cron is currently enabled or (2) a reminder was previously scheduled via CR Cron
				if( ( 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) && '' === get_post_meta( $order_id, '_ivole_review_reminder', true ) ) || get_post_meta( $order_id, '_ivole_cr_cron', true ) ) {
					$sender_result = $this->sender_action( $order_id, true );
					if( 0 === $sender_result ) {
						$order->add_order_note( __( 'CR: a review reminder was scheduled via CR Cron. Please log in to your account on <a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">CR website</a> to view and manage the reminders.', IVOLE_TEXT_DOMAIN ) );
					} else {
						if( is_array( $sender_result ) && count( $sender_result ) > 0 ) {
							$order->add_order_note( sprintf( __( 'CR: a review reminder could not be scheduled via CR Cron. Error %d.', IVOLE_TEXT_DOMAIN ), $sender_result[0] ) );
						} else {
							$order->add_order_note( sprintf( __( 'CR: a review reminder could not be scheduled via CR Cron. Error %d.', IVOLE_TEXT_DOMAIN ), $sender_result ) );
						}
					}
				} else {
					//the logic for WP Cron otherwise
					$timestamp = apply_filters( 'cr_reminder_delay', time() + $delay * (24 * 60 * 60), $order_id );
					if( false === wp_schedule_single_event( $timestamp, 'ivole_send_reminder', array( $order_id ) ) ) {
						$order->add_order_note( __( 'CR: a review reminder could not be scheduled.', IVOLE_TEXT_DOMAIN ) );
					} else {
						$count = get_post_meta( $order_id, '_ivole_review_reminder', true );
						if( !$count ) {
							update_post_meta( $order_id, '_ivole_review_reminder', 0 );
						}
						$order->add_order_note( sprintf( __( 'CR: a review reminder was successfully scheduled for %s.', IVOLE_TEXT_DOMAIN ) , date_i18n( 'F j, Y g:i a', $timestamp ) ) );
					}
				}
			}
		}

		public function sender_action( $order_id, $schedule = false ) {
			//check for duplicate / staging / test site
			if( ivole_is_duplicate_site() ) {
				update_option( 'ivole_enable', 'no' );
				return -1;
			}
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
			return $result;
		}

		// public function new_order_trigger( $order_id ) {
		// 	//initiate the count of review reminders sent in order meta
		// 	if( $order_id ) {
		// 		$count = get_post_meta( $order_id, '_ivole_review_reminder', true );
		// 		if( !$count ) {
		// 			update_post_meta( $order_id, '_ivole_review_reminder', 0 );
		// 		}
		// 	}
		// }
	}

endif;

?>
