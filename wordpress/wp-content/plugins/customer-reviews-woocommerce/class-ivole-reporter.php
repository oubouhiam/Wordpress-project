<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Reporter' ) ) :

	require_once('class-ivole-email.php');

	class Ivole_Reporter {
	  public function __construct() {
			// Triggers for paid orders
			add_action( 'woocommerce_order_status_processing', array( $this, 'reporter_trigger' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'reporter_trigger' ) );
	  }

		public function reporter_trigger( $order_id ) {
			if( $order_id ) {
				$order = new WC_Order( $order_id );
				$order_status = $order->get_status();
				//error_log( "Order status: " . $order->get_status() );
				if( 'processing' === $order_status || 'completed' === $order_status ) {
					// check coupons used in this order
					$coupons = $order->get_used_coupons();
					if( $coupons ) {
						if( count( $coupons ) > 0 ) {
							$customer_email = '';
							$customer_first_name = '';
							$customer_last_name = '';
							$order_date = '';
							$order_currency = '';

							//check if registered customers option is used
							$registered_customers = false;
							if( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
								$registered_customers = true;
							}

							// get information about the order
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
								$order_date = date_i18n( 'd.m.Y', strtotime( $order->order_date ) );
								$order_currency = $order->get_order_currency();
							}

							foreach( $coupons as $coupon) {
								$coupon_obj = new WC_Coupon( $coupon );
								$discount_type = '';
								$discount_amount = '';
								if( method_exists( $order, 'get_billing_email' ) ) {
									// Woocommerce version 3.0 or later
									$discount_type = $coupon_obj->get_discount_type();
									$discount_amount = $coupon_obj->get_amount();
								} else {
									// Woocommerce before version 3.0
									$discount_type = $coupon_obj->type;
									$discount_amount = $coupon_obj->amount;
								}
								$data = array(
									'token' => '164592f60fbf658711d47b2f55a1bbba',
									'shop' => array( 'name' => Ivole_Email::get_blogname(),
								 		'domain' => Ivole_Email::get_blogurl(),
									 	'country' => apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) ) ),
									'customer' => array( 'email' => $customer_email,
								 		'firstname' => $customer_first_name,
										'lastname' => $customer_last_name ),
									'order' => array( 'id' => strval( $order_id ),
										'date' => $order_date,
										'currency' => $order_currency,
										'items' => Ivole_Email::get_order_items2( $order ) ),
									'discount' => array('type' => $discount_type,
										'amount' => $discount_amount,
										'code' => $coupon )
								);
								$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/discount-usage';
								$data_string = json_encode($data);
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
							}
						}
					}
				}
			}
		}
	}

endif;

?>
