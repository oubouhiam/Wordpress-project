<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Product_Fields' ) ) :

	class CR_Product_Fields {

		private $gtin = false;
		private $mpn = false;
		private $brand = false;
		private $identifier_exists = false;

	  public function __construct() {
			$this->gtin = ( 'yes' === get_option( 'ivole_product_feed_enable_gtin', 'no' ) );
			$this->mpn = ( 'yes' === get_option( 'ivole_product_feed_enable_mpn', 'no' ) );
			$this->brand = ( 'yes' === get_option( 'ivole_product_feed_enable_brand', 'no' ) );
			$this->identifier_exists = ( 'yes' === get_option( 'ivole_product_feed_enable_identifier_exists', 'no' ) );

			if( $this->gtin || $this->mpn || $this->brand ) {
				add_action( 'woocommerce_product_options_sku', array( $this, 'display_fields' ) );
				add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
				add_action( 'woocommerce_variation_options_pricing', array( $this, 'display_fields_variation'), 10, 3 );
				add_action( 'woocommerce_variation_options', array( $this, 'display_fields_variation_options'), 10, 3 );
				add_action( 'woocommerce_admin_process_variation_object', array( $this, 'save_fields_variation' ), 10, 2 );
			}
	  }

		public function display_fields() {
			global $product_object;
			if( $this->gtin ) {
				woocommerce_wp_text_input(
					array(
						'id'          => '_cr_gtin',
						'value'       => $product_object->get_meta( '_cr_gtin', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Global Trade Item Number', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'GTIN', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'GTIN refers to a Global Trade Item Number, a globally unique number used to identify trade items, products, or services that can be purchased. GTIN is also an umbrella term that refers to UPC, EAN, JAN, and ISBN.', IVOLE_TEXT_DOMAIN ),
					)
				);
			}
			if( $this->mpn ) {
				woocommerce_wp_text_input(
					array(
						'id'          => '_cr_mpn',
						'value'       => $product_object->get_meta( '_cr_mpn', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Manufacturer Part Number', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'MPN', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'MPN refers to a Manufacturer Part Number, a number that uniquely identifies the product to its manufacturer.', IVOLE_TEXT_DOMAIN ),
					)
				);
			}
			if( $this->brand ) {
				woocommerce_wp_text_input(
					array(
						'id'          => '_cr_brand',
						'value'       => $product_object->get_meta( '_cr_brand', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Brand', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'Brand', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'The brand of the product.', IVOLE_TEXT_DOMAIN ),
					)
				);
			}
			if( $this->identifier_exists ) {
				woocommerce_wp_checkbox(
					array(
						'id'          => '_cr_identifier_exists',
						'value'       => $product_object->get_meta( '_cr_identifier_exists', true, 'edit' ) ? 'yes' : 'no',
						'label'       => '<abbr title="' . esc_attr__( 'identifier_exists attribute for Google Shopping', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'identifier_exists', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'description' => __( 'Enable the checkbox to add "identifier_exists = no" in Google Shopping feed for this product.', IVOLE_TEXT_DOMAIN ),
					)
				);
			}
		}

		public function save_fields( $product ) {
			if( $this->gtin ) {
				$product->update_meta_data( '_cr_gtin', isset( $_POST['_cr_gtin'] ) ? wc_clean( wp_unslash( $_POST['_cr_gtin'] ) ) : null );
			}
			if( $this->mpn ) {
				$product->update_meta_data( '_cr_mpn', isset( $_POST['_cr_mpn'] ) ? wc_clean( wp_unslash( $_POST['_cr_mpn'] ) ) : null );
			}
			if( $this->brand ) {
				$product->update_meta_data( '_cr_brand', isset( $_POST['_cr_brand'] ) ? wc_clean( wp_unslash( $_POST['_cr_brand'] ) ) : null );
			}
			if( $this->identifier_exists ) {
				$product->update_meta_data( '_cr_identifier_exists', ! empty( $_POST['_cr_identifier_exists'] ) );
			}
		}

		public function display_fields_variation( $loop, $variation_data, $variation ) {
			$variation_object = wc_get_product( $variation->ID );
			$css_class = 'form-row-first';
			if( $this->gtin ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_gtin_var{$loop}",
						'name'        => "_cr_gtin_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_gtin', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Global Trade Item Number', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'GTIN', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'GTIN refers to a Global Trade Item Number, a globally unique number used to identify trade items, products, or services that can be purchased. GTIN is also an umbrella term that refers to UPC, EAN, JAN, and ISBN.', IVOLE_TEXT_DOMAIN ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				$css_class = 'form-row-last';
			}
			if( $this->mpn ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_mpn_var{$loop}",
						'name'        => "_cr_mpn_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_mpn', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Manufacturer Part Number', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'MPN', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'MPN refers to a Manufacturer Part Number, a number that uniquely identifies the product to its manufacturer.', IVOLE_TEXT_DOMAIN ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
				if( 'form-row-last' === $css_class ) {
					$css_class = 'form-row-first';
				} else {
					$css_class = 'form-row-last';
				}
			}
			if( $this->brand ) {
				woocommerce_wp_text_input(
					array(
						'id'          => "_cr_brand_var{$loop}",
						'name'        => "_cr_brand_var[{$loop}]",
						'value'       => $variation_object->get_meta( '_cr_brand', true, 'edit' ),
						'label'       => '<abbr title="' . esc_attr__( 'Brand', IVOLE_TEXT_DOMAIN ) . '">' . esc_html__( 'Brand', IVOLE_TEXT_DOMAIN ) . '</abbr>',
						'desc_tip'    => true,
						'description' => __( 'The brand of the product.', IVOLE_TEXT_DOMAIN ),
						'wrapper_class' => 'form-row ' . $css_class
					)
				);
			}
		}
		public function display_fields_variation_options( $loop, $variation_data, $variation ) {
			$variation_object = wc_get_product( $variation->ID );
			if( $this->identifier_exists ) {
				?>
				<label class="tips" data-tip="<?php esc_attr_e( 'Enable the option to add "identifier_exists = no" in Google Shopping feed for this variation.', IVOLE_TEXT_DOMAIN ); ?>">
					<?php esc_html_e( 'identifier_exists', IVOLE_TEXT_DOMAIN ); ?>
					<input type="checkbox" class="checkbox cr_variable_identifier_exists" name="_cr_identifier_exists_var[<?php echo esc_attr( $loop ); ?>]" <?php checked( $variation_object->get_meta( '_cr_identifier_exists', true, 'edit' ), true ); ?> />
				</label>
				<?php
			}
		}
		public function save_fields_variation( $variation, $i ) {
			if( $this->gtin ) {
				$variation->update_meta_data( '_cr_gtin', isset( $_POST['_cr_gtin_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_gtin_var'][$i] ) ) : null );
			}
			if( $this->mpn ) {
				$variation->update_meta_data( '_cr_mpn', isset( $_POST['_cr_mpn_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_mpn_var'][$i] ) ) : null );
			}
			if( $this->brand ) {
				$variation->update_meta_data( '_cr_brand', isset( $_POST['_cr_brand_var'][$i] ) ? wc_clean( wp_unslash( $_POST['_cr_brand_var'][$i] ) ) : null );
			}
			if( $this->identifier_exists ) {
				$variation->update_meta_data( '_cr_identifier_exists', ! empty( $_POST['_cr_identifier_exists_var'][$i] ) );
			}
		}
	}

endif;

?>
