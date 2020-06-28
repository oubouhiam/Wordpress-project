<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CR_Status_Product_Feed' ) ):

class CR_Status_Product_Feed {

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

    public function __construct( $product_feed_menu ) {
        $this->product_feed_menu = $product_feed_menu;

        $this->tab = 'overview';

        add_filter( 'cr_productfeed_tabs', array( $this, 'register_tab' ) );
        add_action( 'cr_productfeed_display_' . $this->tab, array( $this, 'display' ) );
        add_action( 'cr_save_productfeed_' . $this->tab, array( $this, 'save' ) );

        add_action( 'woocommerce_admin_field_feed_file_url', array( $this, 'display_feed_file_url' ) );
        add_action( 'woocommerce_admin_field_product_feed_file_url', array( $this, 'display_product_feed_file_url' ) );
        add_action( 'woocommerce_admin_field_product_feed_status', array( $this, 'display_product_feed_status' ) );
    }

    public function register_tab( $tabs ) {
        $tabs[$this->tab] = __( 'Overview', IVOLE_TEXT_DOMAIN );
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
      $this->settings = array(
          array(
              'title' => __( 'Overview', IVOLE_TEXT_DOMAIN ),
              'type'  => 'title',
              'desc'  => __( '<b>Review Stars in Google Search Organic Listings</b><br><br>' .
      				'The standard WooCommerce functionality already includes structured data markup to display your product reviews effectively within organic search results. This plugin extends the standard functionality and adds some extra markup to help search engines properly crawl your shop. It is important to understand that having a valid structured data markup in place makes your website eligible for organic stars in Google but it doesn\'t guarantee that they will be shown. You can test the rich snippets using <a href="https://search.google.com/structured-data/testing-tool">Google’s Structured Data Testing Tool</a>.<br><br>' .
              'Google now expects product identifiers (GTIN, MPN, Brand) to be populated in the structured data markup. Our plugin offers an option to add fields for product identifiers to WooCommerce products.<br><br>' .
      				'<b>Review Stars in Google Shopping</b><br><br>Google Shopping is a service that allows merchants to list their products by uploading a product feed in the <a href="https://merchants.google.com/">Merchant Center</a>. XML Product Feed is necessary to submit products to Google Shopping. XML Product Review Feed is necessary to show stars for products in Google Shopping search results. The feeds should be maintained in Google Merchant Center account.', IVOLE_TEXT_DOMAIN ),
              'id'    => 'cr_status'
          ),
          array(
            'title'   => __( 'Generate Product Feed', IVOLE_TEXT_DOMAIN ),
            'desc'    => __( 'Generate XML Product Feed', IVOLE_TEXT_DOMAIN ),
            'desc_tip' => __( 'When active, an XML file with products for Google Shopping will be generated immediately after saving settings and then updated every 24 hours.', IVOLE_TEXT_DOMAIN ),
            'id'      => 'ivole_product_feed',
            'default' => 'no',
            'type'    => 'checkbox'
          ),
          array(
    				'id'       => 'ivole_product_feed_variations',
    				'title'    => __( 'Product Variants', IVOLE_TEXT_DOMAIN ),
    				'desc'     => __( 'Include product variants in the XML product feed', IVOLE_TEXT_DOMAIN ),
    				'desc_tip' => __( 'If you sell variable products, enable this option to include product variations in the XML product feed.', IVOLE_TEXT_DOMAIN ),
    				'default'  => 'no',
    				'type'     => 'checkbox'
    			),
          array(
    				'id'       => 'ivole_product_feed_file_url',
    				'title'    => __( 'Product Feed URL', IVOLE_TEXT_DOMAIN ),
    				'type'     => 'product_feed_file_url',
    				'desc'     => __( 'URL of the file with the product feed that should be maintained in Google Merchant Center.', IVOLE_TEXT_DOMAIN ),
    				'desc_tip' => true,
    				'css'      => 'width: 500px;max-width:100%;'
    			),
          array(
    				'id'       => 'ivole_google_generate_xml_feed',
    				'title'    => __( 'Generate Product Review Feed', IVOLE_TEXT_DOMAIN ),
    				'desc'     => __( 'Generate XML Product Review Feed for Google Shopping', IVOLE_TEXT_DOMAIN ),
    				'desc_tip' => __( 'When active, an XML file with product reviews for Google Shopping will be generated immediately after saving settings and then updated every 24 hours.', IVOLE_TEXT_DOMAIN ),
    				'default'  => 'no',
    				'type'     => 'checkbox'
    			),
          array(
    				'id'       => 'ivole_feed_file_url',
    				'title'    => __( 'Product Review Feed URL', IVOLE_TEXT_DOMAIN ),
    				'type'     => 'feed_file_url',
    				'desc'     => __( 'URL of the file with the product reviews feed that should be maintained in Google Merchant Center.', IVOLE_TEXT_DOMAIN ),
    				'desc_tip' => true,
    				'css'      => 'width: 500px;max-width:100%;'
    			),
          array(
    				'id'       => 'ivole_product_feed_status',
    				'title'    => __( 'Status', IVOLE_TEXT_DOMAIN ),
            'is_option' => false,
    				'type'     => 'product_feed_status',
    				'desc'     => __( 'Information about the product categories and product identifiers.', IVOLE_TEXT_DOMAIN ),
    				'desc_tip' => true
    			),
          array(
              'type' => 'sectionend',
              'id'   => 'cr_status'
          )
      );
      return $tbadges;
    }

    public function is_this_tab() {
      return $this->product_feed_menu->is_this_page() && ( $this->product_feed_menu->get_current_tab() === $this->tab );
    }

    public function display_product_feed_file_url( $value ) {
  		$tmp = Ivole_Admin::ivole_get_field_description( $value );
  		$tooltip_html = $tmp['tooltip_html'];
  		$upload_url = wp_upload_dir();
      if( !$value['value'] ) {
        $value['value'] = '/cr/product_feed_' . uniqid() . '.xml';
      }
      ?>
      <tr valign="top">
  			<th scope="row" class="titledesc">
  				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
  				<?php echo $tooltip_html; ?>
  			</th>
  			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
  				<input
  					type="text"
  					style="<?php echo esc_attr( $value['css'] ); ?>"
  					class="<?php echo esc_attr( $value['class'] ); ?>"
  					readonly
  					value="<?php echo $upload_url['baseurl'] . $value['value']; ?>"
  					/>
          <input
            type="hidden"
            id="<?php echo esc_attr( $value['id'] ); ?>"
            name="<?php echo esc_attr( $value['id'] ); ?>"
            value="<?php echo $value['value']; ?>"
            />
  			</td>
  		</tr>
  		<?php
  	}

    public function display_feed_file_url( $value ) {
  		$tmp = Ivole_Admin::ivole_get_field_description( $value );
  		$tooltip_html = $tmp['tooltip_html'];
  		$upload_url = wp_upload_dir();
      ?>
      <tr valign="top">
  			<th scope="row" class="titledesc">
  				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
  				<?php echo $tooltip_html; ?>
  			</th>
  			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
  				<input
  					name="<?php echo esc_attr( $value['id'] ); ?>"
  					id="<?php echo esc_attr( $value['id'] ); ?>"
  					type="text"
  					style="<?php echo esc_attr( $value['css'] ); ?>"
  					class="<?php echo esc_attr( $value['class'] ); ?>"
  					readonly
  					value="<?php echo $upload_url['baseurl'] . '/cr/product_reviews.xml'; ?>"
  					/>
  			</td>
  		</tr>
  		<?php
  	}

    public function display_product_feed_status( $value ) {
  		$tmp = Ivole_Admin::ivole_get_field_description( $value );
  		$tooltip_html = $tmp['tooltip_html'];

      //count categories
      $args_cat = array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false
      );
      $categories = get_categories( $args_cat );
      $cat_total = 0;
      $cat_exclude = 0;
      $cat_mapped = 0;
      if( $categories && is_array( $categories ) ) {
        $cat_total = count( $categories );
      }
      $categories_mapping = get_option( 'ivole_product_feed_categories', array() );
      $categories_exclude = get_option( 'ivole_product_feed_categories_exclude', array() );
      $categories_included = array();
      foreach ($categories as $cat) {
        if( in_array( strval( $cat->term_id ), $categories_exclude, true ) ) {
          $cat_exclude++;
          continue;
        }
        $categories_included[] = $cat->slug;
        if( isset( $categories_mapping[$cat->term_id] ) && $categories_mapping[$cat->term_id] > 0 ) {
          $cat_mapped++;
          continue;
        }
      }
      $cat_total = $cat_total - $cat_exclude;

      //count product identifiers
      $inde_total = 0;
      $inde_mapped = 0;
      $identifiers = get_option( 'ivole_product_feed_identifiers', array(
  			'pid'   => '',
  			'gtin'  => '',
  			'mpn'   => '',
  			'brand' => ''
  		) );
      $inde_total = count( $identifiers );
      $inde_mapped = count( array_filter( $identifiers ) );

      //count product identifiers (reviews)
      $indr_total = 0;
      $indr_mapped = 0;
      $identifiers_reviews = get_option( 'ivole_google_field_map', array(
  			'gtin'  => '',
  			'mpn'   => '',
  			'sku'   => '',
  			'brand' => ''
  		) );
      $indr_total = count( $identifiers_reviews );
      $indr_mapped = count( array_filter( $identifiers_reviews ) );

      //count products
      $prod_total = 0;
      $prod_incl = 0;
      $prod_gtin = 0;
      $prod_mpn = 0;
      $prod_brand = 0;
      $args = array(
        'status' => 'publish'
      );
      $products = wc_get_products( $args );
      if( $products && is_array( $products ) ) {
        $prod_total = count( $products );
      }
      $args = array(
        'status' => 'publish',
        'category' => $categories_included
      );
      $products = wc_get_products( $args );
      if( $products && is_array( $products ) ) {
        $prod_incl = count( $products );
      }
      foreach ($products as $product) {
        if( is_array( $identifiers ) ) {
          if( isset( $identifiers['gtin'] ) ) {
            $gtin = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['gtin'], $product );
    				if( $gtin ) {
    					$prod_gtin++;
    				}
          }
          if( isset( $identifiers['mpn'] ) ) {
            $mpn = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['mpn'], $product );
    				if( $mpn ) {
    					$prod_mpn++;
    				}
          }
          if( isset( $identifiers['brand'] ) ) {
            $brand = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['brand'], $product );
    				if( $brand ) {
    					$prod_brand++;
    				}
          }
        }
      }

      //count variations
      $var_total = 0;
      $var_incl = 0;
      $var_gtin = 0;
      $var_mpn = 0;
      $var_brand = 0;
      $args = array(
        'status' => 'publish',
        'type' => 'variable'
      );
      $products = wc_get_products( $args );
      if( $products && is_array( $products ) ) {
        foreach ($products as $product) {
          $available_variations = $product->get_children();
          $var_total += count( $available_variations );
        }
        $args_incl = array(
          'status' => 'publish',
          'type' => 'variable',
          'category' => $categories_included
        );
        $products_incl = wc_get_products( $args_incl );
        foreach ($products_incl as $product_incl) {
          $available_variations_incl = $product_incl->get_children();
          $var_incl += count( $available_variations_incl );
          if( is_array( $identifiers ) ) {
            foreach ($available_variations_incl as $available_variation_incl) {
              $temp_variation = wc_get_product( $available_variation_incl );
              if( $temp_variation ) {
                if( isset( $identifiers['gtin'] ) ) {
                  $gtin = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['gtin'], $temp_variation );
          				if( $gtin ) {
          					$var_gtin++;
          				}
                }
                if( isset( $identifiers['mpn'] ) ) {
                  $mpn = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['mpn'], $temp_variation );
          				if( $mpn ) {
          					$var_mpn++;
          				}
                }
                if( isset( $identifiers['brand'] ) ) {
                  $brand = CR_Google_Shopping_Prod_Feed::get_field( $identifiers['brand'], $temp_variation );
          				if( $brand ) {
          					$var_brand++;
          				}
                }
              }
            }
          }
        }
      }
      ?>
      <tr valign="top">
  			<th scope="row" class="titledesc">
  				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
  			</th>
  			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
          <table class="wc_status_table widefat cr-product-feed-status" cellspacing="0" id="status">
            <tbody>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product categories mapped, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $cat_mapped, $cat_total, $cat_exclude ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'WooCommerce product categories should be mapped to Google categories on "Categories" tab.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $cat_mapped == $cat_total ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product identifiers mapped', IVOLE_TEXT_DOMAIN ), $inde_mapped, $inde_total ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'WooCommerce product fields should be mapped to Google product identifiers on "Product Identifiers" tab.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $inde_mapped == $inde_total ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product identifiers (reviews) mapped', IVOLE_TEXT_DOMAIN ), $indr_mapped, $indr_total ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'WooCommerce product fields should be mapped to Google product identifiers on "Reviews" tab.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $indr_mapped == $indr_total ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> products have GTIN, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $prod_gtin, $prod_incl, $prod_total - $prod_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, GTIN should be maintained in product details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $prod_gtin == $prod_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> products have MPN, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $prod_mpn, $prod_incl, $prod_total - $prod_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, MPN should be maintained in product details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $prod_mpn == $prod_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> products have Brand, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $prod_brand, $prod_incl, $prod_total - $prod_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, Brand should be maintained in product details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $prod_brand == $prod_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product variations have GTIN, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $var_gtin, $var_incl, $var_total - $var_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, GTIN should be maintained in product variation details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $var_gtin == $var_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product variations have MPN, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $var_mpn, $var_incl, $var_total - $var_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, MPN should be maintained in product variation details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $var_mpn == $var_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php printf( __( '<strong>%1d / %2d</strong> product variations have Brand, <strong>%3d</strong> excluded', IVOLE_TEXT_DOMAIN ), $var_brand, $var_incl, $var_total - $var_incl ); ?></td>
                    <td class="help"><?php echo Ivole_Admin::ivole_wc_help_tip( __( 'If available, Brand should be maintained in product variation details.', IVOLE_TEXT_DOMAIN ) ); ?></td>
                    <td>
                      <?php if ( $var_brand == $var_incl ) : ?>
                          <mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
                      <?php else : ?>
                          <mark class="no"><span class="dashicons dashicons-warning"></span></mark>
                      <?php endif; ?>
                    </td>
                </tr>
            </tbody>
          </table>
  			</td>
  		</tr>
  		<?php
  	}
}

endif;
