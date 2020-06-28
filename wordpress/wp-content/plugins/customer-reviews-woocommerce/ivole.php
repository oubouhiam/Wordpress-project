<?php
/*
Plugin Name: Customer Reviews for WooCommerce
Description: Customer Reviews for WooCommerce plugin helps you get more customer reviews for your shop by sending automated reminders and coupons.
Plugin URI: https://wordpress.org/plugins/customer-reviews-woocommerce/
Version: 3.111
Author: Customer Reviews
Author URI: https://www.cusrev.com/business/
Text Domain: customer-reviews-woocommerce
WC requires at least: 3.6
WC tested up to: 4.1
License: GPLv3

Customer Reviews for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

Customer Reviews for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with WooCommerce Reviews. If not, see https://www.gnu.org/licenses/gpl.html.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! defined( 'IVOLE_TEXT_DOMAIN' ) ) {
	define( 'IVOLE_TEXT_DOMAIN', 'customer-reviews-woocommerce' );
}

if ( ! defined( 'IVOLE_CONTENT_DIR' ) ) {
	if ( defined( 'UPLOADS' ) ) {
		$uploads = untrailingslashit( UPLOADS );
		define( 'IVOLE_CONTENT_DIR', $uploads . '/cr' );
	} else {
		define( 'IVOLE_CONTENT_DIR', WP_CONTENT_DIR . '/uploads/cr' );
	}
}

require_once( 'class-ivole.php' );
require_once( 'class-ivole-qtranslate.php' );
require_once( 'class-ivole-all-reviews.php' );
require_once( 'class-ivole-wpml.php' );
require_once( 'class-ivole-trust-badge.php' );

/**
 * Check if WooCommerce is active
**/
$ivole_activated_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ||
 	( is_multisite() && isset( $ivole_activated_plugins['woocommerce/woocommerce.php'] ) ) ) {
	add_action('init', 'ivole_init', 9);

	function ivole_init() {
		load_plugin_textdomain( IVOLE_TEXT_DOMAIN, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );

		if ( "" == ivole_get_site_url() ) {
			ivole_set_duplicate_site_url_lock();
		}

		$ivole = new Ivole();
	}

	add_action('plugins_loaded', 'qtranslate_init', 1);

	function qtranslate_init() {
		$ivole_qtranslate = new Ivole_QTranslate();
	}
}

add_shortcode( 'ivole_unsubscribe', 'ivole_email_unsubscribe_shortcode' );
function ivole_email_unsubscribe_shortcode() {
	$email = '';
	if( isset( $_GET['ivole_email_unsubscribe'] ) ) {
		$email = strval( $_GET['ivole_email_unsubscribe'] );
	};
	if( isset( $_POST['ivole_submit'] ) && isset( $_POST['ivole_email'] ) ) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$opt_out_emails = get_option( 'ivole_unsubscribed_emails', array() );
			if( !in_array( $email, $opt_out_emails ) ) {
				$opt_out_emails[] = $email;
				update_option( 'ivole_unsubscribed_emails', $opt_out_emails );
				echo '<p>' . __('Success: you have unsubscribed from emails related to reviews!', IVOLE_TEXT_DOMAIN) . '</p>';
			} else {
				echo '<p>' . __('Success: you have unsubscribed from emails related to reviews!', IVOLE_TEXT_DOMAIN) . '</p>';
			}
		} else {
			echo '<p>' . __('Error: please provide a valid email address!', IVOLE_TEXT_DOMAIN) . '</p>';
		}
		echo '<a href="' . get_home_url() . '">' . __( 'Go to home page', IVOLE_TEXT_DOMAIN ) . '</a>';
		return;
	}
	?>
	<div class="ivole-unsubscribe-form">
		<form action="" method="post">
			<input type="hidden" name="ivole_action" value="ivole_unsubscribe" />
			<p>
				<label for="ivole_email"><?php _e('Email Address:', IVOLE_TEXT_DOMAIN); ?></label>
				<input type="text" id="ivole_email" name="ivole_email" value="<?php echo esc_attr($email); ?>" size="25" />
			</p>
			<p>
				<input type="submit" name="ivole_submit" value="<?php _e('Unsubscribe', IVOLE_TEXT_DOMAIN); ?>" />
			</p>
		</form>
	</div>
	<?php
}

add_shortcode( 'cusrev_reviews', 'ivole_reviews_shortcode' );

function ivole_reviews_shortcode( $atts, $content )
{
	$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
	if( $shortcode_enabled === 'no' ) {
		return;
	} else {
		extract( shortcode_atts( array( 'comment_file' => '/comments.php' ), $atts ) );
		$content = ivole_return_comment_form( $comment_file );
	}
	return $content;
}

add_shortcode( 'cusrev_all_reviews', 'ivole_all_reviews_shortcode' );

function ivole_all_reviews_shortcode( $atts )
{
	$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
	if( $shortcode_enabled === 'no' ) {
		return;
	} else {
  	$ivole_all_reviews = new Ivole_All_Reviews( $atts );
		return $ivole_all_reviews->show_all_reviews();
	}
}

// Register Trust Badge shortcode
add_shortcode( 'cusrev_trustbadge', 'ivole_trustbadge_shortcode' );
function ivole_trustbadge_shortcode( $atts )
{
	$shortcode_enabled = get_option( 'ivole_reviews_verified', 'no' );
	if( $shortcode_enabled === 'no' ) {
		return;
	} else {
  	$ivole_trustbadge = new Ivole_Trust_Badge( $atts );
		return $ivole_trustbadge->show_trust_badge();
	}
}

function ivole_return_comment_form( $comment_file )
{
	ob_start();
	comments_template( $comment_file );
	$form = ob_get_contents();
	ob_end_clean();
	return $form;
}

register_activation_hook( __FILE__, 'ivole_activation_hook' );
function ivole_activation_hook() {
	update_option( 'ivole_activation_notice', 1 );
}

//---------Admin notice for staging----------
function ivole_general_admin_notice()
{
	if ( ivole_is_duplicate_site() && current_user_can( 'manage_options' ) ) {

		if ( ! empty( $_REQUEST['_wcsnonce'] ) && wp_verify_nonce( $_REQUEST['_wcsnonce'], 'ivole_duplicate_site' ) && isset( $_GET['ivole_duplicate_site'] ) ) {

			if ( 'update' === $_GET['ivole_duplicate_site'] ) {
				ivole_set_duplicate_site_url_lock();
			} elseif ( 'ignore' === $_GET['ivole_duplicate_site'] ) {
				update_option( 'ivole_ignore_duplicate_siteurl_notice', ivole_get_current_sites_duplicate_lock() );
				update_option( 'ivole_enable', 'no' );
			}
			wp_safe_redirect( remove_query_arg( array( 'ivole_duplicate_site', '_wcsnonce' ) ) );

		} elseif ( ivole_get_current_sites_duplicate_lock() !== get_option( 'ivole_ignore_duplicate_siteurl_notice' ) ) { ?>

			<div id="message" class="error">
				<p><?php
					printf( esc_html__( 'It looks like this site has moved or is a duplicate site. %1$sCustomer Reviews for WooCommerce%2$s has disabled sending automatic review reminder emails on this site to prevent duplicate reminders from a staging or test environment.', IVOLE_TEXT_DOMAIN ), '<strong>', '</strong>' ); ?></p>
				<div style="margin: 5px 0;">
					<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ivole_duplicate_site', 'ignore' ), 'ivole_duplicate_site', '_wcsnonce' ) ); ?>"><?php esc_html_e( 'Hide this message (but don\'t enable automatic review reminders)', IVOLE_TEXT_DOMAIN ); ?></a>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ivole_duplicate_site', 'update' ), 'ivole_duplicate_site', '_wcsnonce' ) ); ?>"><?php esc_html_e( 'Enable automatic review reminders', IVOLE_TEXT_DOMAIN ); ?></a>
				</div>
			</div>
		<?php
		}
	}
}

add_action('admin_notices', 'ivole_general_admin_notice');

/**
 * Returns CR record of the site URL for this site
 */
function ivole_get_site_url( $blog_id = null, $path = '', $scheme = null ) {
	if ( empty( $blog_id ) || ! is_multisite() ) {
		$url = get_option( 'ivole_siteurl' );
	} else {
		switch_to_blog( $blog_id );
		$url = get_option( 'ivole_siteurl' );
		restore_current_blog();
	}

	// Remove the prefix used to prevent the site URL being updated on WP Engine
	$url = str_replace( '_[ivole_siteurl]_', '', $url );

	$url = set_url_scheme( $url, $scheme );

	if ( ! empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false ) {
		$url .= '/' . ltrim( $path, '/' );
	}

	return $url;
}

/**
 * Checks if the WordPress site URL is the same as the URL for the site CR normally
 * runs on. Useful for checking if automatic reminders should be disabled.
 */
function ivole_is_duplicate_site() {

	if ( defined( 'WP_SITEURL' ) ) {
		$site_url = WP_SITEURL;
	} else {
		$site_url = get_site_url();
	}

	$wp_site_url_parts  = wp_parse_url( $site_url );
	$ivole_site_url_parts = wp_parse_url( ivole_get_site_url() );

	if ( ! isset( $wp_site_url_parts['path'] ) && ! isset( $ivole_site_url_parts['path'] ) ) {
		$paths_match = true;
	} elseif ( isset( $wp_site_url_parts['path'] ) && isset( $ivole_site_url_parts['path'] ) && $wp_site_url_parts['path'] == $ivole_site_url_parts['path'] ) {
		$paths_match = true;
	} else {
		$paths_match = false;
	}

	if ( isset( $wp_site_url_parts['host'] ) && isset( $ivole_site_url_parts['host'] ) && $wp_site_url_parts['host'] == $ivole_site_url_parts['host'] ) {
		$hosts_match = true;
	} else {
		$hosts_match = false;
	}

	// Check the host and path, do not check the protocol/scheme to avoid issues with WP Engine and other occasions where the WP_SITEURL constant may be set, but being overridden (e.g. by FORCE_SSL_ADMIN)
	if ( $paths_match && $hosts_match ) {
		$is_duplicate = false;
	} else {
		$is_duplicate = true;
	}

	return $is_duplicate;
}

/**
 * Creates a URL based on the current site's URL that can be used to prevent duplicate payments from staging sites.
 *
 * The URL can not simply be the site URL, e.g. http://example.com, because WP Engine replaces all instances of the site URL in the database
 * when creating a staging site. As a result, we obfuscate the URL by inserting '_[ivole_siteurl]_' into the middle of it.
 *
 * Why not just use a hash? Because keeping the URL in the value allows for viewing and editing the URL directly in the database.
 */
function ivole_get_current_sites_duplicate_lock() {

	if ( defined( 'WP_SITEURL' ) ) {
		$site_url = WP_SITEURL;
	} else {
		$site_url = get_site_url();
	}

	return substr_replace( $site_url, '_[ivole_siteurl]_', strlen( $site_url ) / 2, 0 );
}

function ivole_set_duplicate_site_url_lock() {
	update_option( 'ivole_siteurl', ivole_get_current_sites_duplicate_lock() );
}

function ivole_generate_google_shopping_prod_feed() {
	$feed = new CR_Google_Shopping_Prod_Feed();
	$feed->generate();
}

function ivole_generate_google_shopping_reviews_feed() {
	$field_map = get_option( 'ivole_google_field_map', array(
		'gtin'  => '',
		'mpn'   => '',
		'sku'   => '',
		'brand' => ''
	) );

	$feed = new Ivole_Google_Shopping_Feed( $field_map );
	$feed->generate();
}

add_action( 'ivole_generate_prod_feed', 'ivole_generate_google_shopping_prod_feed' );
add_action( 'ivole_generate_feed', 'ivole_generate_google_shopping_reviews_feed' );

function ivole_add_block_editor_settings( $settings, $post ) {
	$settings['cusrev'] = array(
		'reviews_verified' => ( get_option( 'ivole_reviews_verified', 'no' ) !== 'no' ),
		'reviews_shortcodes' => ( get_option( 'ivole_reviews_shortcode', 'no' ) !== 'no' )
	);

	return $settings;
}

add_filter( 'block_editor_settings', 'ivole_add_block_editor_settings', 10, 2 );

?>
