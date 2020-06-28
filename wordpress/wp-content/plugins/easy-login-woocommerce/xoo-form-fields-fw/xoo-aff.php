<?php

if( !defined( 'XOO_AFF_DIR' ) ){
	define( 'XOO_AFF_DIR', dirname(__FILE__) );
}

if( !defined( 'XOO_AFF_URL' ) ){
	define( 'XOO_AFF_URL', plugins_url( '', __FILE__  ) );
}

if( !defined( 'XOO_AFF_VERSION' ) ){
	define( 'XOO_AFF_VERSION', '1.1' );
}

//Begin
function xoo_aff_fire( $plugin_slug ){
	
	if( !$plugin_slug ) return;
	include_once XOO_AFF_DIR.'/includes/class-xoo-aff.php';
	return new Xoo_Aff( $plugin_slug );

	do_action( 'xoo_aff_loaded' );
	
}
?>