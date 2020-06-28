<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'Ivole_WPML' ) ) :

  class Ivole_WPML {

    public static function translate_admin( $fields ) {
      if ( has_action( 'wpml_register_single_string' ) ) {
        //tab = Review Reminder
        $fields_to_translate = array(
          'ivole_email_subject',
          'ivole_email_heading',
          'ivole_email_body',
          'ivole_form_header',
          'ivole_form_body',
          'ivole_email_from_name',
          'ivole_email_footer',
          'ivole_email_subject_coupon',
          'ivole_email_heading_coupon',
          'ivole_email_body_coupon',
        );
        foreach ( $fields_to_translate as $field_to_translate ) {
          if ( isset( $fields[$field_to_translate] ) ) {
            do_action( 'wpml_register_single_string', 'ivole', $field_to_translate, $fields[$field_to_translate] );
          }
        }
      }
    }
  }
  
endif;