<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CR_Product_Feed_Admin_Menu' ) ):

class CR_Product_Feed_Admin_Menu {

    /**
     * @var string URL to admin diagnostics page
     */
    protected $page_url;

    /**
     * @var string The slug identifying this menu
     */
    protected $menu_slug;

    /**
     * @var string The slug of the currently displayed tab
     */
    protected $current_tab = 'overview';

    public function __construct() {
        $this->menu_slug = 'ivole-reviews-product-feed';

        $this->page_url = add_query_arg( array(
            'page' => $this->menu_slug
        ), admin_url( 'admin.php' ) );

        if ( isset( $_GET['tab'] ) ) {
            $this->current_tab = $_GET['tab'];
        }

        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'admin_menu', array( $this, 'register_settings_menu' ), 11 );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_product_feed_css_js' ) );
    }

    public function register_settings_menu() {
        add_submenu_page(
            'ivole-reviews',
            __( 'Integration with Google Services', IVOLE_TEXT_DOMAIN ),
            __( 'Google', IVOLE_TEXT_DOMAIN ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'display_productfeed_admin_page' )
        );
    }

    public function display_productfeed_admin_page() {
        ?>
        <div class="wrap ivole-new-settings woocommerce">
            <h1 class="wp-heading-inline" style="margin-bottom:8px;"><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <hr class="wp-header-end">
        <?php
        $tabs = apply_filters( 'cr_productfeed_tabs', array() );

        if ( is_array( $tabs ) && sizeof( $tabs ) > 1 ) {
            echo '<ul class="subsubsub">';

            $array_keys = array_keys( $tabs );
            $last = end( $array_keys );

            foreach ( $tabs as $tab => $label ) {
                echo '<li><a href="' . $this->page_url . '&tab=' . $tab . '" class="' . ( $this->current_tab === $tab ? 'current' : '' ) . '">' . $label . '</a> ' . ( $last === $tab ? '' : '|' ) . ' </li>';
            }

            echo '</ul><br class="clear" />';
        }
        ?>
        <form action="" method="post" id="mainform" enctype="multipart/form-data">
            <?php
                WC_Admin_Settings::show_messages();

                do_action( 'cr_productfeed_display_' . $this->current_tab );
            ?>
            <p class="submit">
    			    <?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				        <button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
			        <?php endif; ?>
			        <?php wp_nonce_field( 'cr-productfeed' ); ?>
		        </p>
            </div>
        </form>
        <?php
        update_option( 'ivole_activation_notice', 0 );
    }

    public function save_settings() {
        if ( $this->is_this_page() && ! empty( $_POST ) ) {
            check_admin_referer( 'cr-productfeed' );

            do_action( 'cr_save_productfeed_' . $this->current_tab );

            WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
        }
    }

    public function is_this_page() {
        return ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug );
    }

    public function get_current_tab() {
        return $this->current_tab;
    }

    public function load_product_feed_css_js( $hook ) {
      //error_log( print_r( $hook, true ) );
      $reviews_screen_id = sanitize_title( __( 'Reviews', IVOLE_TEXT_DOMAIN ) );
      if( $reviews_screen_id . '_page_ivole-reviews-product-feed' === $hook ) {
        wp_enqueue_style( 'ivole_trustbadges_admin_css', plugins_url('css/admin.css', __FILE__) );
        wp_enqueue_style( 'ivole_select2_admin_css', plugins_url('css/select2.min.css', __FILE__) );
        wp_enqueue_script( 'ivole_select2_admin_js', plugins_url('js/select2.min.js', __FILE__) );
        wp_enqueue_script( 'ivole-admin-categories', plugins_url('js/admin-categories.js', __FILE__ ), array(), false, false );
      }
    }
}

endif;
