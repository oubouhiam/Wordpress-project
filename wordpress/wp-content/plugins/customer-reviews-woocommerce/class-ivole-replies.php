<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once('firebase/src/JWT.php');
use \ivole\Firebase\JWT\JWT;

if ( ! class_exists( 'Ivole_Replies' ) ) :

	require_once('class-ivole-email.php');

	class Ivole_Replies {
		private $api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/review-reply';

	  public function __construct( $comment_id ) {
			if( 'yes' == get_option( 'ivole_reviews_verified', 'no' ) ) {
				$comment = get_comment( $comment_id );
	    	if ( $comment ) {
					// get parent comment (orignal review) and find order number related to this review
					//it is possible that we have a reply to reply
					//in this case, we will have to loop through previous replies to find the original review
					$max_iterations = 200;
					$parent_id = $comment->comment_parent;
					$i = 0;
					while( $parent_id ) {
						$i++;
						//just a safety measure to avoid infinite loop
						if( $i > $max_iterations ) {
							break;
						}
						$parent = get_comment( $parent_id );
						if( $parent ) {
							if( $parent->comment_parent ) {
								$parent_id = $parent->comment_parent;
								continue;
							} else {
								$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
								$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
								if( $ivole_order && $rating ) {
									$current_user = wp_get_current_user();

									if( $current_user->ID ) {
										$key = get_option( 'ivole_license_key' );
										$payload = array(
											'iss' => Ivole_Email::get_blogurl(),
											'aud' => 'www.cusrev.com',
											'iat' => time()
										);
										$jwt = JWT::encode( $payload, $key, 'HS256' );
										//support for shop pages (product ID = -1)
										$product_id = $parent->comment_post_ID;
										$shop_page_id = wc_get_page_id( 'shop' );
										if( $shop_page_id == $product_id ) {
											$product_id = '-1';
										}
										//WPML integration
										$ivole_language = get_option( 'ivole_language' );
										if ( has_filter( 'wpml_object_id' ) && $ivole_language === 'WPML' ) {
											$ivole_language = get_post_meta( $ivole_order, 'wpml_language', true );
										}
										if ( empty( $ivole_language ) ) {
											$ivole_language = 'EN';
										}
										$data = array(
											'shopDomain' => Ivole_Email::get_blogurl(),
											'orderId' => $ivole_order,
											'productId' => $product_id,
											'replyId' => strval( $comment_id ),
											'replyType' => 1, //1 means this is a reply from a shop owner
											'email' => $current_user->user_email,
											'language' => $ivole_language,
											'text' => $comment->comment_content,
											'token' => $jwt
										);
										$data_string = json_encode( $data );
										//error_log( print_r( $data_string, true ) );
										$ch = curl_init();
										curl_setopt( $ch, CURLOPT_URL, $this->api_url );
										curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
										curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
										curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
										curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
											'Content-Type: application/json',
											'Content-Length: ' . strlen( $data_string ) )
										);
										$result = curl_exec( $ch );
										//error_log( print_r( $result, true ) );
										$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
										if( $httpcode ) {
											$meta = array();
											if( 201 === $httpcode ) {
												//success
												$meta[] = 201;
												$meta[] = __( 'A copy of the reply was published on CR', IVOLE_TEXT_DOMAIN );
											} else {
												//some error
												$resultd = json_decode( $result );
												if( $resultd && isset( $resultd->code ) && isset( $resultd->error ) ) {
													$meta[] = $resultd->code;
													$meta[] = $resultd->error;
												} else {
													$meta[] = 999;
													$meta[] = __( 'Unknown error', IVOLE_TEXT_DOMAIN );
												}
											}
											update_comment_meta( $comment_id, 'ivole_reply', $meta );
										} else {
											//error_log( print_r( $result, true ) );
											if( false === $result ) {
												$meta = array();
												$meta[] = 997;
												$meta[] = curl_error( $ch );
											} else {
												$meta = array();
												$meta[] = 998;
												$meta[] = __( 'Unknown error', IVOLE_TEXT_DOMAIN );
											}
											update_comment_meta( $comment_id, 'ivole_reply', $meta );
										}
									} else {
										$meta = array();
										$meta[] = 996;
										$meta[] = __( 'ID of the current user is not set', IVOLE_TEXT_DOMAIN );
										update_comment_meta( $comment_id, 'ivole_reply', $meta );
									}
								}
								break;
							}
						} else {
							break;
						}
					}
				};
			}
	  }

		public static function isReplyForCRReview( $comment ) {
			if( $comment && $comment->comment_parent ) {
				$parent_id = $comment->comment_parent;
				$max_iterations = 200;
				$i = 0;
				while( $parent_id ) {
					$i++;
					//just a safety measure to avoid infinite loop
					if( $i > $max_iterations ) {
						break;
					}
					$parent = get_comment( $parent_id );
					if( $parent ) {
						if( $parent->comment_parent ) {
							$parent_id = $parent->comment_parent;
							continue;
						} else {
							$ivole_order = get_comment_meta( $parent->comment_ID, 'ivole_order', true );
							$rating = get_comment_meta( $parent->comment_ID, 'rating', true );
							if( $ivole_order && $rating ) {
								return array( $ivole_order, $parent->comment_post_ID );
							} else {
								return false;
							}
						}
					} else {
						return false;
					}
				}
			}
			return false;
		}

	}

endif;

?>
