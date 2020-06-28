<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_QTranslate' ) ) :

	class Ivole_QTranslate {
	  public function __construct() {
			add_action( 'qtranslate_init_language', array( $this, 'qwc_init_language' ) );
	  }

		public function qwc_init_language( $url_info )
		{
			if( $url_info['doing_front_end'] ) {
				return;
			} else {
				add_filter( 'qtranslate_load_admin_page_config', array( $this, 'translate_admin' ) );
			}
		}

		public function translate_admin( $page_configs ) {
			//tab = Review Reminder
			$page_config = array();
			$page_config['pages'] = array( 'admin.php' => 'page=ivole-reviews');

			$page_config['forms'] = array();

			$f = array();
			$f['form'] = array( 'id' => 'mainform' );

			$f['fields'] = array();
			$fields = &$f['fields']; // shorthand

			$fields[] = array( 'id' => 'ivole_email_subject' );
			$fields[] = array( 'id' => 'ivole_email_heading' );
			$fields[] = array( 'id' => 'ivole_email_body' );
			$fields[] = array( 'id' => 'ivole_form_header' );
			$fields[] = array( 'id' => 'ivole_form_body' );

			$page_config['forms'][] = $f;
			$page_configs[] = $page_config;

			//tab = Review for Discount
			$page_config = array();
			$page_config['pages'] = array( 'admin.php' => 'page=ivole-reviews-settings&tab=review_discount');

			$page_config['forms'] = array();

			$f = array();
			$f['form'] = array( 'id' => 'mainform' );

			$f['fields'] = array();
			$fields = &$f['fields']; // shorthand

			$fields[] = array( 'id' => 'ivole_email_subject_coupon' );
			$fields[] = array( 'id' => 'ivole_email_heading_coupon' );
			$fields[] = array( 'id' => 'ivole_email_body_coupon' );

			$page_config['forms'][] = $f;
			$page_configs[] = $page_config;

			return $page_configs;
		}
	}

endif;

?>
