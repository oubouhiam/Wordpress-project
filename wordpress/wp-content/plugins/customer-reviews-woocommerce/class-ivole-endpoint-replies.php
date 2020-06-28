<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('firebase/src/JWT.php');
require_once('firebase/src/SignatureInvalidException.php');
use \ivole\Firebase\JWT\JWT;

if ( ! class_exists( 'Ivole_Endpoint_Replies' ) ) :

	class Ivole_Endpoint_Replies {

		private $decoded_message = null;

	  public function __construct() {
			add_action( 'rest_api_init', array( $this, 'init_endpoint' ) );
	  }

		public function init_endpoint( ) {
			$this->register_routes();
		}

		public function register_routes() {
	    $version = '1';
	    $namespace = 'ivole/v' . $version;
	    register_rest_route( $namespace, '/review-reply', array(
	      array(
	        'methods'         => 'POST, PATCH',
	        'callback'        => array( $this, 'manage_replies' ),
	        'permission_callback' => array( $this, 'manage_replies_permissions_check' ),
	        'args'            => array(),
	      ),
	    ) );
	  }

		public function manage_replies( $request ) {
			if( $this->decoded_message ) {
				if( empty( $this->decoded_message->text ) ) {
					return new WP_REST_Response( 'Reply must be nonempty', 400 );
				}
				if( !empty( $this->decoded_message->callbackKey ) && !empty( $this->decoded_message->shopOrderId ) &&
					!empty( $this->decoded_message->shopProductId ) && !empty( $this->decoded_message->text ) ) {

						$order = new WC_Order( $this->decoded_message->shopOrderId );

						//check if registered customers option is used
						$registered_customers = false;
						if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
							$registered_customers = true;
						}
						//check if moderation is enabled
						$comment_approved = 1;
						$moderation_enabled = get_option( 'ivole_enable_moderation', 'no' );
						if( $moderation_enabled === 'yes' ) {
							$comment_approved = 0;
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
						}

						$customer_user = get_user_by( 'email', $customer_email );
						$customer_user_id = 0;
						if( $customer_user ) {
							$customer_user_id = $customer_user->ID;
						}

						$product_id = $this->decoded_message->shopProductId;
						//check for shop reviews
						if( -1 === intval( $product_id ) ) {
							$product_id = wc_get_page_id( 'shop' );
						}

						//find ID of the review corresponding to the reply
						$review_id = 0;
						$args = array(
							'post_id' => $product_id,
							'meta_key' => 'ivole_order',
							'meta_value' => $this->decoded_message->shopOrderId,
							'orderby' => 'comment_ID',
							'order' => 'DESC'
						);
						$existing_reviews = get_comments( $args );
						$num_existing_reviews = count( $existing_reviews );
						if( 0 < $num_existing_reviews ) {
							$review_id = $existing_reviews[0]->comment_ID;
							$customer_name = $existing_reviews[0]->comment_author;
						}

						if( $review_id > 0 ) {
							//check if shopReplyID was provided in API call
							//if it was provided, it means we are updating an existing reply
							if( !empty( $this->decoded_message->shopReplyID ) && get_comment( $this->decoded_message->shopReplyID ) ) {
								$commentdata = array(
									'comment_ID' => $this->decoded_message->shopReplyID,
									'comment_content' => sanitize_text_field( $this->decoded_message->text ),
									'comment_approved' => $comment_approved,
									'comment_author' => $customer_name,
								 	'comment_parent' => $review_id );
								wp_update_comment( $commentdata );
								$meta[] = 202;
								$meta[] = __( 'This reply was originally posted on CR portal', IVOLE_TEXT_DOMAIN );
								update_comment_meta( $this->decoded_message->shopReplyID, 'ivole_reply', $meta );
								wp_update_comment_count_now( $product_id );
								return new WP_REST_Response( array( 'replyId' => strval( $this->decoded_message->shopReplyID ) ), 201 );
							} else {
								$commentdata = array(
									'comment_author' => $customer_name,
									'comment_author_email' => $customer_email,
									'comment_author_url' => '',
									'user_id' => $customer_user_id,
									'comment_content' => sanitize_text_field( $this->decoded_message->text ),
									'comment_post_ID' => $product_id,
									'comment_type' => '',
									'comment_approved' => $comment_approved,
								 	'comment_parent' => $review_id );
								$reply_id = wp_insert_comment( $commentdata );
								if( $reply_id ) {
									$meta[] = 202;
									$meta[] = __( 'This reply was originally posted on CR portal', IVOLE_TEXT_DOMAIN );
									update_comment_meta( $reply_id, 'ivole_reply', $meta );
									wp_update_comment_count_now( $product_id );
									return new WP_REST_Response( array( 'replyId' => strval( $reply_id ) ), 201 );
								}
							}
						} else {
							return new WP_REST_Response( 'Review could not be found', 500 );
						}
				}
			}
			return new WP_REST_Response( 'Generic error', 500 );
		}

		public function manage_replies_permissions_check( WP_REST_Request $request ) {
			$body = $request->get_body();
			if( !empty( $body ) ) {
					$key = get_option( 'ivole_license_key' );
					if( empty( $key ) ) {
						return false;
					}
					try {
						//error_log( print_r( $body, true ) );
						//error_log( print_r( $key, true ) );
						$decoded = JWT::decode( $body, $key, array('HS256') );
						if( $decoded ) {
							if( !empty( $decoded->callbackKey ) && !empty( $decoded->shopOrderId ) &&
								!empty( $decoded->shopProductId ) ) {
									$saved_key = get_post_meta( $decoded->shopOrderId, 'ivole_secret_key', true );
									if( $decoded->callbackKey === $saved_key ) {
										//error_log( print_r( $decoded, true ) );
										$this->decoded_message = $decoded;
										return true;
									}
								}
						}
					} catch( Exception $e ) {
						//error_log( print_r( $e->getMessage(), true ) );
						return false;
					}
					//error_log( print_r( $decoded, true ) );
			}
			return false;
		}
	}

endif;

?>
