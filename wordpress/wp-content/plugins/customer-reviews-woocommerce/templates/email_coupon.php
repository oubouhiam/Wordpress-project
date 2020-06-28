<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//qTranslate integration
$ivole_language = get_option( 'ivole_language' );
if( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) && $ivole_language === 'QQ' ) {
	echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( wpautop( wp_kses_post( get_option( 'ivole_email_body_coupon', $def_body ) ) ) );
} else {
	//WPML integration
	if ( has_filter( 'wpml_translate_single_string' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE && $ivole_language === 'WPML' ) {
		$wpml_current_language = strtolower( $lang );
		echo wpautop( wp_kses_post( apply_filters( 'wpml_translate_single_string', get_option( 'ivole_email_body_coupon', $def_body ), 'ivole', 'ivole_email_body_coupon', $wpml_current_language ) ) );
	} else {
		echo wpautop( wp_kses_post( get_option( 'ivole_email_body_coupon', $def_body ) ) );
	}
}
