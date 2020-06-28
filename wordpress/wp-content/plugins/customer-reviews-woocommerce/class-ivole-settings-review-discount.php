<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Review_Discount_Settings' ) ):

class Ivole_Review_Discount_Settings {

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

        $this->tab = 'review_discount';

        add_filter( 'ivole_settings_tabs', array( $this, 'register_tab' ) );
        add_action( 'ivole_settings_display_' . $this->tab, array( $this, 'display' ) );
        add_action( 'ivole_save_settings_' . $this->tab, array( $this, 'save' ) );
        add_action( 'admin_head', array( $this, 'add_admin_js' ) );

        add_action( 'woocommerce_admin_field_titlewithid', array( $this, 'show_titlewithid' ) );
        add_action( 'woocommerce_admin_field_sectionendwithid', array( $this, 'show_sectionendwithid' ) );
        add_action( 'woocommerce_admin_field_couponselect', array( $this, 'show_couponselect' ) );
        add_action( 'woocommerce_admin_field_productsearch', array( $this, 'show_productsearch' ) );
        add_action( 'woocommerce_admin_field_htmltext_coupon', array( $this, 'show_htmltext_coupon' ) );

        // array_filter with one argument will filter empty values from the array
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_coupon__product_ids', 'array_filter', 10, 1 );
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_coupon__exclude_product_ids', 'array_filter', 10, 1 );
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_coupon__product_categories', array( $this, 'save_product_categories' ), 10, 3 );
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_coupon__excluded_product_categories', array( $this, 'save_product_categories' ), 10, 3 );
        add_action( 'woocommerce_admin_settings_sanitize_option_ivole_email_body_coupon', array( $this, 'save_htmltext_coupon' ), 10, 3 );

        add_action( 'wp_ajax_woocommerce_json_search_coupons', array( $this, 'woocommerce_json_search_coupons' ) );
        add_action( 'wp_ajax_ivole_send_test_email_coupon', array( $this, 'send_test_email' ) );

        add_action( 'views_edit-shop_coupon', array( $this, 'coupons_quick_link' ), 20 );
        add_filter( 'parse_query', array( $this, 'coupons_quick_link_filter'), 20 );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( 'Review for Discount', IVOLE_TEXT_DOMAIN );
        return $tabs;
    }

    public function display() {
        $this->init_settings();

        WC_Admin_Settings::output_fields( $this->settings );
    }

    public function save() {
        $this->init_settings();
        WC_Admin_Settings::save_fields( $this->settings );
    }

    protected function init_settings() {
        $this->settings = array(
            array(
                'title' => __( 'Review for Discount', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( 'Customize CR settings for sending discount coupons to customers who left reviews. By enabling and using this feature, you agree to <a href="https://www.cusrev.com/terms.html" target="_blank">terms and conditions</a>.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_coupon_options_selector',
            ),
            array(
                'title'   => __( 'Enable Review for Discount', IVOLE_TEXT_DOMAIN ),
                'desc'    => __( 'Enable generation of discount coupons for customers who provide reviews.', IVOLE_TEXT_DOMAIN ),
                'id'      => 'ivole_coupon_enable',
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'title'    => __( 'Coupon to Use', IVOLE_TEXT_DOMAIN ),
                'type'     => 'select',
                'desc'     => __( 'Choose if an individual unique coupon will be generated for' .
                    'each customer or the same existing coupon configured in WooCommerce' .
                    'will be sent to all customers.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_coupon_type',
                'default'  =>'static',
                'options'  => array(
                    'static'  => __( 'Existing Coupon', IVOLE_TEXT_DOMAIN ),
                    'dynamic' => __( 'Generate a Unique Coupon', IVOLE_TEXT_DOMAIN )
                ),
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'BCC Address', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'Add a BCC recipient for emails with discount coupon. It can be useful to verify that emails are being sent properly.', IVOLE_TEXT_DOMAIN ),
                'default'  => '',
                'id'       => 'ivole_coupon_email_bcc',
                'css'      => 'min-width:300px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Reply-To Address', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'Add a Reply-To address for emails with discount coupons. If customers decide to reply to automatic emails, their replies will be sent to this address.', IVOLE_TEXT_DOMAIN ),
                'default'  => get_option( 'admin_email' ),
                'id'       => 'ivole_coupon_email_replyto',
                'css'      => 'min-width:300px;',
                'desc_tip' => true
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'ivole_coupon_options_selector'
            ),
            array(
                'title' => __( 'Existing Coupon to Use', IVOLE_TEXT_DOMAIN ),
                'type'  => 'titlewithid',
                'desc'  => __( 'Choose one the existing coupons to be sent to customers who provided reviews.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_coupon_options_static',
                'class' => 'coupon-setting-fields-static',
                'css'   => 'display:none;'
            ),
            array(
                'title' => __( 'Existing Coupon', IVOLE_TEXT_DOMAIN ),
                'type'  => 'couponselect',
                'desc'  => __( 'This coupon code will be sent to customers who provide reviews.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_existing_coupon'
            ),
            array(
                'type' => 'sectionendwithid',
                'id'   => 'ivole_coupon_options_static'
            ),
            array(
                'title' => __( 'Generation of Individual Coupons', IVOLE_TEXT_DOMAIN ),
                'type'  => 'titlewithid',
                'desc'  => __( 'Settings for automatic generation of unique coupons for each customer and order. When a customer submits a review, a new, unique discount code will be generated according to these settings and sent to the customer.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_coupon_options_dynamic',
                'class' => 'coupon-setting-fields-dynamic',
                'css'   => 'display:none'
            ),
            array(
                'id'      => 'ivole_coupon__discount_type',
                'title'   => __( 'Discount type', 'woocommerce' ),
                'options' => wc_get_coupon_types(),
                'type'    => 'select'
            ),
            array(
                'id'          => 'ivole_coupon__coupon_amount',
                'title'       => __( 'Coupon amount', 'woocommerce' ),
                'placeholder' => wc_format_localized_price( 0 ),
                'desc'        => __( 'Value of the coupon.', 'woocommerce' ),
                'type'        => 'text',
                'desc_tip'    => true,
            ),
            array(
                'id'      => 'ivole_coupon__free_shipping',
                'title'   => __( 'Allow free shipping', 'woocommerce' ),
                'desc'    => sprintf( __( 'Check this box if the coupon grants free shipping. A <a href="%s" target="_blank">free shipping method</a> must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woocommerce' ), 'https://docs.woocommerce.com/document/free-shipping/' ),
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'id'          => 'ivole_coupon__expires_days',
                'title'       => __( 'Validity', IVOLE_TEXT_DOMAIN ),
                'placeholder' => 0, //wc_format_localized_price( 0 ),
                'desc'        => __( 'Number of days during which the coupon will be valid from the moment of submission of a review or set to 0 for unlimited validity.', IVOLE_TEXT_DOMAIN ),
                'class'       => 'short',
                'custom_attributes' => array(
                    'step' => 1,
                    'min'  => 0,
                ),
                'type'     => 'number',
                'default'  => '0',
                'desc_tip' => true,
            ),
            array(
                'id'          => 'ivole_coupon__minimum_amount',
                'title'       => __( 'Minimum spend', 'woocommerce' ),
                'placeholder' => __( 'No minimum', 'woocommerce' ),
                'desc'        => __( 'This field allows you to set the minimum spend (subtotal, including taxes) allowed to use the coupon.', 'woocommerce' ),
                'type'        => 'text',
                'desc_tip'    => true,
            ),
            array(
                'id'          => 'ivole_coupon__maximum_amount',
                'title'       => __( 'Maximum spend', 'woocommerce' ),
                'placeholder' => __( 'No maximum', 'woocommerce' ),
                'desc'        => __( 'This field allows you to set the maximum spend (subtotal, including taxes) allowed when using the coupon.', 'woocommerce' ),
                'type'        => 'text',
                'desc_tip'    => true,
            ),
            array(
                'id'      => 'ivole_coupon__individual_use',
                'title'   => __( 'Individual use only', 'woocommerce' ),
                'desc'    => __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce' ),
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'id'      => 'ivole_coupon__exclude_sale_items',
                'title'   => __( 'Exclude sale items', 'woocommerce' ),
                'desc'    => __( 'Check this box if the coupon should not apply to items on sale. Per-item coupons will only work if the item is not on sale. Per-cart coupons will only work if there are items in the cart that are not on sale.', 'woocommerce' ),
                'default' => 'no',
                'type'    => 'checkbox'
            ),
            array(
                'id'          => 'ivole_coupon__product_ids',
                'title'       => __( 'Products', 'woocommerce' ),
                'desc'        => __( 'Products which need to be in the cart to use this coupon or, for "Product Discounts", which products are discounted.', 'woocommerce' ),
                'placeholder' => __( 'Search for a product&hellip;', 'woocommerce' ),
                'type'        => 'productsearch'
            ),
            array(
                'id'          => 'ivole_coupon__exclude_product_ids',
                'title'       => __( 'Exclude products', 'woocommerce' ),
                'desc'        => __( 'Products which must not be in the cart to use this coupon or, for "Product Discounts", which products are not discounted.', 'woocommerce' ),
                'placeholder' => __( 'Search for a product&hellip;', 'woocommerce' ),
                'type'        => 'productsearch'
            ),
            array(
                'title'    => __( 'Product categories', IVOLE_TEXT_DOMAIN ),
                'type'     => 'cselect',
                'desc'     => __( 'A product must be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will be discounted.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_coupon__product_categories',
                'desc_tip' => true,
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:400px;'
            ),
            array(
                'title'    => __( 'Exclude categories', IVOLE_TEXT_DOMAIN ),
                'type'     => 'cselect',
                'desc'     => __( 'Product must not be in this category for the coupon to remain valid or, for "Product Discounts", products in these categories will not be discounted.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_coupon__excluded_product_categories',
                'desc_tip' => true,
                'class'    => 'wc-enhanced-select',
                'css'      => 'min-width:300px;'
            ),
            array(
                'id'                => 'ivole_coupon__usage_limit',
                'title'             => __( 'Usage limit', 'woocommerce' ),
                'desc'              => __( 'How many times this coupon can be used before it is void. Set it to 0 for unlimited usage.', 'woocommerce' ),
                'type'              => 'number',
                'desc_tip'          => true,
                'default'           => 0,
                'placeholder'       => 0,
                'class'             => 'short',
                'custom_attributes' => array(
                    'step' => 1,
                    'min'  => 0,
                )
            ),
            array(
                'type' => 'sectionendwithid',
                'id'   => 'ivole_coupon_options_dynamic'
            ),
            array(
                'title' => __( 'Email Template', IVOLE_TEXT_DOMAIN ),
                'type'  => 'title',
                'desc'  => __( 'Adjust template of the email that will be sent to customers. If you enable <b>advanced</b> email templates in your account on <a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">CR website</a>, they will <b>override</b> the email template below.', IVOLE_TEXT_DOMAIN ),
                'id'    => 'ivole_options_email_coupon'
            ),
            array(
                'title'    => __( 'Email Subject', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'Subject of the email that will be sent to customers.', IVOLE_TEXT_DOMAIN ),
                'default'  => '[{site_title}] Discount Coupon for You',
                'id'       => 'ivole_email_subject_coupon',
                'css'      => 'min-width:600px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Email Heading', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'desc'     => __( 'Heading of the email that will be sent to customers.', IVOLE_TEXT_DOMAIN ),
                'default'  => 'Thank You for Leaving a Review',
                'id'       => 'ivole_email_heading_coupon',
                'css'      => 'min-width:600px;',
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Email Body', IVOLE_TEXT_DOMAIN ),
                'type'     => 'htmltext_coupon',
                'desc'     => __( 'Body of the email that will be sent to customers.', IVOLE_TEXT_DOMAIN ),
                'id'       => 'ivole_email_body_coupon',
                'desc_tip' => true
            ),
            array(
                'title' => __( 'Email Color 1', IVOLE_TEXT_DOMAIN ),
                'type' => 'text',
                'id' => 'ivole_email_coupon_color_bg',
                'default' => '#0f9d58',
                'desc' => __( 'Background color for heading of the email and review button.', IVOLE_TEXT_DOMAIN ),
                'desc_tip' => true
            ),
            array(
                'title'    => __( 'Email Color 2', IVOLE_TEXT_DOMAIN ),
                'type'     => 'text',
                'id'       => 'ivole_email_coupon_color_text',
                'default'  => '#ffffff',
                'desc'     => __( 'Text color for heading of the email and review button.', IVOLE_TEXT_DOMAIN ),
                'desc_tip' => true
            ),
            array(
                'title'       => __( 'Send Test', IVOLE_TEXT_DOMAIN ),
                'type'        => 'emailtest',
                'desc'        => __( 'Send a test email to this address. You must save changes before sending a test email.', IVOLE_TEXT_DOMAIN ),
                'default'     => '',
                'placeholder' => 'Email address',
                'id'          => 'ivole_email_test_coupon',
                'css'         => 'min-width:300px;',
                'desc_tip'    => true
            ),
            array(
                'type' => 'sectionend',
                'id' => 'ivole_options_email_coupon'
            )
        );
    }

    public function add_admin_js() {
        if ( $this->settings_menu->is_this_page() && $this->settings_menu->get_current_tab() === 'review_reminder' ){
            // add warning text about coupons dynamically above the email body
            $is_coupon_enabled = WC_Admin_Settings::get_option( 'ivole_coupon_enable' );
            ?>
            <style>
                li.select2-selection__choice[title=""] {
                    display: none;
                }
                #coupon_notification > td{
                    padding:0 !important;
                }
                #coupon_notification > td span{
                    max-width: 680px !important;
                    padding:10px;
                    margin-left:10px;
                    display:inline-block;
                    background-color: #ffff00;
                }
            </style>
            <script type="text/javascript">
                var coupon_notification_html = "<tr valign='top' <?php if ( $is_coupon_enabled != 'yes' ) echo "style='display:none;'"; ?> id='coupon_notification'><th></th><td><span><strong>";
                coupon_notification_html += "<?php echo __( 'Discounts for customers who provide reviews are enabled. Don’t forget to mention it in this email to increase the number of reviews.</span>', IVOLE_TEXT_DOMAIN ) ?>";
                coupon_notification_html += "</strong></td></tr>";

                jQuery(document).ready(function(){
                    jQuery('#ivole_email_heading').parent().parent().after(coupon_notification_html);
                });
            </script>
            <?php
        } elseif ( $this->is_this_tab() ) {
            // show/hide the setting for static/dynamic coupon creation
            $coupon_type = WC_Admin_Settings::get_option( 'ivole_coupon_type', 'static' );
            ?>
            <style>
                li.select2-selection__choice[title=""] {
                    display: none;
                }
            </style>
            <script id='ivole-coupon-scripts' type="text/javascript">
                jQuery(document).ready(function(){
                    var ctype = jQuery('#ivole_coupon_type').val();

                    if (ctype == 'static') {
                        jQuery('.coupon-setting-fields-dynamic').hide();
                        jQuery('.coupon-setting-fields-static').show();
                    } else {
                        jQuery('.coupon-setting-fields-dynamic').show();
                        jQuery('.coupon-setting-fields-static').hide();
                    }

                    jQuery('#ivole_coupon_type').change(function(){
                        if (jQuery(this).val() == 'static') {
                            jQuery('.coupon-setting-fields-dynamic').hide();
                            jQuery('.coupon-setting-fields-static').show();
                        } else {
                            jQuery('.coupon-setting-fields-dynamic').show();
                            jQuery('.coupon-setting-fields-static').hide();
                        }
                    });

                    jQuery('#mainform').submit(function(){
                        if ( jQuery('#ivole_coupon_type').val() == 'static' && jQuery('#ivole_coupon_enable:checked').length > 0 ) {
                            var v = jQuery('#ivole_existing_coupon').val();

                            if (parseInt(v) + '' != v + '' || parseInt(v) == 0) {
                                alert("<?php echo __('Please select an existing coupon!'); ?>");
                                return false;
                            }

                            return true;
                        }

                        return true;
                    });
                });

                jQuery( function( $ ) {
                    function getEnhancedSelectFormatString() {
                        return {
                            'language': {
                                errorLoading: function() {
                                    // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                                    return wc_enhanced_select_params.i18n_searching;
                                },
                                inputTooLong: function(args) {
                                    var overChars = args.input.length - args.maximum;
                                    if (1 === overChars) {
                                        return wc_enhanced_select_params.i18n_input_too_long_1;
                                    }

                                    return wc_enhanced_select_params.i18n_input_too_long_n.replace('%qty%', overChars);
                                },
                                inputTooShort: function(args) {
                                    var remainingChars = args.minimum - args.input.length;

                                    if (1 === remainingChars) {
                                        return wc_enhanced_select_params.i18n_input_too_short_1;
                                    }

                                    return wc_enhanced_select_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
                                },
                                loadingMore: function() {
                                    return wc_enhanced_select_params.i18n_load_more;
                                },
                                maximumSelected: function(args) {
                                    if (args.maximum === 1) {
                                        return wc_enhanced_select_params.i18n_selection_too_long_1;
                                    }

                                    return wc_enhanced_select_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
                                },
                                noResults: function() {
                                    return wc_enhanced_select_params.i18n_no_matches;
                                },
                                searching: function() {
                                    return wc_enhanced_select_params.i18n_searching;
                                }
                            }
                        };
                    }

                    try {
                        // Ajax coupon search box
                        $(':input.wc-coupon-search').filter(':not(.enhanced)').each(function () {
                            var select2_args = {
                                allowClear: $(this).data('allow_clear') ? true : false,
                                placeholder: $(this).data('placeholder'),
                                minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : '3',
                                escapeMarkup: function (m) {
                                    return m;
                                },
                                ajax: {
                                    url: wc_enhanced_select_params.ajax_url,
                                    dataType: 'json',
                                    delay: 250,
                                    data: function (params) {
                                        return {
                                            term: params.term,
                                            action: $(this).data('action') || 'woocommerce_json_search_coupons',
                                            security: wc_enhanced_select_params.search_products_nonce,
                                            exclude: $(this).data('exclude'),
                                            include: $(this).data('include'),
                                            limit: $(this).data('limit')
                                        };
                                    },
                                    processResults: function (data) {
                                        var terms = [];
                                        if (data) {
                                            $.each(data, function (id, text) {
                                                terms.push({id: id, text: text});
                                            });
                                        }
                                        return {
                                            results: terms
                                        };
                                    },
                                    cache: true
                                }
                            };

                            select2_args = $.extend(select2_args, getEnhancedSelectFormatString());

                            $(this).select2(select2_args).addClass('enhanced');

                            if ($(this).data('sortable')) {
                                var $select = $(this);
                                var $list = $(this).next('.select2-container').find('ul.select2-selection__rendered');

                                $list.sortable({
                                    placeholder: 'ui-state-highlight select2-selection__choice',
                                    forcePlaceholderSize: true,
                                    items: 'li:not(.select2-search__field)',
                                    tolerance: 'pointer',
                                    stop: function () {
                                        $($list.find('.select2-selection__choice').get().reverse()).each(function () {
                                            var id = $(this).data('data').id;
                                            var option = $select.find('option[value="' + id + '"]')[0];
                                            $select.prepend(option);
                                        });
                                    }
                                });
                            }
                        });
                    } catch(err) {
                        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                        window.console.log( err );
                    }
                });
            </script>
            <?php
        }
    }

    public function is_this_tab() {
        return $this->settings_menu->is_this_page() && ( $this->settings_menu->get_current_tab() === $this->tab );
    }

    /**
     * Custom field type for section start with ID and/or CLASS option (adding a wrapper div)
    */
    public function show_titlewithid( $value ) {
        $tmp = Ivole_Admin::ivole_get_field_description( $value );
        $description = $tmp['description'];
        $id = ( isset( $value['id'] ) && $value['id'] != '' ) ? "id='" . $value['id'] . "'" : '';
        $class = isset( $value['class'] ) ? "class='" . $value['class'] . "'" : '';
        $css = isset( $value['css'] ) ? $value['css'] : '';

        echo "<div $id $class style='$css'>";

        if ( ! empty( $value['title'] ) ) {
            echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
        }

        if ( ! empty( $description ) ) {
            echo wpautop( wptexturize( wp_kses_post( $description ) ) );
        }

        echo '<table class="form-table">'. "\n\n";
    }

    /**
     * Custom field type for section end with ID and/or CLASS option (adding a wrapper div)
     */
    public function show_sectionendwithid( $value ) {
        echo '</table></div>';
    }

    /**
     * Custom field type for selecting existing coupons
     */
    public function show_couponselect( $value ) {
        $tmp = Ivole_Admin::ivole_get_field_description( $value );
        $tooltip_html = $tmp['tooltip_html'];
        $description = $tmp['description'];
        $selection = WC_Admin_Settings::get_option( $value['id'] );
        $coupons = $this->get_existing_coupons();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $tooltip_html; ?>
            </th>
            <td class="forminp">
                <select class="wc-coupon-search"  style="width: 350px;" id="<?php echo esc_attr( $value['id'] ); ?>"
                    name="<?php echo esc_attr( $value['id'] ); ?>" data-placeholder="Search for a coupon&hellip;"
                    data-action="woocommerce_json_search_coupons" >
                    <?php
                        foreach ( $coupons as $key => $val ) {
                            if ( $selection == $key ) {
                                echo "<option value='". esc_attr( $key ) ."'>".$val."</option>";
                            }
                        }
                    ?>
                </select>
                <br>
                <?php echo ( $description ) ? $description : ''; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom field type for selecting products
     */
    public function show_productsearch( $value ) {
        $tmp = Ivole_Admin::ivole_get_field_description( $value );
        $tooltip_html = $tmp['tooltip_html'];
        $description = $tmp['description'];
        $selection = (array) WC_Admin_Settings::get_option( $value['id'] );
        $class = 'wc-product-search';
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $tooltip_html; ?>
            </th>
            <td class="forminp">
                <!-- id="<?php echo esc_attr( $value['id'] ); ?>" -->
                <select class="<?php echo $class; ?>"  multiple="multiple" style="width: 350px;"
                    name="<?php echo esc_attr( $value['id'] ); ?>[]" data-placeholder="<?php esc_attr_e( $value['placeholder'], 'woocommerce' ); ?>"
                    data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true" >
                    <option value="" selected="selected"></option>
                    <?php
                        foreach ( $selection as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                    ?>
                </select>
                <br>
                <?php echo ( $description ) ? $description : ''; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom field type for body email
     */
    public function show_htmltext_coupon( $value ) {
        $tmp = Ivole_Admin::ivole_get_field_description( $value );
        $tooltip_html = $tmp['tooltip_html'];
        $description = $tmp['description'];
        $default_text = Ivole_Email::$default_body_coupon;

        $body = wp_kses_post( WC_Admin_Settings::get_option( $value['id'], $default_text ) );
        $settings = array (
            'teeny'         => true,
            'editor_css'    => '<style>#wp-ivole_email_body_coupon-wrap {max-width: 700px !important;}</style>',
            'textarea_rows' => 20
        );
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php echo $tooltip_html; ?>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                <?php echo $description; ?>
                <?php wp_editor( $body, 'ivole_email_body_coupon', $settings );
                echo '<div>';
                echo '<p style="font-weight:bold;margin-top:1.5em;font-size=1em;">' . __( 'Variables', IVOLE_TEXT_DOMAIN ) . '</p>';
                echo '<p>' . __( 'You can use the following variables in the email:', IVOLE_TEXT_DOMAIN ) . '</p>';
                echo '<p><strong>{site_title}</strong> - ' . __( 'The title of your WordPress website.', IVOLE_TEXT_DOMAIN ) . '</p>';
                echo '<p><strong>{customer_first_name}</strong> - ' . __( 'The first name of the customer who purchased from your store.', IVOLE_TEXT_DOMAIN ) . '</p>';
                echo '<p><strong>{customer_name}</strong> - ' . __( 'The full name of the customer who purchased from your store.', IVOLE_TEXT_DOMAIN ) . '</p>';
                echo '<p><strong>{coupon_code}</strong> - ' . __( 'The code of coupon for discount.', IVOLE_TEXT_DOMAIN ).'</p>';
                echo '<p><strong>{discount_amount}</strong> - ' . __( 'Amount of the coupon (e.g., $10 or 11% depending on type of the coupon).', IVOLE_TEXT_DOMAIN ).'</p>';
                echo '</div>';
                ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Custom field type for body of coupon email
     */
    public function save_htmltext_coupon( $value, $option, $raw_value ) {
        return wp_kses_post( $raw_value );
    }

    /**
     * Custom field type for selecting product categories of unique coupon
     */
    public function save_product_categories( $value, $option, $raw_value ) {
        if( is_array( $value ) ) {
            $value = array_filter( $value, function($v) { return $v != ""; } );
        } else {
          $value = array();
        }
        return $value;
    }

    /**
     * Show a quick link to coupons generated by this plugin on the standard WooCommerce coupons admin page.
     */
    public function coupons_quick_link( $views ) {
        global $wp_query;

        if ( is_admin() ) {
            $query = array(
                'post_type'    => 'shop_coupon',
                'post_status'  => array( 'publish' ),
                'meta_key'     => '_ivole_auto_generated',
                'meta_value'   => 1,
                'meta_compare' => 'NOT EXISTS'
            );

            $result = new WP_Query( $query );

            if ( $result->found_posts > 0 ) {
                $class = ( '_ivole_auto_generated' == $wp_query->query_vars['meta_key'] ) ? ' class="current"' : '';
                $views['ivole'] = sprintf( __('<a href="%s"'. $class .'>Manually Published <span class="count">(%d)</span></a>', IVOLE_TEXT_DOMAIN ), admin_url( 'edit.php?post_type=shop_coupon&ivole_coupon=0' ), $result->found_posts );
            }
        }

        return $views;
    }

    /**
     * Parse "ivole_coupon" GET parameter and adjust WP Query to show only coupons generated by this plugin.
     */
    public function coupons_quick_link_filter( $query ) {
        if ( is_admin() && array_key_exists( 'post_type', $query->query ) && 'shop_coupon' == $query->query['post_type'] ) {
            $qv = &$query->query_vars;
            if ( isset( $_GET['ivole_coupon'] ) ) {
                $qv['post_status'] = 'publish';
                $qv['meta_key'] = '_ivole_auto_generated';
                $qv['meta_value'] = 1;
                $qv['meta_compare'] = 'NOT EXISTS';
            }
        }
    }

    /**
     * Ajax action callback for enhanced select box for existing coupuns
    */
    public function woocommerce_json_search_coupons(){
        global $wpdb;

        $term = stripslashes( $_GET['term'] . '%' );
        if ( empty( $term ) ) {
            wp_die();
        }

        $data_store = WC_Data_Store::load( 'coupon' );
        $all = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $wpdb->posts
                WHERE post_title LIKE %s AND post_type = 'shop_coupon' AND post_status = 'publish'
                ORDER BY post_date DESC;",
                $term
            ),
            ARRAY_A
        );

        $coupons = array();
        $today = time();
        foreach ( $all as $coupon ) {
            $expires = get_post_meta( $coupon['ID'], 'date_expires', true );
            $email_array = get_post_meta( $coupon['ID'], 'customer_email', true );
            if ( ( intval( $expires ) > $today || intval( $expires ) == 0 ) &&
                 ( ! is_array( $email_array ) || count( $email_array ) == 0 ) ) {
                $coupons[ $coupon['ID'] ] = rawurldecode( stripslashes( $coupon['post_title'] ) );
            }
        }

        wp_send_json( $coupons );
    }

    /**
     * Ajax callback  that sends testing email
     */
    public function send_test_email() {
        global $q_config;

        $email = strval( $_POST['email'] );
        $coupon_type = $_POST['coupon_type'];
        $q_language = $_POST['q_language'];
      	// integration with qTranslate
      	if ( $q_language >= 0 ) {
      		$q_config['language'] = $q_language;
        }

        if ( $coupon_type === 'static' ) {
            $coupon_id = intval( $_POST['existing_coupon'] );
            if ( get_post_type( $coupon_id ) == 'shop_coupon' && get_post_status( $coupon_id ) == 'publish' ) {
                $coupon_code = get_post_field( 'post_title', $coupon_id );
                $discount_type = get_post_meta( $coupon_id, 'discount_type', true );
                $discount_amount = intval( get_post_meta( $coupon_id, 'coupon_amount', true ) );
                $discount_string = '';
                if ( $discount_type == 'percent' ) {
                    $discount_string = $discount_amount . '%';
                } else {
                    $discount_string = trim(
                        strip_tags(
                            wc_price( $discount_amount, array( 'currency' => get_option( 'woocommerce_currency' ) ) )
                        )
                    );
                }
            } else {
                $coupon_code = "<strong>NO_COUPON_SET</strong>";
                $discount_string = "<strong>NO_AMOUNT_SET</strong>";
            }
        } else {
            $discount_type = $_POST['discount_type'];
            $discount_amount = intval( $_POST['discount_amount'] );
            if ( $discount_type === "percent" && $discount_amount > 0 ){
                $discount_string = $discount_amount . "%";
            } elseif ( $discount_amount > 0 ) {
                $discount_string = trim(
                    strip_tags(
                        wc_price( $discount_amount, array( 'currency' => get_option( 'woocommerce_currency' ) ) )
                    )
                );
            }

            $coupon_code = uniqid( 'TEST' );
        }

        if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            $e = new Ivole_Email_Coupon();
            $result = $e->trigger2( null, $email, $coupon_code, $discount_string );
            if ( is_array( $result ) && count( $result )  > 1 && 2 === $result[0] ) {
        		wp_send_json( array( 'code' => 2, 'message' => $result[1] ) );
        	} elseif( 0 === $result ) {
        		wp_send_json( array( 'code' => 0, 'message' => '' ) );
        	} elseif( 1 === $result ) {
        		wp_send_json( array( 'code' => 1, 'message' => '' ) );
        	}
        } else {
            wp_send_json( array( 'code' => 99, 'message' => '' ) );
        }

        wp_send_json( array( 'code' => 98, 'message' => '' ) );
    }

    protected function get_existing_coupons() {
        global $wpdb;

        $all = $wpdb->get_results(
            "SELECT * FROM {$wpdb->posts}
            WHERE post_type = 'shop_coupon' AND post_status = 'publish'
            ORDER BY post_date DESC;",
            ARRAY_A
        );

        $coupons = array();
        $today = time();
        foreach ( $all as $coupon ) {
            $expires = get_post_meta( $coupon['ID'], 'date_expires', true );
            $email_array = get_post_meta( $coupon['ID'], 'customer_email', true );
            if ( ( intval( $expires ) > $today || intval( $expires ) == 0 ) &&
                 ( ! is_array( $email_array ) || count( $email_array ) == 0 ) ) {
                $coupons[ $coupon['ID'] ] = rawurldecode( stripslashes( $coupon['post_title'] ) );
            }
        }

        return $coupons;
    }
}

endif;
