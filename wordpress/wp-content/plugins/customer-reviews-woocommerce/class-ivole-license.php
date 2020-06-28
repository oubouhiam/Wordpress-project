<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_License' ) ) :

  require_once('class-ivole-email.php');

	class Ivole_License {
	  public function __construct() {
	  }

		public function check_license() {
      $licenseKey = get_option( 'ivole_license_key', '' );
      if( strlen( $licenseKey ) === 0 ) {
				update_option( 'ivole_reviews_nobranding', 'no' );
				update_option( 'ivole_shop_logo', NULL );
        return __( 'No license key entered', IVOLE_TEXT_DOMAIN );
      }
      $data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'licenseKey' => $licenseKey,
        'shopDomain' => Ivole_Email::get_blogurl()
			);
			$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/check-license';
      $data_string = json_encode($data);
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
      if( false === $result ) {
  			return __( 'Unknown: ', IVOLE_TEXT_DOMAIN ) . curl_error( $ch );
  		}
      $result = json_decode( $result );
			//error_log( print_r( $result, true ) );
      if( isset( $result->error ) ) {
				update_option( 'ivole_shop_logo', NULL );
				update_option( 'ivole_form_rating_bar', 'smiley' );
				update_option( 'ivole_form_geolocation', 'no' );
        return __( 'Not Active: ', IVOLE_TEXT_DOMAIN ) . $result->error;
      } else if( isset( $result->valid ) && 1 == $result->valid ) {
        return __( 'Active: Professional Version', IVOLE_TEXT_DOMAIN );
      } else if( isset( $result->expired ) && 1 == $result->expired ) {
				update_option( 'ivole_shop_logo', NULL );
				update_option( 'ivole_form_rating_bar', 'smiley' );
				update_option( 'ivole_form_geolocation', 'no' );
        return __( 'Expired: Professional Version', IVOLE_TEXT_DOMAIN );
			} else if( isset( $result->expired ) && isset( $result->valid )
					&& false === $result->expired && false === $result->valid ) {
				update_option( 'ivole_shop_logo', NULL );
				update_option( 'ivole_form_rating_bar', 'smiley' );
				update_option( 'ivole_form_geolocation', 'no' );
        return __( 'Active: Free Version', IVOLE_TEXT_DOMAIN );
      } else {
				update_option( 'ivole_shop_logo', NULL );
				update_option( 'ivole_form_rating_bar', 'smiley' );
				update_option( 'ivole_form_geolocation', 'no' );
        return __( 'Unknown Error', IVOLE_TEXT_DOMAIN );
      }
		}

    public function register_license( $new_license ) {
      if( strlen( $new_license ) === 0 ) {
        return;
      }
      $data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'licenseKey' => $new_license,
        'shopDomain' => Ivole_Email::get_blogurl()
			);
      $api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/register-license';
      $data_string = json_encode($data);
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
      if( false === $result ) {
        WC_Admin_Settings::add_error( sprintf( __( 'License registration error: %s.', IVOLE_TEXT_DOMAIN ), curl_error( $ch ) ) );
  			return;
  		}
      $result = json_decode( $result );
      //error_log( print_r( $result, true ) );
      if( isset( $result->status ) ) {
        WC_Admin_Settings::add_message( sprintf( __( 'License has been successfully registered for \'%s\'.', IVOLE_TEXT_DOMAIN ), Ivole_Email::get_blogurl() ) );
        return;
      } else if( isset( $result->error ) ) {
        WC_Admin_Settings::add_error( sprintf( __( 'License registration error: %s.', IVOLE_TEXT_DOMAIN ), $result->error ) );
        return;
      } else {
        WC_Admin_Settings::add_error( __( 'License registration error #99', IVOLE_TEXT_DOMAIN ) );
        return;
      }
    }

	}

endif;

?>
