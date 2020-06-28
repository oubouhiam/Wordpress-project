<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Verified_Reviews' ) ) :

  require_once('class-ivole-email.php');

	class Ivole_Verified_Reviews {
	  public function __construct() {
	  }

		public function check_status() {
      $data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array(
					'domain' => Ivole_Email::get_blogurl(),
					'name' => Ivole_Email::get_blogname()
				),
        'action' => 'status'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
      $data_string = json_encode($data);
      $ch = curl_init();
  		curl_setopt( $ch, CURLOPT_URL, $api_url );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
  			'Content-Type: application/json',
  			'Content-Length: ' . strlen( $data_string ) )
  		);
  		$result = curl_exec( $ch );
			//error_log( print_r( $result, true ) );
      if( false === $result ) {
  			return 1;
  		}
      $result = json_decode( $result );
      if( isset( $result->status ) && 'enabled' === $result->status ) {
        return 0;
      } else {
				update_option( 'ivole_reviews_verified', 'no' );
        return 1;
      }
		}

    public function enable( $reviewsUrl ) {
			if( strlen( $reviewsUrl ) === 0 ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error: \'Verified Reviews Page\' cannot be empty.', IVOLE_TEXT_DOMAIN ) );
				return 1;
			}
      $data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array(
					'domain' => Ivole_Email::get_blogurl(),
					'name' => Ivole_Email::get_blogname(),
				 	'reviewsUrl' => $reviewsUrl
				),
        'action' => 'enable'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
      $data_string = json_encode($data);
      $ch = curl_init();
  		curl_setopt( $ch, CURLOPT_URL, $api_url );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
  		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
  			'Content-Type: application/json',
  			'Content-Length: ' . strlen( $data_string ) )
  		);
  		$result = curl_exec( $ch );
      if( false === $result ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error #98. ' . curl_error( $ch ), IVOLE_TEXT_DOMAIN ) );
  			return 1;
  		}
      $result = json_decode( $result );
      //error_log( print_r( $result, true ) );
      if( isset( $result->status ) && 'enabled' === $result->status ) {
        WC_Admin_Settings::add_message( __( 'Trust badges have been successfully activated.', IVOLE_TEXT_DOMAIN ) );
        return 0;
      } elseif( isset( $result->error ) && 'Duplicate reviews url' === $result->error ) {
				WC_Admin_Settings::add_error( sprintf( __( 'Trust badges activation error: \'%s\' is already in use. Please enter a different page name.', IVOLE_TEXT_DOMAIN ), $reviewsUrl ) );
        return 1;
			} elseif( isset( $result->error ) && 'Wrong reviews url' === $result->error ) {
				WC_Admin_Settings::add_error( __( 'Trust badges activation error: page name contains unsupported symbols. Only latin characters (a-z), numbers (0-9), and . symbol are allowed.', IVOLE_TEXT_DOMAIN ) );
        return 1;
			}
			else {
        WC_Admin_Settings::add_error( __( 'Trust badges activation error #99.', IVOLE_TEXT_DOMAIN ) );
        return 1;
      }
    }

		public function disable() {
      $data = array(
				'token' => '164592f60fbf658711d47b2f55a1bbba',
				'shop' => array( 'domain' => Ivole_Email::get_blogurl(), 'name' => Ivole_Email::get_blogname() ),
        'action' => 'disable'
			);
			$api_url = 'https://api.cusrev.com/v1/production/shop-page';
      $data_string = json_encode($data);
      $ch = curl_init();
  		curl_setopt( $ch, CURLOPT_URL, $api_url );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
  		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
  			'Content-Type: application/json',
  			'Content-Length: ' . strlen( $data_string ) )
  		);
  		$result = curl_exec( $ch );
      if( false === $result ) {
				WC_Admin_Settings::add_error( __( 'Trust badges deactivation error #98. Please try again.', IVOLE_TEXT_DOMAIN ) );
  			return 1;
  		}
      $result = json_decode( $result );
      //error_log( print_r( $result, true ) );
      if( isset( $result->status ) && 'disabled' === $result->status ) {
        WC_Admin_Settings::add_message( __( 'Trust badges have been successfully deactivated.', IVOLE_TEXT_DOMAIN ) );
        return 0;
      } else {
        WC_Admin_Settings::add_error( __( 'Trust badges deactivation error #99.', IVOLE_TEXT_DOMAIN ) );
        return 1;
      }
    }

	}

endif;

?>
