<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Endpoint' ) ) :

	require_once('class-ivole-email.php');
	require_once('class-ivole-email-coupon.php');
	require_once('class-cr-custom-questions.php');

	class Ivole_Endpoint {
	  public function __construct() {
			add_action( 'rest_api_init', array( $this, 'init_endpoint' ) );
	  }

		public function init_endpoint( ) {
			$this->register_routes();
		}

		public function register_routes() {
	    $version = '1';
	    $namespace = 'ivole/v' . $version;
	    register_rest_route( $namespace, '/review', array(
	      array(
	        'methods'         => WP_REST_Server::CREATABLE,
	        'callback'        => array( $this, 'create_review' ),
	        'permission_callback' => array( $this, 'create_review_permissions_check' ),
	        'args'            => array(),
	      ),
	    ) );
	  }

		public function create_review( $request ) {
			global $wpdb;
			$body = $request->get_body();
			$body2 = json_decode( $body );
			if( json_last_error() === JSON_ERROR_NONE ) {
				if( isset( $body2->key ) && isset( $body2->order ) ) {
					if( isset( $body2->order->id ) && isset( $body2->order->items ) ) {
						//error_log( print_r( $body2, true ) );
						$order_id = intval( $body2->order->id );
						$order = new WC_Order( $order_id );
						$customer_name = '';
						$customer_first_name = '';
						$customer_last_name = '';
						$customer_email = '';

						//check if registered customers option is used
						$registered_customers = false;
						if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
							$registered_customers = true;
						}

						if( method_exists( $order, 'get_billing_email' ) ) {
							// Woocommerce version 3.0 or later
							if( $registered_customers ) {
								$user = $order->get_user();
								if( $user ) {
									$customer_email = $user->user_email;
								} else {
									$customer_email = $order->get_billing_email();
								}
							} else {
								$customer_email = $order->get_billing_email();
							}
							$customer_first_name = $order->get_billing_first_name();
							$customer_last_name = $order->get_billing_last_name();
							$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
							$order_date = date_i18n( 'd.m.Y', strtotime( $order->get_date_created() ) );
							$order_currency = $order->get_currency();
						} else {
							// Woocommerce before version 3.0
							if( $registered_customers ) {
								$user_id = get_post_meta( $order_id, '_customer_user', true );
								if( $user_id ) {
									$user = get_user_by( 'id', $user_id );
									if( $user ) {
										$customer_email = $user->user_email;
									} else {
										$customer_email = get_post_meta( $order_id, '_billing_email', true );
									}
								} else {
									$customer_email = get_post_meta( $order_id, '_billing_email', true );
								}
							} else {
								$customer_email = get_post_meta( $order_id, '_billing_email', true );
							}
							$customer_first_name = get_post_meta( $order_id, '_billing_first_name', true );
							$customer_last_name = get_post_meta( $order_id, '_billing_last_name', true );
							$customer_name = get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true );
							$order_date = date_i18n( 'd.m.Y', strtotime( $order->order_date ) );
							$order_currency = $order->get_order_currency();
						}

						//if customer specified preference for display name, take into account their preference
						if( !empty( $body2->order->display_name ) ) {
							$customer_name = strval( $body2->order->display_name );
						}

						//settings for moderation of reviews
						$comment_approved = 1;
						$moderation_enabled = get_option( 'ivole_enable_moderation', 'no' );
						if( $moderation_enabled === 'yes' ) {
							$comment_approved = 0;
						}

						//Find WordPress user ID of the customer (if a customer has an account)
						$customer_user = get_user_by( 'email', $customer_email );
						$customer_user_id = 0;
						if( $customer_user ) {
							$customer_user_id = $customer_user->ID;
						}

						//Country / region of the customer
						$country = null;
						if( isset( $body2->geo_location ) && isset( $body2->geo_location->code )
					 		&& isset( $body2->geo_location->desc ) ) {
							$country_code = sanitize_text_field( $body2->geo_location->code );
							if( strlen( $country_code ) > 0 ) {
								$country = array( 'code' => $country_code,
							 		'desc' => sanitize_text_field( $body2->geo_location->desc ) );
							}
						}

						$previous_comments_exist = false;
						$comment_date = current_time( 'mysql' );

						//shop review
						if( isset( $body2->order->shop_rating ) && isset( $body2->order->shop_comment ) ) {
							$shop_page_id = wc_get_page_id( 'shop' );
							if( $shop_page_id > 0 ) {
								$shop_comment_text = strval( $body2->order->shop_comment );

								//check if API provided replies to custom questions
								$shop_custom_questions = new CR_Custom_Questions();
								$shop_custom_questions->parse_shop_questions( $body2->order );

								//WPML integration
								//wc_get_page_id returns shop page ID in the default WPML site language
								//If a review was submitted in a language different from the default one, it is necessary to get shop page ID for the non-default language
								$ivole_language = get_option( 'ivole_language' );
								$wpml_current_lang = '';
								if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
									$wpml_order_language = get_post_meta( $order_id, 'wpml_language', true );
									$shop_page_id = apply_filters( 'wpml_object_id', $shop_page_id, 'page', true, $wpml_order_language );
									//switch the current WPML site language to the language of the order because
									//call to get_comments (below) returns only comments for shop page in the current WPML language
									$wpml_current_lang = apply_filters( 'wpml_current_language', null );
									do_action( 'wpml_switch_language', $wpml_order_language );
								}
								// Polylang integration
								if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_get_post_language' ) && $ivole_language === 'WPML' ) {
									$polylang_order_language = pll_get_post_language( $order_id );
									$shop_page_id = pll_get_post( $shop_page_id, $polylang_order_language  );
								}

								// check if a shop review has already been submitted for this order by this customer
								$args = array(
									'post_id' => $shop_page_id,
									'author_email' => $customer_email,
									'meta_key' => 'ivole_order',
									'meta_value' => $order_id,
									'orderby' => 'comment_ID',
									'order' => 'DESC'
								);
								$existing_comments = get_comments( $args );
								$num_existing_comments = count( $existing_comments );

								//WPML integration
								if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
									do_action( 'wpml_switch_language', $wpml_current_lang );
								}

								if( $num_existing_comments > 0 ) {
									$previous_comments_exist = true;
									$review_id = $existing_comments[0]->comment_ID;
									$commentdata = array(
										'comment_ID' => $review_id,
										'comment_content' => $shop_comment_text,
										'comment_approved' => $comment_approved,
										'comment_author' => $customer_name,
									 	'comment_date' => $comment_date,
									 	'comment_date_gmt' => get_gmt_from_date( $comment_date ) );
									wp_update_comment( $commentdata );
									update_comment_meta( $review_id, 'rating', intval( $body2->order->shop_rating ) );
									if( $country ) {
										update_comment_meta( $review_id, 'ivole_country', $country );
									} else {
										delete_comment_meta( $review_id, 'ivole_country' );
									}
									if( $shop_custom_questions->has_questions() ) {
										$shop_custom_questions->save_questions( $review_id );
									} else {
										$shop_custom_questions->delete_questions( $review_id );
									}
									wp_update_comment_count_now( $shop_page_id );
								} else {
									$commentdata = array(
										'comment_author' => $customer_name,
										'comment_author_email' => $customer_email,
										'comment_author_url' => '',
										'user_id' => $customer_user_id,
										'comment_content' => $shop_comment_text,
										'comment_post_ID' =>  $shop_page_id,
										'comment_type' => 'review',
										'comment_approved' => $comment_approved,
										'comment_meta' => array( 'rating' => intval( $body2->order->shop_rating ) ) );
									$review_id = wp_insert_comment( $commentdata );
									if( !$review_id ) {
										//adding a new review may fail, if review fields include characters that are not supported by DB
										//for example, collation utf8_general_ci does not support emoticons
										//in these cases, we will remove unsupported characters and try to add the review again
										$tfields = array( 'comment_author', 'comment_author_email', 'comment_content' );
										foreach ( $tfields as $field ) {
											if ( isset( $commentdata[ $field ] ) ) {
													$commentdata[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $commentdata[ $field ] );
											}
										}
										$review_id = wp_insert_comment( $commentdata );
									}
									if( $review_id ) {
										//add_comment_meta( $review_id, 'rating', intval( $body2->order->shop_rating ), true );
										add_comment_meta( $review_id, 'ivole_order', $order_id, true );
										if( $country ) {
											update_comment_meta( $review_id, 'ivole_country', $country );
										}
										if( $shop_custom_questions->has_questions() ) {
											$shop_custom_questions->save_questions( $review_id );
										}
										wp_update_comment_count_now( $shop_page_id );
										// set current user to emulate submission of review by a real user - it is necessary for compatibility with other plugins
										$current_user = wp_get_current_user();
										if( $customer_user_id ) {
											wp_set_current_user( $customer_user_id );
											do_action( 'comment_post', $review_id, $commentdata['comment_approved'], $commentdata );
										}
										// set the previous current user back
										if( $current_user ) {
											wp_set_current_user( $current_user->ID );
										} else {
											wp_set_current_user( 0 );
										}
									} else {
										return new WP_REST_Response( 'Review creation error 3', 500 );
									}
								}
							}
						}

						//product reviews
						$result = true;
						if( is_array( $body2->order->items ) ) {
							$num_items = count( $body2->order->items );
							for( $i = 0; $i < $num_items; $i++ ) {
								//error_log( print_r( $body2->order->items[$i], true) );
								if( isset( $body2->order->items[$i]->rating ) && isset( $body2->order->items[$i]->id ) ) {
									// check if replies to custom questions were provided
									$product_custom_questions = new CR_Custom_Questions();
									$product_custom_questions->parse_product_questions( $body2->order->items[$i] );

									// check if review text was provided, if not then we will be adding an empty comment
									$comment_text = '';
									if( isset( $body2->order->items[$i]->comment ) ) {
										$comment_text = strval( $body2->order->items[$i]->comment );
									}

									// check if media files were provided
									$media_meta = array();
									if( isset( $body2->order->items[$i]->media ) && is_array( $body2->order->items[$i]->media ) ) {
										$num_media = count( $body2->order->items[$i]->media );
										for( $m = 0; $m < $num_media; $m++ ) {
											// image
											if( 'image' === $body2->order->items[$i]->media[$m]->type && isset( $body2->order->items[$i]->media[$m]->href ) ) {
												$media_meta[] = array(
													'meta' => 'ivole_review_image',
													'value' => array( 'url' => $body2->order->items[$i]->media[$m]->href )
												);
											}
											// video
											else if( 'video' === $body2->order->items[$i]->media[$m]->type && isset( $body2->order->items[$i]->media[$m]->href) ) {
												$media_meta[] = array(
													'meta' => 'ivole_review_video',
													'value' => array( 'url' => $body2->order->items[$i]->media[$m]->href )
												);
											}
										}
									}
									$media_meta_count = count( $media_meta );

									$order_item_product_id = intval( $body2->order->items[$i]->id );

									//WPML integration
									//The order contains product ID of the product in the default WPML site language
									//If a review was submitted in a language different from the default one, it is necessary to get product ID for the non-default language
									$ivole_language = get_option( 'ivole_language' );
									$wpml_current_lang = '';
									if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
										$wpml_order_language = get_post_meta( $order_id, 'wpml_language', true );
										$order_item_product_id = apply_filters( 'wpml_object_id', $order_item_product_id, 'product', true, $wpml_order_language );
										//switch the current WPML site language to the language of the order because
										//call to get_comments (below) returns only comments for products in the current WPML language
										$wpml_current_lang = apply_filters( 'wpml_current_language', null );
										do_action( 'wpml_switch_language', $wpml_order_language );
									}
									// Polylang integration
									if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_get_post_language' ) && $ivole_language === 'WPML' ) {
										$polylang_order_language = pll_get_post_language( $order_id );
										$order_item_product_id = pll_get_post( $order_item_product_id, $polylang_order_language  );
									}

									// check if a review has already been submitted for this product and for this order by this customer
									$args = array(
										'post_id' => $order_item_product_id,
										'author_email' => $customer_email,
										'meta_key' => 'ivole_order',
										'meta_value' => $order_id,
										'orderby' => 'comment_ID',
										'order' => 'DESC'
									);
									$existing_comments = get_comments( $args );
									$num_existing_comments = count( $existing_comments );

									//WPML integration
									if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
										do_action( 'wpml_switch_language', $wpml_current_lang );
									}

									if( $num_existing_comments > 0 ) {
										// there are previous comment(s) submitted via external form
										$previous_comments_exist = true;
										$review_id = $existing_comments[0]->comment_ID;
										$commentdata = array(
											'comment_ID' => $review_id,
										 	'comment_content' => $comment_text,
										 	'comment_approved' => $comment_approved,
										 	'comment_author' => $customer_name,
											'comment_date' => $comment_date,
										 	'comment_date_gmt' => get_gmt_from_date( $comment_date ) );
										wp_update_comment( $commentdata );
										update_comment_meta( $review_id, 'rating', intval( $body2->order->items[$i]->rating ) );
										update_comment_meta( $review_id, 'ivole_country', $country );
										//remove previously added media files
										delete_comment_meta( $review_id, 'ivole_review_image' );
										delete_comment_meta( $review_id, 'ivole_review_video' );
										// add media files to meta if they exist
										if( $media_meta_count > 0 ) {
											for( $m = 0; $m < $media_meta_count; $m++ ) {
												add_comment_meta( $review_id, $media_meta[$m]['meta'], $media_meta[$m]['value'], false );
											}
										}
										if( $product_custom_questions->has_questions() ) {
											$product_custom_questions->save_questions( $review_id );
										} else {
											$product_custom_questions->delete_questions( $review_id );
										}
										wp_update_comment_count_now( $order_item_product_id );
									} else {
										// there are no previous comment(s) submitted via external form for this order and product
										$commentdata = array(
											'comment_author' => $customer_name,
									 		'comment_author_email' => $customer_email,
											'comment_author_url' => '',
											'user_id' => $customer_user_id,
										 	'comment_content' => $comment_text,
										 	'comment_post_ID' =>  $order_item_product_id,
										 	'comment_type' => 'review',
										 	'comment_approved' => $comment_approved,
										 	'comment_meta' => array( 'rating' => intval( $body2->order->items[$i]->rating ) ) );
										$review_id = wp_insert_comment( $commentdata );
										if( !$review_id ) {
											//adding a new review may fail, if review fields include characters that are not supported by DB
											//for example, collation utf8_general_ci does not support emoticons
											//in these cases, we will remove unsupported characters and try to add the review again
											$tfields = array( 'comment_author', 'comment_author_email', 'comment_content' );
							        foreach ( $tfields as $field ) {
						            if ( isset( $commentdata[ $field ] ) ) {
						                $commentdata[ $field ] = $wpdb->strip_invalid_text_for_column( $wpdb->comments, $field, $commentdata[ $field ] );
						            }
							        }
											$review_id = wp_insert_comment( $commentdata );
										}
										if( $review_id ) {
											//add_comment_meta( $review_id, 'rating', intval( $body2->order->items[$i]->rating ), true );
											add_comment_meta( $review_id, 'ivole_order', $order_id, true );
											if( $country ) {
												update_comment_meta( $review_id, 'ivole_country', $country );
											}
											// add media files to meta if they exist
											if( $media_meta_count > 0 ) {
												for( $m = 0; $m < $media_meta_count; $m++ ) {
													add_comment_meta( $review_id, $media_meta[$m]['meta'], $media_meta[$m]['value'], false );
												}
											}
											if( $product_custom_questions->has_questions() ) {
												$product_custom_questions->save_questions( $review_id );
											}
											wp_update_comment_count_now( $order_item_product_id );
											// set current user to emulate submission of review by a real user - it is necessary for compatibility with other plugins
											$current_user = wp_get_current_user();
											if( $customer_user_id ) {
												wp_set_current_user( $customer_user_id );
												do_action( 'comment_post', $review_id, $commentdata['comment_approved'], $commentdata );
											}
											// set the previous current user back
											if( $current_user ) {
												wp_set_current_user( $current_user->ID );
											} else {
												wp_set_current_user( 0 );
											}
										} else {
											$result = false;
										}
									}
								}
							}
						}
						//if there was a problem with any product review, return an error
						if( !$result ) {
							return new WP_REST_Response( 'Review creation error 1', 500 );
						}
						// if there are previous comments, it means that the customer has already received a coupon
						// and we don't need to send another one, so return early
						if( $previous_comments_exist ) {
							// send result to the endpoint
							return new WP_REST_Response( '', 200 );
						}
						// send a coupon to the customer
						$coupon_enabled = get_option( 'ivole_coupon_enable', 'no' );
						if( $coupon_enabled === 'yes' ) {
							//qTranslate integration
							$lang = get_post_meta( $order_id, '_user_language', true );
							$old_lang = '';
							if( $lang ) {
								global $q_config;
								$old_lang = $q_config['language'];
								$q_config['language'] = $lang;
							}

							$ec = new Ivole_Email_Coupon( $order_id );

							$coupon_type = get_option( 'ivole_coupon_type', 'static' );
							if( $coupon_type === 'static' ) {
								$coupon_id = get_option( 'ivole_existing_coupon', 0 );
							} else {
								$coupon_id = $ec->generate_coupon( $customer_email, $order_id );
								// compatibility with W3 Total Cache plugin
								// clear DB cache to read properties of the coupon
								if( function_exists( 'w3tc_dbcache_flush' ) ) {
									w3tc_dbcache_flush();
								}
							}
							if( $coupon_id > 0 && get_post_type( $coupon_id ) === 'shop_coupon' && get_post_status( $coupon_id ) === 'publish' ) {

								$coupon_code = get_post_field( 'post_title', $coupon_id );
								$discount_type = get_post_meta( $coupon_id, 'discount_type', true );
								$discount_amount = get_post_meta( $coupon_id, 'coupon_amount', true );
								$discount_string = "";
								if( $discount_type == "percent" && $discount_amount > 0 ) {
									$discount_string = $discount_amount . "%";
								} elseif( $discount_amount > 0 ) {
									$discount_string = trim( strip_tags( wc_price( $discount_amount, array( 'currency' => get_option( 'woocommerce_currency' ) ) ) ) );
								}

								$ec->replace['customer-first-name'] = $customer_first_name;
								$ec->replace['customer-name'] = $customer_name;
								$ec->replace['coupon-code'] = $coupon_code;
								$ec->replace['discount-amount'] = $discount_string;

								$from_address = get_option( 'ivole_email_from', '' );
								$from_name = get_option( 'ivole_email_from_name', Ivole_Email::get_blogname() );
								$footer = get_option( 'ivole_email_footer', '' );

								// check if Reply-To address needs to be added to email
								$replyto = get_option( 'ivole_coupon_email_replyto', get_option( 'admin_email' ) );
								if( filter_var( $replyto, FILTER_VALIDATE_EMAIL ) ) {
									$replyto = $replyto;
								} else {
									$replyto = get_option( 'admin_email' );
								}

								$bcc_address = get_option( 'ivole_coupon_email_bcc', '' );
								if( !filter_var( $bcc_address, FILTER_VALIDATE_EMAIL ) ) {
									$bcc_address = '';
								}

								$message = $ec->get_content();
								$message = $ec->replace_variables( $message );

								$data = array(
									'token' => '164592f60fbf658711d47b2f55a1bbba',
									'shop' => array( "name" => Ivole_Email::get_blogname(),
								 		'domain' => Ivole_Email::get_blogurl(),
									 	'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) ) ),
									'email' => array( 'to' => $customer_email,
										'from' => $from_address,
										'fromText' => $from_name,
										'replyTo' => $replyto,
										'bcc' => $bcc_address,
								 		'subject' => $ec->replace_variables( $ec->subject ),
										'header' => $ec->replace_variables( $ec->heading ),
										'body' => $message,
									 	'footer' => $ec->footer ),
									'customer' => array( 'firstname' => $customer_first_name,
										'lastname' => $customer_last_name ),
									'order' => array( 'id' => strval( $order_id ),
								 		'date' => $order_date,
										'currency' => $order_currency,
									 	'items' => Ivole_Email::get_order_items2( $order ) ),
									'discount' => array('type' => $discount_type,
										'amount' => $discount_amount,
										'code' => $coupon_code ),
									'colors' => array(
										'email' => array(
											"bg" => get_option( 'ivole_email_coupon_color_bg', '#0f9d58' ),
											'text' => get_option( 'ivole_email_coupon_color_text', '#ffffff' )
										)
									),
									'language' => $ec->language,
								);
								$license = get_option( 'ivole_license_key', '' );
								if( strlen( $license ) > 0 ) {
									$data['licenseKey'] = $license;
								}
								$api_url = 'https://api.cusrev.com/v1/production/review-discount';
								$data_string = json_encode( $data );
								//error_log( $data_string );
								$ch = curl_init();
								curl_setopt( $ch, CURLOPT_URL, $api_url );
								curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
								curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
								curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
								curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
									'Content-Type: application/json',
									'Content-Length: ' . strlen( $data_string ) )
								);
								$result = curl_exec( $ch );
								//error_log( $result );
								$result = json_decode( $result );
							}
							//qTranslate integration
							if( $lang ) {
								$q_config['language'] = $old_lang;
							}
						}

						// send result to the endpoint
						if( $result ) {
							return new WP_REST_Response( '', 200 );
						} else {
							return new WP_REST_Response( 'Review creation error 2', 500 );
						}
					}
				} else if( isset( $body2->test ) ) {
					return new WP_REST_Response( 'CR Test OK', 200 );
				}
			}
			return new WP_REST_Response( 'Generic error', 500 );
		}

		public function create_review_permissions_check( WP_REST_Request $request ) {
			$body = $request->get_body();
			//error_log( print_r( $body, true) );
			$body2 = json_decode( $body );
			if( json_last_error() === JSON_ERROR_NONE ) {
				if( isset( $body2->key ) && isset( $body2->order ) ) {
					if( isset( $body2->order->id ) ) {
						$saved_key = get_post_meta( $body2->order->id, 'ivole_secret_key', true );
						if( $body2->key === $saved_key ) {
							//error_log( 'keys match!' );
							return true;
						}
					}
				} else if( isset( $body2->test ) ) {
					//error_log( print_r( get_option( 'ivole_test_secret_key' ), true ) );
					if( false != get_option( 'ivole_test_secret_key' ) && $body2->test === get_option( 'ivole_test_secret_key' ) ){
						 return true;
					}
				}
			}
			return false;
		}
	}

endif;

?>
