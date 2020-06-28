<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Premium_Settings' ) ):

class Ivole_Premium_Settings {

    /**
     * @var Ivole_Settings_Admin_Menu The instance of the settings admin menu
     */
    protected $settings_menu;

    /**
     * @var string The slug of this tab
     */
    protected $tab;

    /**
     * @var array The fields for this tab
     */
    protected $settings;

    public function __construct( $settings_menu ) {
        $this->settings_menu = $settings_menu;

        $this->tab = 'license-key';

        add_filter( 'ivole_settings_tabs', array( $this, 'register_tab' ) );
        add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
        add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );

        add_action( 'woocommerce_admin_field_ivole_upload_shop_logo', array( $this, 'show_upload_shop_logo' ) );

        add_action( 'wp_ajax_ivole_update_shop_logo', array( $this, 'update_shop_logo' ) );

        add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( '&#9733; License Key &#9733;', IVOLE_TEXT_DOMAIN );
        return $tabs;
    }

    public function display() {
        $this->init_settings();
        WC_Admin_Settings::output_fields( $this->settings );
    }

    public function save() {
        $this->init_settings();

        $field_id = 'ivole_license_key';
				if( !empty( $_POST ) && isset( $_POST[$field_id] ) ) {
					//error_log( print_r( $_POST[$field_id], true ) );
					$license = new Ivole_License();
					$license->register_license( $_POST[$field_id] );
				}

        WC_Admin_Settings::save_fields( $this->settings );
    }

    protected function init_settings() {
        $this->settings = array(
            array(
                'title' => __( 'Types of License Keys', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( '<p>Customer Reviews (CR) service works with two types of license keys: (1) professional and (2) free.</p><p>(1) You can unlock <b>all</b> features for managing customer reviews by purchasing a professional license key => <a href="https://www.cusrev.com/business/" target="_blank">Professional License Key</a><img src="' . untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/img/external-link.png' .'" class="cr-product-feed-categories-ext-icon"></p>' .
                '<p>(2) Basic features of CR service (e.g., social media integration, analytics, replies to reviews) are available for free but require a (free) license key. If you would like to request a free license key (no pro features), create an account here => <a href="https://www.cusrev.com/register.html" target="_blank">Free License Key</a><img src="' . untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/img/external-link.png' .'" class="cr-product-feed-categories-ext-icon"></p>', IVOLE_TEXT_DOMAIN ) .
                '<p>' . sprintf( __( 'An overview of features available in the Free and Pro versions of Customer Reviews: %s', IVOLE_TEXT_DOMAIN ), '<a href="https://www.cusrev.com/business/pricing.html" target="_blank">Free vs Pro</a><img src="' . untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/img/external-link.png' .'" class="cr-product-feed-categories-ext-icon"></p>'),
                'id'    => 'ivole_options_premium'
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ivole_options_premium'
            ),
            array(
                'title' => __( 'License Key', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( 'Please enter your license key (free or pro) in the field below. The plugin will automatically determine type of your license key.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_options_license'
            ),
            array(
                'title'    => __( 'License Status', IVOLE_TEXT_DOMAIN ),
                'type'     => 'license_status',
                'desc'     => __( 'Information about license status.', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_license_status',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'License Key', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'Enter your license key here.', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_license_key',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ivole_options_license'
            ),
            array(
                'title' => __( 'Shop Logo', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( 'Upload a logo of your shop. The logo will be added to email templates, review forms, and (if enabled) pages with verified reviews generated by CR. This feature requires the professional license.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_options_logo'
            ),
            array(
                'title'    => __( 'Upload Logo', IVOLE_TEXT_DOMAIN ),
                'type'     => 'ivole_upload_shop_logo',
                'desc'     => __( 'Upload your shop logo', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_upload_shop_logo',
                'css'      => 'min-width:250px;',
                'desc_tip' => true
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ivole_options_logo'
            )
        );
    }

    public function is_this_tab() {
        return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
    }

    /**
	 * Function to save logo
	 */
	public function update_shop_logo() {
		if ( current_user_can( 'manage_options' ) ) {
			$logo_url = sanitize_text_field( $_POST['logo_url'] );
			$logo_url = ( $logo_url ) ? $logo_url : NULL;
      update_option( 'ivole_shop_logo', $logo_url );
      $logo_message = ( $logo_url ) ? __( 'Shop logo updated', IVOLE_TEXT_DOMAIN ) : __( 'Shop logo removed', IVOLE_TEXT_DOMAIN );
      wp_send_json( array(
				'logo_result' => $logo_url,
				'error' => false,
				'logo_message' => $logo_message,
			) );
		} else {
			wp_send_json( array(
        'logo_result' => '',
				'error' => true,
				'logo_message' => __( 'Unauthorized', IVOLE_TEXT_DOMAIN ),
			) );
		}
	}

    /**
	 * Custom field type for shop logo upload
	 */
	public function show_upload_shop_logo( $value ) {
		$tmp = Ivole_Admin::ivole_get_field_description( $value );
		$tooltip_html = $tmp['tooltip_html'];
		$description = $tmp['description'];
    ?>
    <tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<input
					name="ivole_shop_domain"
					id="ivole_shop_domain"
					type="hidden"
					value="<?php echo Ivole_Email::get_blogurl(); ?>" />
				<input
					name="ivole_shop_name"
					id="ivole_shop_name"
					type="hidden"
					value="<?php echo Ivole_Email::get_blogname(); ?>" />
				<input
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="ivole_upload_shop_logo"
					type="file"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
					placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
					disabled />
				<button
					name="ivole_upload_shop_logo_submit"
					id="ivole_upload_shop_logo_submit"
					class="button-primary ivole-upload-shop-logo-submit"
					type="button"
					value="<?php _e( 'Upload', IVOLE_TEXT_DOMAIN ); ?>"
					disabled>
					<?php _e( 'Upload', IVOLE_TEXT_DOMAIN ); ?>
				</button>
				<p id="ivole_upload_shop_logo_result">&nbsp;</p>
				<p><img id="ivole_shop_logo" style="width:200px;" src="<?php echo ( get_option( 'ivole_shop_logo', '') ) ? add_query_arg( 't', time(), get_option( 'ivole_shop_logo', '' ) ) : ''; ?>"></p>
				<?php
        $remove_button_style = ( get_option( 'ivole_shop_logo', '' ) ) ? 'visibility:visible' : 'visibility:hidden';
        $disabled_button = ( get_option( 'ivole_shop_logo', '' ) ) ? '' : 'disabled:"disabled"';
        ?>
				<button
					name="ivole_remove_shop_logo_submit"
					id="ivole_remove_shop_logo_submit"
					class="button-secondary ivole-remove-shop-logo-submit"
					type="button"
					style="<?php echo $remove_button_style; ?>"
          value="<?php _e( 'Remove logo', IVOLE_TEXT_DOMAIN ); ?>"
          <?php echo $disabled_button; ?> >
          <?php _e( 'Remove logo', IVOLE_TEXT_DOMAIN ); ?>
        </button>
			</td>
		</tr>
		<?php
    }

    public function output_page_javascript() {
        if ( $this->is_this_tab() ) {
        ?>
            <script type="text/javascript">
                jQuery(function($) {
                    if ( jQuery('#ivole_license_status').length > 0 ) {
                        var data = {
                            'action': 'ivole_check_license_ajax'
                        };

                        jQuery('#ivole_license_status').val( 'Checking...' );

                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#ivole_license_status').val( response.message );

                            if ('<?php echo __( 'Active: Professional Version', IVOLE_TEXT_DOMAIN ); ?>' === response.message) {
                                jQuery( '.ivole-upload-shop-logo-submit' ).prop( 'disabled', false );
                                jQuery( '#ivole_upload_shop_logo' ).prop( 'disabled', false );
                            }
                        });
                    }
                });
            </script>
        <?php
        }
    }
}

endif;
