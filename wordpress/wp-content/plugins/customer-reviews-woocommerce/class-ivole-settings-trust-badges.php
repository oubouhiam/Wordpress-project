<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Trust_Badges' ) ):

require_once('class-ivole-trust-badge.php');

class Ivole_Trust_Badges {

    /**
     * @var Ivole_Trust_Badges The instance of the trust badges admin menu
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
    protected $language;

    public function __construct( $settings_menu ) {
        $this->settings_menu = $settings_menu;
        $this->tab = 'trust_badges';
        $this->language = Ivole_Trust_Badge::get_badge_language();

        add_action( 'woocommerce_admin_field_trust_badge', array( $this, 'show_trustbadge' ) );
        add_action( 'woocommerce_admin_field_verified_badge', array( $this, 'show_verified_badge_checkbox' ) );
        add_action( 'woocommerce_admin_field_verified_page', array( $this, 'show_verified_page' ) );
        add_filter( 'ivole_settings_tabs', array( $this, 'register_tab' ) );
        add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
        add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_trustbadges_css' ) );
        add_action( 'admin_footer', array( $this, 'output_page_javascript' ) );
        add_action( 'wp_ajax_ivole_check_verified_reviews_ajax', array( $this, 'check_verified_reviews_ajax' ) );
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_reviews_verified', array( $this, 'save_verified_badge_checkbox' ), 10, 3 );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( 'Trust Badges', IVOLE_TEXT_DOMAIN );
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
					$license = new Ivole_License();
					$license->register_license( $_POST[$field_id] );
				}

        WC_Admin_Settings::save_fields( $this->settings );
    }

    protected function init_settings() {
        $this->settings = array(
            array(
                'title' => __( 'Trust Badges', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( '<p>Increase your store\'s conversion rate by placing a "trust badge" on the home, checkout or any other page(s). Let customers feel more confident about shopping on your site by featuring a trust badge that shows a summary of verified customer reviews. Trust badges can be enabled using shortcodes or blocks in the page editor (blocks require WordPress 5.0 or newer).</p><p>Reviews are considered to be verified when they are collected via an independent third-party website (www.cusrev.com) integrated with this plugin. Reviews submitted directly on your site cannot be considered as verified. Each trust badge contains a nofollow link to a dedicated page at <b>www.cusrev.com</b> with all verified reviews for your store. You can configure URL of the page with verified reviews for your store below.</p>', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_options'
            ),
            array(
                'title'   => __( 'Trust Badges', IVOLE_TEXT_DOMAIN ),
                'desc'    => sprintf( __( 'Enable this option to display trust badges and additional %1s icons for individual reviews on product pages in your store. Each %2s icon will contain a nofollow link to a verified copy of the review on <strong>www.cusrev.com</strong>.', IVOLE_TEXT_DOMAIN ),
                  '<img src="' . untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/img/shield-20.png" style="width:17px;">', '<img src="' . untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/img/shield-20.png" style="width:17px;">' ),
                'id'      => 'ivole_reviews_verified',
                'default' => 'no',
                'type'    => 'verified_badge'
            ),
            array(
                'title'    => __( 'Verified Reviews Page', IVOLE_TEXT_DOMAIN ),
                'desc'     => __( 'Specify name of the page with verified reviews. This will be a base URL for reviews related to your shop. You can use alphanumeric symbols and \'.\' in the name of the page.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_reviews_verified_page',
                'default'  => Ivole_Email::get_blogdomain(),
                'type'     => 'verified_page',
                'css'      => 'width:250px;vertical-align:middle;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Small Light Badge', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the small light trust badge.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_sl',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Small Light Badge (with Store Rating)', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the small light trust badge with store rating.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_slp',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Small Dark Badge', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the small dark trust badge.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_sd',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Small Dark Badge (with Store Rating)', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the small dark trust badge with store rating.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_sdp',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Wide Light Badge', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the wide light trust badge. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_wl',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Wide Light Badge (with Store Rating)', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the wide light trust badge with store rating. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_wlp',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Wide Dark Badge', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the wide dark trust badge. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_wd',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Wide Dark Badge (with Store Rating)', IVOLE_TEXT_DOMAIN ),
                'type'     => 'trust_badge',
                'desc'     => __( 'Shortcode and preview of the wide dark trust badge with store rating. The wide badge has a version for small screens that will be automatically shown when a website is viewed from phones.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_trust_badge_wdp',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ivole_options'
            )
        );
    }

    public function is_this_tab() {
        return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
    }

      /**
     * Custom field type for trust badges
     */
    public function show_trustbadge( $value ) {
      $tmp = Ivole_Admin::ivole_get_field_description( $value );
  		$tooltip_html = $tmp['tooltip_html'];
  		$description = $tmp['description'];
      $shortcode = '';
      $suffix = '';
      $l_suffix = '';
      if( 'en' !== $this->language ) {
        $l_suffix = '-' . $this->language;
      }

      switch( $value['id']  ) {
        case 'ivole_trust_badge_sl':
          $shortcode = '[cusrev_trustbadge type="SL" border="yes" color="#FFFFFF"]';
          $suffix = 'sl';
          break;
        case 'ivole_trust_badge_slp':
          $shortcode = '[cusrev_trustbadge type="SLP" border="yes" color="#FFFFFF"]';
          $suffix = 'slp';
          break;
        case 'ivole_trust_badge_sd':
          $shortcode = '[cusrev_trustbadge type="SD" border="yes" color="#3D3D3D"]';
          $suffix = 'sd';
          break;
        case 'ivole_trust_badge_sdp':
          $shortcode = '[cusrev_trustbadge type="SDP" border="yes" color="#3D3D3D"]';
          $suffix = 'sdp';
          break;
        case 'ivole_trust_badge_wl':
          $shortcode = '[cusrev_trustbadge type="WL" color="#FFFFFF"]';
          $suffix = 'wl';
          break;
        case 'ivole_trust_badge_wlp':
          $shortcode = '[cusrev_trustbadge type="WLP" color="#FFFFFF"]';
          $suffix = 'wlp';
          break;
        case 'ivole_trust_badge_wd':
          $shortcode = '[cusrev_trustbadge type="WD" color="#003640"]';
          $suffix = 'wd';
          break;
        case 'ivole_trust_badge_wdp':
          $shortcode = '[cusrev_trustbadge type="WDP" color="#003640"]';
          $suffix = 'wdp';
          break;
        default:
          $shortcode = '';
          $suffix = '';
          break;
      }
      ?>
      <tr valign="top">
  			<th scope="row" class="titledesc">
  				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
  				<?php echo $tooltip_html; ?>
  			</th>
  			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
          <p>Use <b><?php echo $shortcode; ?></b> shortcode to display this badge on your site. If the shortcode includes <b>border</b> argument, you can set it to <b>yes</b> or <b>no</b> to display or hide border. If the shortcode includes <b>color</b> argument, you can set it to a custom <a href="https://www.google.com/search?q=color+picker" target="_blank">color</a> (in HEX format).</p>
          <?php
          if( 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ) :
          ?>
          <p><a href="https://www.cusrev.com/reviews/<?php echo get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ); ?>" rel="nofollow" target="_blank" style="display:inline-block;"><img id="ivole_trustbadge_admin" class="ivole-trustbadge-<?php echo $suffix; ?>" src="<?php echo add_query_arg( 't', time(), 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $suffix . $l_suffix . '.png' ); ?>"></a></p>
          <?php
          else :
            echo '<p style="color:blue;">Preview of trust badges is turned off. Please enable \'Trust Badges\' checkbox and save changes to view trust badges.</p>';
          endif;
          ?>
  			</td>
  		</tr>
  		<?php
    }

    public function load_trustbadges_css( $hook ) {
      $reviews_screen_id = sanitize_title( __( 'Reviews', IVOLE_TEXT_DOMAIN ) );
      if( $reviews_screen_id . '_page_ivole-reviews-settings' === $hook ) {
        wp_enqueue_style( 'ivole_trustbadges_admin_css', plugins_url('css/admin.css', __FILE__) );
      }
    }

      /**
     * Custom field type for verified_badge checkbox
     */
    public function show_verified_badge_checkbox( $value ) {
      $tmp = Ivole_Admin::ivole_get_field_description( $value );
      $description = $tmp['description'];
      $option_value = get_option( $value['id'], $value['default'] );
      ?>
        <tr valign="top">
        <th scope="row" class="titledesc">
          <?php echo esc_html( $value['title'] ); ?>
        </th>
        <td class="forminp forminp-checkbox">
          <fieldset>
            <legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
            <label for="<?php echo $value['id'] ?>">
              <input
                name="<?php echo esc_attr( $value['id'] ); ?>"
                id="<?php echo esc_attr( $value['id'] ); ?>"
                type="checkbox"
                class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
                value="1"
                              disabled="disabled" />
                          <?php echo $description ?>
            </label>
            <p id="ivole_verified_badge_status" style="font-style:italic;visibility:hidden;"></p>
          </fieldset>
        </td>
      </tr>
      <?php
      }

      /**
     * Custom field type for license status
     */
    public function show_verified_page( $value ) {
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
          https://www.cusrev.com/reviews/
          <input
            name="<?php echo esc_attr( $value['id'] ); ?>"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            type="text"
            style="<?php echo esc_attr( $value['css'] ); ?>"
            class="<?php echo esc_attr( $value['class'] ); ?>"
            value="<?php echo get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ); ?>"
                      disabled />
                  <?php echo $description; ?>
        </td>
      </tr>
      <?php
    }

      /**
     * Custom field type for verified_badge checkbox save
     */
    public function save_verified_badge_checkbox( $value, $option, $raw_value ) {
      $value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';

      $verified_reviews = new Ivole_Verified_Reviews();
      if( 'yes' === $value ) {
        if( 0 != $verified_reviews->enable( $_POST['ivole_reviews_verified_page'] ) ) {
          // if activation failed, disable the option
          $value = 'no';
        }
      } else {
        $verified_reviews->disable();
      }

      return $value;
    }

      /**
     * Function to check if verified reviews are enabled
     */
    public function check_verified_reviews_ajax() {
      $vrevs = new Ivole_Verified_Reviews();
      $rval = $vrevs->check_status();

      if ( 0 === $rval ) {
        wp_send_json( array( 'status' => 0 ) );
      } else {
        wp_send_json( array( 'status' => 1 ) );
      }
    }

    public function output_page_javascript() {
        if ( $this->is_this_tab() ) {
        ?>
            <script type="text/javascript">
                jQuery(function($) {
                  // Load of Review Extensions page and check if verified reviews are enabled
				              if ( jQuery('#ivole_reviews_verified').length > 0 ) {
                        var data = {
                            'action': 'ivole_check_verified_reviews_ajax'
                        };
                        jQuery('#ivole_verified_badge_status').text('Checking settings...');
                        jQuery('#ivole_verified_badge_status').css('visibility', 'visible');
                        jQuery.post(ajaxurl, data, function(response) {
                            jQuery('#ivole_reviews_verified').prop( 'checked', <?php echo 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ? 'true' : 'false'; ?> );
                            jQuery('#ivole_verified_badge_status').css( 'visibility', 'hidden' );
                            jQuery('#ivole_reviews_verified').prop( 'disabled', false );
                            jQuery('#ivole_reviews_verified_page').prop( 'disabled', <?php echo 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ? 'false' : 'true'; ?> );
                        });

                        jQuery('#ivole_reviews_verified').change(function(){
                            if( this.checked ) {
                                jQuery('#ivole_reviews_verified_page').prop( 'disabled', false );
                            } else {
                                jQuery('#ivole_reviews_verified_page').prop( 'disabled', true );
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
