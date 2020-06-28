<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Email_Verify' ) ) :

  require_once('class-ivole-email.php');

	class Ivole_Email_Verify {
	  public function __construct() {
	  }

		public function is_verified() {
      $licenseKey = get_option( 'ivole_license_key', '' );
      $emailFrom = get_option( 'ivole_email_from', '' );
      if( filter_var( $emailFrom, FILTER_VALIDATE_EMAIL ) ) {
        $data = array(
  				'token' => '164592f60fbf658711d47b2f55a1bbba',
  				'licenseKey' => $licenseKey,
          'email' => $emailFrom
  			);
  			$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/is-email-verified';
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
    			return 0;
    		}
        $result = json_decode( $result );
        //error_log( print_r( $result, true ) );
        if( isset( $result->verified ) && 1 == $result->verified ) {
          return 1;
        } else {
          return 0;
        }
      } else {
        return 0;
      }
		}

    public function verify_email( $email ) {
      $licenseKey = get_option( 'ivole_license_key', '' );
      if( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$email = strtolower( $email );
        $data = array(
  				'token' => '164592f60fbf658711d47b2f55a1bbba',
  				'licenseKey' => $licenseKey,
          'shopDomain' => Ivole_Email::get_blogurl(),
          'email' => $email
  			);
  			$api_url = 'https://z4jhozi8lc.execute-api.us-east-1.amazonaws.com/v1/verify-email';
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
    			return array( 'res' => 2, 'message' => curl_error( $ch ) );
    		}
        $result = json_decode( $result );
        //error_log( print_r( $result, true ) );
        if( isset( $result->status ) && 'OK' === $result->status ) {
          update_option( 'ivole_email_from', $email );
          return array( 'res' => 1, 'message' => '' );
        } else if( isset( $result->error ) && 'Email has already been verified' === $result->error ) {
          return array( 'res' => 3, 'message' => $result->error );
        } else {
          return array( 'res' => 0, 'message' => '' );
        }
      } else {
        return array( 'res' => 99, 'message' => '' );
      }
    }
	}

endif;

?>
