<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CR_Reviews_Product_Feed' ) ):

class CR_Reviews_Product_Feed {

    /**
     * @var CR_Product_Feed_Admin_Menu The instance of the admin menu
     */
    protected $product_feed_menu;

    /**
     * @var string The slug of this tab
     */
    protected $tab;

    /**
     * @var array The fields for this tab
     */
    protected $settings;

    protected $alternate = false;

    public function __construct( $product_feed_menu ) {
      $this->product_feed_menu = $product_feed_menu;

      $this->tab = 'reviews';

      add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
      add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
      add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );
      add_action( 'woocommerce_admin_field_ivole_field_map', array( $this, 'display_field_map' ) );
      add_filter( 'woocommerce_admin_settings_sanitize_option_ivole_google_field_map', array( $this, 'sanitize_field_map' ) );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( 'Reviews', IVOLE_TEXT_DOMAIN );
        return $tabs;
    }

    public function display() {
        $this->init_settings();
        WC_Admin_Settings::output_fields( $this->settings );
    }

    public function save() {
      $this->init_settings();
      WC_Admin_Settings::save_fields( $this->settings );

      $feed = new CR_Google_Shopping_Prod_Feed();
  		if ( $feed->is_enabled() ) {
  			$feed->activate();
  		} else {
  			$feed->deactivate();
  		}

      $feed_reviews = new Ivole_Google_Shopping_Feed();
  		if ( $feed_reviews->is_enabled() ) {
  			$feed_reviews->activate();
  		} else {
  			$feed_reviews->deactivate();
  		}
    }

    protected function init_settings() {
      $field_map = get_option( 'ivole_google_field_map', array(
  			'gtin'  => '',
  			'mpn'   => '',
  			'sku'   => '',
  			'brand' => ''
  		) );

      $this->settings = array(
        array(
            'title' => __( 'Reviews XML Feed', IVOLE_TEXT_DOMAIN ),
            'type'  => 'title',
            'desc'  => __( 'Google Shopping is a service that allows merchants to list their products by uploading a product feed in the <a href="https://merchants.google.com/">Merchant Center</a>. Use XML Product Review Feed to enable star ratings for your products in Google Shopping.', IVOLE_TEXT_DOMAIN ),
            'id'    => 'cr_reviews_xml'
        ),
        array(
  				'id'       => 'ivole_google_exclude_variable_parent',
  				'title'    => __( 'Exclude Parent Product', IVOLE_TEXT_DOMAIN ),
  				'desc'     => __( 'Exclude product IDs for parents of variable products from the XML feed', IVOLE_TEXT_DOMAIN ),
  				'default'  => 'yes',
  				'type'     => 'checkbox'
  			),
        array(
  				'id'       => 'ivole_google_encode_special_chars',
  				'title'    => __( 'Encode Special Characters', IVOLE_TEXT_DOMAIN ),
  				'desc'     => __( 'Encode special characters in the XML feed', IVOLE_TEXT_DOMAIN ),
  				'default'  => 'no',
  				'type'     => 'checkbox'
  			),
        array(
  				'id'        => 'ivole_google_field_map',
  				'type'      => 'ivole_field_map',
  				'title'     => __( 'Fields Mapping', IVOLE_TEXT_DOMAIN ),
  				'desc'      => __( 'Specify WooCommerce fields that should be mapped to GTIN, MPN, SKU, and Brand fields in XML Product Review Feed for Google Shopping.', IVOLE_TEXT_DOMAIN ),
  				'desc_tip'  => true,
  				'field_map' => $field_map
  			),
        array(
            'type' => 'sectionend',
            'id'   => 'cr_reviews_xml'
        )
      );
    }

    public function is_this_tab() {
        return $this->product_feed_menu->is_this_page() && ( $this->product_feed_menu->get_current_tab() === $this->tab );
    }

    public function display_field_map( $options ) {
      $options = wp_parse_args( $options, array(
  			'field_map' => array(
  				'gtin'  => '',
  				'mpn'   => '',
  				'sku'   => '',
  				'brand' => ''
  			)
  		) );
  		$tmp = Ivole_Admin::ivole_get_field_description( $options );
  		$tooltip_html = $tmp['tooltip_html'];
      ?>
      <tr valign="top">
        <td colspan="2" style="padding-left:0px;padding-right:0px;padding-bottom:0px;font-weight:600;color:#23282d;">
          <?php echo esc_html( $options['title'] ); ?>
          <?php echo $tooltip_html; ?>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2" style="padding-left:0px;padding-right:0px;">
          <table class="cr-product-feed-categories widefat">
            <thead>
          		<tr>
          			<th class="cr-product-feed-categories-th">
                  <?php
                  esc_html_e( 'XML Feed Field', IVOLE_TEXT_DOMAIN );
                  ?>
                </th>
          			<th class="cr-product-feed-categories-th">
                  <?php
                  esc_html_e( 'WooCommerce Field', IVOLE_TEXT_DOMAIN );
                  //echo Ivole_Admin::ivole_wc_help_tip( __( 'Product category from Google Shopping product taxonomy', IVOLE_TEXT_DOMAIN ) );
                  ?>
                </th>
          		</tr>
          	</thead>
            <tbody>
              <tr>
                <td class="cr-product-feed-categories-td">
                  <?php echo __( 'GTIN', IVOLE_TEXT_DOMAIN ); ?>
                </td>
                <td class="cr-product-feed-categories-td">
                  <select class="cr-product-feed-identifiers-select" name="ivole_field_wc_target_gtin">
                    <option></option>
                    <?php foreach ( $this->get_product_attributes() as $attribute_value => $attribute_name ): ?>
  										<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $options['field_map']['gtin'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
  									<?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr class="cr-alternate">
                <td class="cr-product-feed-categories-td">
                  <?php echo __( 'MPN', IVOLE_TEXT_DOMAIN ); ?>
                </td>
                <td class="cr-product-feed-categories-td">
                  <select class="cr-product-feed-identifiers-select" name="ivole_field_wc_target_mpn">
                    <option></option>
                    <?php foreach ( $this->get_product_attributes() as $attribute_value => $attribute_name ): ?>
  										<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $options['field_map']['mpn'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
  									<?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr>
                <td class="cr-product-feed-categories-td">
                  <?php echo __( 'SKU', IVOLE_TEXT_DOMAIN ); ?>
                </td>
                <td class="cr-product-feed-categories-td">
                  <select class="cr-product-feed-identifiers-select" name="ivole_field_wc_target_sku">
                    <option></option>
                    <?php foreach ( $this->get_product_attributes() as $attribute_value => $attribute_name ): ?>
  										<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $options['field_map']['sku'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
  									<?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr class="cr-alternate">
                <td class="cr-product-feed-categories-td">
                  <?php echo __( 'Brand', IVOLE_TEXT_DOMAIN ); ?>
                </td>
                <td class="cr-product-feed-categories-td">
                  <select class="cr-product-feed-identifiers-select" name="ivole_field_wc_target_brand">
                    <option></option>
                    <?php foreach ( $this->get_product_attributes() as $attribute_value => $attribute_name ): ?>
  										<option value="<?php echo $attribute_value; ?>" <?php if ( $attribute_value == $options['field_map']['brand'] ) echo "selected"; ?>><?php echo $attribute_name; ?></option>
  									<?php endforeach; ?>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </td>
  		</tr>
  		<?php
  	}

    public function sanitize_field_map( $value ) {
  		if ( isset(
  			$_POST['ivole_field_wc_target_gtin'],
  			$_POST['ivole_field_wc_target_mpn'],
  			$_POST['ivole_field_wc_target_sku'],
  			$_POST['ivole_field_wc_target_brand']
  		) ) {
  			$value = array(
  				'gtin'  => sanitize_key( $_POST['ivole_field_wc_target_gtin'] ),
  				'mpn'   => sanitize_key( $_POST['ivole_field_wc_target_mpn'] ),
  				'sku'   => sanitize_key( $_POST['ivole_field_wc_target_sku'] ),
  				'brand' => sanitize_key( $_POST['ivole_field_wc_target_brand'] )
  			);
  		}

  		return $value;
  	}

    protected function get_product_attributes() {
  		global $wpdb;

  		$product_attributes = array(
  			'product_id'   => __( 'Product ID', IVOLE_TEXT_DOMAIN ),
  			'product_sku'  => __( 'Product SKU', IVOLE_TEXT_DOMAIN ),
  			'product_name' => __( 'Product Name', IVOLE_TEXT_DOMAIN )
  		);

  		$product_attributes = array_reduce( wc_get_attribute_taxonomies(), function( $attributes, $taxonomy ) {
  			$key = 'attribute_' . $taxonomy->attribute_name;
  			$attributes[$key] = ucfirst( $taxonomy->attribute_label );

  			return $attributes;
  		}, $product_attributes );

  		$meta_attributes = $wpdb->get_results(
  			"SELECT meta.meta_id, meta.meta_key, meta.meta_value
  			FROM {$wpdb->postmeta} AS meta, {$wpdb->posts} AS posts
  			WHERE meta.post_id = posts.ID AND posts.post_type LIKE '%product%' AND (
  				meta.meta_key NOT LIKE '\_%'
  				OR meta.meta_key LIKE '\_woosea%'
  				OR meta.meta_key LIKE '\_wpm%' OR meta.meta_key LIKE '\_cr_%'
  				OR meta.meta_key LIKE '\_yoast%'
  				OR meta.meta_key = '_product_attributes'
  			)
  			GROUP BY meta.post_id, meta.meta_key",
  			ARRAY_A
  		);

  		if ( is_array( $meta_attributes ) ) {
  			$product_attributes = array_reduce( $meta_attributes, function( $attributes, $meta_attribute ) {

  				// If the meta entry is _product_attributes, then consider each attribute spearately
  				if ( $meta_attribute['meta_key'] === '_product_attributes' ) {

  					$attrs = maybe_unserialize( $meta_attribute['meta_value'] );
  					if ( is_array( $attrs ) ) {

  						foreach ( $attrs as $attr_key => $attr ) {
  							$key = 'attribute_' . $attr_key;
  							$attributes[$key] = ucfirst( $attr['name'] );
  						}

  					}

  				} else {
            $key = 'meta_' . $meta_attribute['meta_key'];
            $attributes[$key] = ucfirst( str_replace( '_', ' ', $meta_attribute['meta_key'] ) );
  				}
  				return $attributes;
  			}, $product_attributes );
  		}
      $product_attributes['meta__cr_gtin'] = __( 'Product GTIN', IVOLE_TEXT_DOMAIN );
      $product_attributes['meta__cr_mpn'] = __( 'Product MPN', IVOLE_TEXT_DOMAIN );
      $product_attributes['meta__cr_brand'] = __( 'Product Brand', IVOLE_TEXT_DOMAIN );

      $product_attributes['tags_tags'] = __( 'Product Tag', IVOLE_TEXT_DOMAIN );

      natcasesort( $product_attributes );

  		return $product_attributes;
  	}
}

endif;
