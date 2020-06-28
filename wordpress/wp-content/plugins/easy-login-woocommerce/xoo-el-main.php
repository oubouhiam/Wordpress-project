<?php
/**
* Plugin Name: Login/Signup Popup ( Inline Form + Woocommerce )
* Plugin URI: http://xootix.com/easy-login-for-woocommerce
* Author: XootiX
* Version: 1.7
* Text Domain: easy-login-woocommerce
* Domain Path: /languages
* Author URI: http://xootix.com
* Description: Allow users to login/signup anywhere from the site with the simple pop up.
* Tags: woocommerce login, woocommerce signup, woocommerce mobile login, otp login, login popup 
*/


//Exit if accessed directly
if(!defined('ABSPATH')){
	return;
}

define("XOO_EL_PATH",plugin_dir_path(__FILE__)); // Plugin path
define("XOO_EL_URL",plugins_url('',__FILE__)); // plugin url
define("XOO_EL_PLUGIN_BASENAME",plugin_basename( __FILE__ ));
define("XOO_EL_VERSION","1.7"); //Plugin version



if ( ! class_exists( 'Xoo_El_Core' ) ) {
	require XOO_EL_PATH.'/includes/class-xoo-el-core.php';
}


/**
 *
 * @since    1.0.0
 */
function xoo_el(){
	
	do_action('xoo_el_before_plugin_activation');

	return Xoo_El_Core::get_instance();
	
}
add_action( 'plugins_loaded', 'xoo_el', 10 );



function xoo_el_update_message( $args, $response ){

	// If 2.0 update
	if ( version_compare( XOO_EL_VERSION, '2.0', '>=' ) ) return;


	?>

	<style type="text/css">
		.xoo-el-upc-info {
		    padding: 10px 0;
		    font-size: 14px;
		    line-height: 21px;
		    font-family: monospace;
		}
	</style>
	<div class="xoo-el-up-container">
		<div class="xoo-el-upc-info">
			2.0 is a major update.<br>
			If you see any issues with the plugin or is not working for you, please leave an offline message <a target="_blank" href="http://xootix.com/support/">here</a><br>
		</div>
	</div>
	<?php

}
add_action( 'in_plugin_update_message-easy-login-woocommerce/xoo-el-main.php', 'xoo_el_update_message', 10, 2 );