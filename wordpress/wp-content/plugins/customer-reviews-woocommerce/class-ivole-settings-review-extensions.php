<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Review_Extensions_Settings' ) ):

class Ivole_Review_Extensions_Settings {

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

        $this->tab = 'review_extensions';

        add_filter( 'ivole_settings_tabs', array( $this, 'register_tab' ) );
        add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
        add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( 'Review Extensions', IVOLE_TEXT_DOMAIN );
        return $tabs;
    }

    public function display() {
        $this->init_settings();

        WC_Admin_Settings::output_fields( $this->settings );
    }

    public function save() {
        $this->init_settings();

        // make sure that there the maximum number of attached images is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_quantity'] ) ) {
					if( $_POST['ivole_attach_image_quantity'] <= 0 ) {
						$_POST['ivole_attach_image_quantity'] = 1;
					}
				}
				// make sure that there the maximum size of attached image is larger than zero
				if( !empty( $_POST ) && isset( $_POST['ivole_attach_image_size'] ) ) {
					if( $_POST['ivole_attach_image_size'] <= 0 ) {
						$_POST['ivole_attach_image_size'] = 1;
					}
				}

        WC_Admin_Settings::save_fields( $this->settings );
    }

    protected function init_settings() {
        $this->settings = array(
            array(
                'title' => __( 'Extensions for Customer Reviews', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( 'The plugin is based on the standard WooCommerce reviews functionality. Here, you can configure various extensions for standard WooCommerce reviews.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_options'
            ),
            array(
                'title'   => __( 'Attach Images', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable attachment of images to reviews left on WooCommerce product pages. If you would like to enable attachment of images on aggregated review forms, this can be done <a href="' . admin_url( 'admin.php?page=ivole-reviews-settings&tab=review_reminder' ) . '">here</a>.', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_attach_image',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'title'    => __( 'Quantity of Images', IVOLE_TEXT_DOMAIN ),
                'desc'     => __( 'Specify the maximum number of images that can be uploaded for a single review. This setting applies only to reviews submitted on single product pages.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_attach_image_quantity',
                'default'  => 3,
                'type'     => 'number',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Maximum Size of Image', IVOLE_TEXT_DOMAIN ),
                'desc'     => __( 'Specify the maximum size (in MB) of an image that can be uploaded with a review. This setting applies only to reviews submitted on single product pages.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_attach_image_size',
                'default'  => 5,
                'type'     => 'number',
                'desc_tip' => true
            ),

            //------------------------------------------
            array(
                'title'         => __( 'Disable Lightbox', IVOLE_TEXT_DOMAIN ),
                'desc'          => __( 'Disable lightboxes for images attached to reviews (not recommended).
Use this option only if your theme generates lightboxes for any picture on the website
and this leads to two lightboxes shown after clicking on an image attached to a review.', IVOLE_TEXT_DOMAIN ),
                'id'            => 'ivole_disable_lightbox',
                'default'       => 'no',
                'type'          => 'checkbox'
            ),
            //------------------------------------------

            array(
                'title'   => __( 'reCAPTCHA V2 for Reviews', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable reCAPTCHA to eliminate fake reviews. You must enter Site Key and Secret Key in the fields below if you want to use reCAPTCHA. You will receive Site Key and Secret Key after registration at reCAPTCHA website.', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_enable_captcha',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'title'    => __( 'reCAPTCHA V2 Site Key', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Site Key that you will receive after registration at reCAPTCHA website.', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_captcha_site_key',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'reCAPTCHA V2 Secret Key', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'If you want to use reCAPTCHA V2, insert here Secret Key that you will receive after registration at reCAPTCHA website.', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_captcha_secret_key',
                'css'      => 'min-width:400px;',
                'desc_tip' => true
            ),
            array(
                'title'   => __( 'Reviews Shortcodes', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable shortcodes and Gutenberg blocks<br><br>- Use <strong>[cusrev_reviews]</strong> shortcode to display reviews at different locations on product pages. ' .
                    'You can use this shortcode as <b>[cusrev_reviews comment_file=”/comments.php”]</b> or simply as <b>[cusrev_reviews]</b>. ' .
                    'Here, \'comment_file\' is an optional argument. If you have a custom comment file, you should specify it here. ' .
                    'This shortcode works ONLY on WooCommerce single product pages.<br><br>' .
                    '- Use <strong>[cusrev_all_reviews]</strong> shortcode to display a list of all product reviews on any page or post. ' .
                    'This shortcode supports arguments: <b>[cusrev_all_reviews sort="DESC" per_page="10" number="-1" show_summary_bar="true" show_pictures="false" ' .
                    'show_products="true" categories="" products="" shop_reviews="true" number_shop_reviews="-1" inactive_products="false" show_replies="false"]</b>.' .
                    '<ul><li><b>"sort"</b> argument accepts "ASC" to show oldest reviews first and "DESC" to show newest reviews first.</li><li><b>"per_page"</b> argument ' .
                    'defines how many reviews will be shown at once.</li><li><b>"number"</b> argument defines the total number of product reviews to show. ' .
                    'If you set "number" to "-1", then all product reviews will be shown.</li><li><b>"show_summary_bar"</b> argument accepts "true" or "false" ' .
                    'and specifies if a summary bar should be shown on top of the reviews.</li><li><b>"show_pictures"</b> argument accepts "true" or "false" ' .
                    'and specifies if pictures uploaded to reviews will be shown.</li><li><b>"show_products"</b> argument accepts "true" or "false" ' .
                    'and specifies if product names along with product thumbnails should be shown for each review.</li><li><b>"categories"</b> argument ' .
                    'accepts a comma-separated list of product categories IDs. Use this argument to show reviews only from particular ' .
                    'categories of products.</li><li><b>"products"</b> argument accepts a comma-separated list of product IDs. Use this argument to show ' .
                    'reviews only from particular products.</li><li><b>"shop_reviews"</b> argument accepts "true" or "false" and specifies if general shop ' .
                    'reviews will be shown.</li><li><b>"number_shop_reviews"</b> argument defines the total number of shop reviews to show. If you set ' .
                    '"number_shop_reviews" to "-1", then all shop reviews will be shown.</li><li><b>"inactive_products"</b> argument accepts "true" or "false" and specifies if ' .
                    'reviews corresponding to unpublished products will be shown.</li><li><b>"show_replies"</b> argument accepts "true" or "false" and specifies if ' .
                    'replies to reviews will be shown.</li></ul><br>' .
                    '- Use <strong>[cusrev_reviews_grid]</strong> shortcode to display a grid of reviews on any page or post. This shortcode ' .
                    'supports arguments: <b>[cusrev_reviews_grid count="3" show_products="true" product_links="true" sort_by="date" sort="DESC" categories="" ' .
                    'products="" color_ex_brdr="#ebebeb" color_brdr="#ebebeb" color_ex_bcrd="" color_bcrd="#fbfbfb" color_pr_bcrd="#f2f2f2" shop_reviews="false" count_shop_reviews="1" inactive_products="false"]</b>.' .
                    '<ul><li><b>"count"</b> argument defines the number of product reviews to show. It is recommended to keep it between 1 and 9.</li><li><b>"show_products"</b> argument ' .
                    'accepts "true" or "false" and defines if pictures and names of products corresponding to the review will be shown below the review.</li>' .
                    '<li><b>"product_links"</b> argument accepts "true" or "false" and defines if product names will include links to product pages.</li>' .
                    '<li><b>"sort_by"</b> argument accepts "date" to sort reviews by date and "rating" to sort reviews by rating.</li><li><b>"sort"</b> argument defines how reviews ' .
                    'are sorted. Possible values are "ASC", "DESC" and "RAND".</li><li><b>"categories"</b> argument accepts a comma-separated list of product categories IDs to ' .
                    'show only reviews corresponding to specified categories of products.</li><li><b>"products"</b> argument accepts a comma-separated list of product IDs ' .
                    'to show only reviews corresponding to specified products.</li><li><b>"color_ex_brdr"</b> argument is a hex color code of the external border around the ' .
                    'grid of reviews.</li><li><b>"color_brdr"</b> argument is a hex color code of the border around review cards.</li><li><b>"color_ex_bcrd"</b> argument is a hex color code of ' .
                    'the external background of the grid.</li><li><b>"color_bcrd"</b> argument is a hex color code of the background of review cards.</li><li><b>"color_pr_bcrd"</b> argument is ' .
                    'a hex color code of the background color of product areas on review cards.</li><li><b>"shop_reviews"</b> argument accepts "true" or "false" and specifies if general shop ' .
                    'reviews will be shown.</li><li><b>"count_shop_reviews"</b> argument defines the total number of shop reviews to show. It is recommended to keep it between 0 and 3.</li>' .
                    '<li><b>"inactive_products"</b> argument accepts "true" or "false" and specifies if reviews corresponding to unpublished products will be shown.</li></ul><br>' .
                    '- <strong>[cusrev_reviews_grid]</strong> shortcode is also available as <strong>Reviews Grid</strong> block in the new WordPress Gutenberg page editor (blocks require WordPress 5.0 or newer).', IVOLE_TEXT_DOMAIN ) .
                    '<br><br>' . __( '- Use <strong>[cusrev_reviews_slider]</strong> shortcode to display a slider with reviews on any page or post. This shortcode ' .
                    'supports arguments: <b>[cusrev_reviews_slider count="5" slides_to_show="3" show_products="true" product_links="true" sort_by="date" sort="DESC" categories="" ' .
                    'products="" color_brdr="#ebebeb" color_bcrd="#fbfbfb" color_pr_bcrd="#f2f2f2" color_stars="#6bba70" shop_reviews="false" count_shop_reviews="1" inactive_products="false" autoplay="false" avatars="true"]</b>. ' .
                    '<ul><li><b>"count"</b> argument defines the number of product reviews to show. It is recommended to keep it between 0 and 5. If you do not want to show product reviews, ' .
                    'set it to 0 and enable shop reviews (see arguments below).</li><li><b>"slides_to_show"</b> argument defines the number of slides to show. ' .
                    'It is recommended to keep it between 1 and 4.</li><li><b>"show_products"</b> argument ' .
                    'accepts "true" or "false" and defines if pictures and names of products corresponding to the review will be shown below the review.</li><li>' .
                    '<b>"product_links"</b> argument accepts "true" or "false" and defines if product names will include links to product pages.</li><li>' .
                    '<b>"sort_by"</b> argument accepts "date" to sort reviews by date and "rating" to sort reviews by rating.</li><li><b>"sort"</b> argument defines how reviews ' .
                    'are sorted. Possible values are "ASC", "DESC" and "RAND".</li><li><b>"categories"</b> argument accepts a comma-separated list of product categories IDs to ' .
                    'show only reviews corresponding to specified categories of products.</li><li><b>"products"</b> argument accepts a comma-separated list of product IDs ' .
                    'to show only reviews corresponding to specified products.</li><li><b>"color_brdr"</b> argument is a hex color code of the border around review cards.</li><li><b>"color_bcrd"</b> argument is a hex color code of the background of review cards.</li><li><b>"color_pr_bcrd"</b> argument is ' .
                    'a hex color code of the background color of product areas on review cards.</li><li><b>"color_stars"</b> argument is a hex color code of rating stars on review cards.</li><li><b>"shop_reviews"</b> argument accepts "true" or "false" and specifies if general shop ' .
                    'reviews will be shown.</li><li><b>"count_shop_reviews"</b> argument defines the total number of shop reviews to show. It is recommended to keep it between 0 and 5.</li><li>' .
                    '<b>"inactive_products"</b> argument accepts "true" or "false" and specifies if reviews corresponding to unpublished products will be shown.</li><li>' .
                    '<b>"autoplay"</b> argument accepts "true" or "false" and defines if the slider will slide automatically.</li><li>' .
                    '<b>"avatars"</b> argument accepts "true" or "false" and defines if the slider will show avatars of customers.</li></ul>', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_reviews_shortcode',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'title'   => __( 'Reviews Summary Bar', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable display of a histogram table with a summary of reviews on a product page.', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_reviews_histogram',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'title'    => __( 'Vote for Reviews', IVOLE_TEXT_DOMAIN ),
                'desc'     => __( 'Enable people to upvote or downvote reviews. The plugin allows one vote per review per person. If the person is a guest, the plugin uses cookies and IP addresses to identify this visitor.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_reviews_voting',
                'default'  => 'no',
                'type'     => 'checkbox'
            ),
            array(
                'title'   => __( 'Remove Plugin\'s Branding', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable this option to remove plugin\'s branding ("Powered by Customer Reviews Plugin") from the reviews summary bar. If you like our plugin and would like to support us, please disable this checkbox.', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_reviews_nobranding',
                'default' => 'yes',
                'type'    => 'checkbox'
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
}

endif;
