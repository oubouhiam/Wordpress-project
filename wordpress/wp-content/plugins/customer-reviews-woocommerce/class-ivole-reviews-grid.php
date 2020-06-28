<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ivole_Reviews_Grid' ) ) {

/**
 * Class for reviews grid shortcode and block.
 *
 * @since 3.61
 */
final class Ivole_Reviews_Grid {

	private static $sort_order_by;
	private static $sort_order;

	/**
	 * Constructor.
	 *
	 * @since 3.61
	 */
	public function __construct() {
		$this->register_shortcode();

		$shortcode_enabled_grid = get_option( 'ivole_reviews_shortcode', 'no' );
		$shortcode_enabled_tbadge = get_option( 'ivole_reviews_verified', 'no' );

		if( 'no' !== $shortcode_enabled_grid || 'no' !== $shortcode_enabled_tbadge ) {
			add_action( 'init', array( $this, 'ivole_register_blocks_script' ) );
			add_action( 'init', array( $this, 'register_block' ) );
			add_action( 'init', array( Ivole_Trust_Badge::class, 'register_block' ) );
			add_action( 'enqueue_block_assets', array( $this, 'ivole_enqueue_block_scripts' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'maybe_print_wc_settings' ) );
			add_action( 'wp_ajax_ivole_fetch_product_categories', array( $this, 'fetch_product_categories' ) );
			add_action( 'wp_ajax_ivole_fetch_products', array( $this, 'fetch_products' ) );
		}
	}

	/**
	 * Add the cusrev_reviews_grid shortcode.
	 *
	 * @since 3.61
	 */
	public function register_shortcode() {
		add_shortcode( 'cusrev_reviews_grid', array( $this, 'render_reviews_grid_shortcode' ) );
	}

	/**
	 * Register the reviews-grid.
	 *
	 * @since 3.61
	 */
	public function register_block() {
		// Only register the block if the WP is at least 5.0, or gutenberg is installed.
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( 'ivole/cusrev-reviews-grid', array(
				'editor_script' => 'ivole-wc-components',

				'editor_style'  => 'ivole-wc-components',

				'attributes' => array(
					'count' => array(
						'type' => 'number',
						'default' => 3,
						'minimum' => 1,
						'maximum' => 6
					),
					'show_products' => array(
						'type' => 'boolean',
						'default' => true
					),
					'product_links' => array(
						'type' => 'boolean',
						'default' => true
					),
					'sort_by' => array(
						'type' => 'string',
						'enum' => array( 'date', 'rating' ),
						'default' => 'date'
					),
					'sort' => array(
						'type' => 'string',
						'enum' => array( 'ASC', 'DESC', 'RAND' ),
						'default' => 'DESC'
					),
					'categories' => array(
						'type' => 'array',
						'default' => array(),
						'items' => array(
							'type' => 'integer',
							'minimum' => 1
						)
					),
					'products' => array(
						'type' => 'array',
						'default' => array(),
						'items' => array(
							'type' => 'integer',
							'minimum' => 1
						)
					),
					'color_ex_brdr' => array(
						'type' => 'string',
						'default' => '#ebebeb'
					),
					'color_brdr' => array(
						'type' => 'string',
						'default' => '#ebebeb'
					),
					'color_ex_bcrd' => array(
						'type' => 'string',
						'default' => ''
					),
					'color_bcrd' => array(
						'type' => 'string',
						'default' => '#fbfbfb'
					),
					'color_pr_bcrd' => array(
						'type' => 'string',
						'default' => '#f2f2f2'
					),
					'shop_reviews' => array(
						'type' => 'boolean',
						'default' => false
					),
					'count_shop_reviews' => array(
						'type' => 'number',
						'default' => 1,
						'minimum' => 0,
						'maximum' => 3
					),
					'inactive_products' => array(
						'type' => 'boolean',
						'default' => false
					)
				),

				'render_callback' => array( $this, 'render_reviews_grid' )
			) );
		}
	}

	/**
	 * Returns the review grid markup.
	 *
	 * @since 3.61
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_reviews_grid( $attributes ) {
		if ( get_option( 'ivole_reviews_shortcode', 'no' ) === 'no' ) {
      return '';
    }
		$max_reviews = $attributes['count'];
		$order_by = $attributes['sort_by'] === 'date' ? 'comment_date_gmt' : 'rating';
		$order = $attributes['sort'];
		$inactive_products = $attributes['inactive_products'];

		$post_ids = $attributes['products'];
		if ( count( $attributes['categories'] ) > 0 ) {
			$post_ids = get_posts(
				array(
					'post_type' => 'product',
					'posts_per_page' => -1,
					'fields' => 'ids',
					'post__in' => $attributes['products'],
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $attributes['categories']
						),
					)
				)
			);
		}

		$args = array(
			'status'      => 'approve',
			'post_type'   => 'product',
			'meta_key'    => 'rating',
			'orderby'     => $order_by,
			'post__in'    => $post_ids
		);

		if( !$inactive_products ) {
			$args['post_status'] = 'publish';
		}

		$reviews = [];
		if( 'RAND' === $order ) {
			$all_product_reviews = get_comments( $args );
			$count_all_product_reviews = count( $all_product_reviews );
			$max_reviews = ( $count_all_product_reviews < $max_reviews ) ? $count_all_product_reviews : $max_reviews;
			$random_keys = array_rand( $all_product_reviews, $max_reviews );
			if( is_array( $random_keys ) ) {
				for( $i = 0; $i < $max_reviews; $i++ ) {
					$reviews[] = $all_product_reviews[$random_keys[$i]];
				}
			} else {
				$reviews[] = $all_product_reviews[$random_keys];
			}
		} else {
			if( 0 < $max_reviews ) {
				$args['order'] = $order;
				$args['number'] = $max_reviews;
				$reviews = get_comments( $args );
			}
		}

		$shop_page_id = wc_get_page_id( 'shop' );
		if( true === $attributes['shop_reviews'] ) {
			$max_shop_reviews = $attributes['count_shop_reviews'];
			if( $shop_page_id > 0 && $max_shop_reviews > 0 ) {
				$args_s = array(
					'status'      => 'approve',
					'post_status' => 'publish',
					'post_id'			=> $shop_page_id,
					'meta_key'    => 'rating',
					'orderby'     => $order_by
				);
				$shop_reviews = [];
				if( 'RAND' === $order ) {
					$all_shop_reviews = get_comments( $args_s );
					$count_all_shop_reviews = count( $all_shop_reviews );
					if( 0 < $count_all_shop_reviews ) {
						$max_shop_reviews = ( $count_all_shop_reviews < $max_shop_reviews ) ? $count_all_shop_reviews : $max_shop_reviews;
						$random_keys = array_rand( $all_shop_reviews, $max_shop_reviews );
						if( is_array( $random_keys ) ) {
							for( $i = 0; $i < $max_shop_reviews; $i++ ) {
								$shop_reviews[] = $all_shop_reviews[$random_keys[$i]];
							}
						} else {
							$shop_reviews[] = $all_shop_reviews[$random_keys];
						}
					}
				} else {
					if( 0 < $max_shop_reviews ) {
						$args_s['order'] = $order;
						$args_s['number'] = $max_shop_reviews;
						$shop_reviews = get_comments( $args_s );
					}
				}

				if( is_array( $reviews ) && is_array( $shop_reviews ) ) {
					$reviews = array_merge( $reviews, $shop_reviews );
					Ivole_Reviews_Grid::$sort_order_by = $order_by;
					Ivole_Reviews_Grid::$sort_order = $order;
					usort( $reviews, array( "Ivole_Reviews_Grid", "compare_dates_sort" ) );
				}
			}
		}

		$num_reviews = count( $reviews );

		if ( $num_reviews < 1 ) {
			return __( 'No reviews to show', IVOLE_TEXT_DOMAIN );
		}

		$show_products = $attributes['show_products'];
		$product_links = $attributes['product_links'];
		$verified_text = '(' . __( 'verified owner', IVOLE_TEXT_DOMAIN ) . ')';

		$verified_reviews_enabled = false;
		if ( 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ) {
			$verified_reviews_enabled = true;
		}
		$country_enabled = ('yes' === get_option( 'ivole_form_geolocation', 'no' ) ? true : false);

		$badge_link = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/p/p-%s/r-%s';
		$badge = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', __FILE__ ) . '" alt="' . __( 'Verified review', IVOLE_TEXT_DOMAIN ) . '" class="ivole-verified-badge-icon">';
		$badge .= '<span class="ivole-verified-badge-text">';
		$badge .= __( 'Verified review', IVOLE_TEXT_DOMAIN );
		$badge .= ' - <a href="' . $badge_link . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', IVOLE_TEXT_DOMAIN ) . '</a>';
		$badge .= '</span></p>';

		$badge_link_sr = 'https://www.cusrev.com/reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '/s/r-%s';
		$badge_sr = '<p class="ivole-verified-badge"><img src="' . plugins_url( '/img/shield-20.png', __FILE__ ) . '" alt="' . __( 'Verified review', IVOLE_TEXT_DOMAIN ) . '" class="ivole-verified-badge-icon">';
		$badge_sr .= '<span class="ivole-verified-badge-text">';
		$badge_sr .= __( 'Verified review', IVOLE_TEXT_DOMAIN );
		$badge_sr .= ' - <a href="' . $badge_link_sr . '" title="" target="_blank" rel="nofollow noopener">' . __( 'view original', IVOLE_TEXT_DOMAIN ) . '</a>';
		$badge_sr .= '</span></p>';

		$section_style = "border-color:" . $attributes['color_ex_brdr'] . ";";
		if ( ! empty( $attributes['color_ex_bcrd'] ) ) {
			$section_style .= "background-color:" . $attributes['color_ex_bcrd'] . ";";
		}
		$card_style = "border-color:" . $attributes['color_brdr'] . ";";
		$card_style .= "background-color:" . $attributes['color_bcrd'] . ";";
		$product_style = "background-color:" . $attributes['color_pr_bcrd'] . ";";

		$id = uniqid( 'ivole-reviews-grid-' );

		$template = wc_locate_template(
			'reviews-grid.php',
			'customer-reviews-woocommerce',
			'templates/'
		);

		ob_start();
		include( $template );
		return ob_get_clean();
	}

	/**
	 * Returns the review grid markup for a shortcode.
	 *
	 * @since 3.61
	 * @uses Ivole_Reviews_Grid::render_reviews_grid
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_reviews_grid_shortcode( $attributes ) {
		$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
		if( $shortcode_enabled === 'no' ) {
			return;
		} else {
			// Convert shortcode attributes to block attributes
			$attributes = shortcode_atts( array(
				'count' => 3,
				'show_products' => true,
				'product_links' => true,
				'sort_by' => 'date',
				'sort' => 'DESC',
				'categories' => array(),
				'products' => array(),
				'color_ex_brdr' => '#ebebeb',
				'color_brdr' => '#ebebeb',
				'color_ex_bcrd' => '',
				'color_bcrd' => '#fbfbfb',
				'color_pr_bcrd' => '#f2f2f2',
				'shop_reviews' => 'false',
				'count_shop_reviews' => 1,
				'inactive_products' => 'false'
			), $attributes, 'cusrev_reviews_grid' );

			$attributes['count'] = absint( $attributes['count'] );
			$attributes['show_products'] = ( $attributes['show_products'] !== 'false' && boolval( $attributes['count'] ) );
			$attributes['product_links'] = ( $attributes['product_links'] !== 'false' );
			$attributes['shop_reviews'] = ( $attributes['shop_reviews'] !== 'false' && boolval( $attributes['count_shop_reviews'] ) );
			$attributes['count_shop_reviews'] = absint( $attributes['count_shop_reviews'] );
			$attributes['inactive_products'] = ( $attributes['inactive_products'] === 'true' );

			if ( ! is_array( $attributes['categories'] ) ) {
				$attributes['categories'] = array_filter( array_map( 'trim', explode( ',', $attributes['categories'] ) ) );
			}

			if ( ! is_array( $attributes['products'] ) ) {
				$attributes['products'] = array_filter( array_map( 'trim', explode( ',', $attributes['products'] ) ) );
			}

			return $this->render_reviews_grid( $attributes );
		}
	}

	/**
	 * When displaying the block editor, check for WooCommerce support then set
	 * an action to print wc data.
	 *
	 * @since 3.61
	 */
	public function maybe_print_wc_settings() {
		if ( ! function_exists( 'wc_get_theme_support' ) ) {
			return;
		}

		add_action( 'admin_print_footer_scripts', array( $this, 'print_settings' ), 1 );
	}

	/**
	 * Print JS variables to the editor page with WC data.
	 *
	 * @since 3.61
	 */
	public function print_settings() {
		global $wp_locale;

		$code = get_woocommerce_currency();

		// NOTE: wcSettings is not used directly, it's only for @woocommerce/components
		//
		// Settings and variables can be passed here for access in the app.
		// Will need `wcAdminAssetUrl` if the ImageAsset component is used.
		// Will need `dataEndpoints.countries` if Search component is used with 'country' type.
		// Will need `orderStatuses` if the OrderStatus component is used.
		// Deliberately excluding: `embedBreadcrumbs`, `trackingEnabled`.
		$settings = array(
			'adminUrl'      => admin_url(),
			'wcAssetUrl'    => plugins_url( 'assets/', WC_PLUGIN_FILE ),
			'siteLocale'    => esc_attr( get_bloginfo( 'language' ) ),
			'currency'      => array(
				'code'      => $code,
				'precision' => wc_get_price_decimals(),
				'symbol'    => get_woocommerce_currency_symbol( $code ),
				'position'  => get_option( 'woocommerce_currency_pos' ),
			),
			'stockStatuses' => wc_get_product_stock_status_options(),
			'siteTitle'     => get_bloginfo( 'name' ),
			'dataEndpoints' => array(),
			'l10n'          => array(
				'userLocale'    => get_user_locale(),
				'weekdaysShort' => array_values( $wp_locale->weekday_abbrev ),
			),
		);

		// Global settings used in each block.
		$block_settings = array(
			'min_columns'       => wc_get_theme_support( 'product_grid::min_columns', 1 ),
			'max_columns'       => wc_get_theme_support( 'product_grid::max_columns', 6 ),
			'default_columns'   => wc_get_default_products_per_row(),
			'min_rows'          => wc_get_theme_support( 'product_grid::min_rows', 1 ),
			'max_rows'          => wc_get_theme_support( 'product_grid::max_rows', 6 ),
			'default_rows'      => wc_get_default_product_rows_per_page(),
			'placeholderImgSrc' => wc_placeholder_img_src(),
			'min_height'        => wc_get_theme_support( 'featured_block::min_height', 500 ),
			'default_height'    => wc_get_theme_support( 'featured_block::default_height', 500 ),
		);

		?>
		<script type="text/javascript">
			var wcSettings = wcSettings || <?php echo wp_json_encode( $settings ); ?>;
			var wc_product_block_data = <?php echo wp_json_encode( $block_settings ); ?>;
		</script>
		<?php
	}

	/**
	 * Fetch the product categories for use by the reviews grid block settings.
	 *
	 * @since 3.61
	 */
	public function fetch_product_categories() {
		$prepared_args = array(
			'exclude'    => [],
			'include'    => [],
			'order'      => 'asc',
			'orderby'    => 'name',
			'product'    => null,
			'hide_empty' => false,
			'number'     => 100,
			'offset'     => 0
		);

		$query_result = get_terms( 'product_cat', $prepared_args );

		$response = array();
		foreach ( $query_result as $term ) {
			$response[] = array(
				'id'     => (int) $term->term_id,
				'name'   => $term->name,
				'slug'   => $term->slug,
				'parent' => (int) $term->parent,
				'count'  => (int) $term->count
			);
		}

		wp_send_json( $response, 200 );
	}

	/**
	 * Fetch the product categories for use by the reviews grid block settings.
	 *
	 * @since 3.61
	 */
	public function fetch_products() {
		$query_args = array(
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post_type'      => 'product',
			'orderby'        => 'date ID'
		);

		$query = new WP_Query();
		$products = $query->query( $query_args );
		$products = array_map( 'wc_get_product', $products );

		$response = array();
		foreach ( $products as $product ) {
			$response[] = array(
				'id'   => $product->get_id(),
				'name' => $product->get_name(),
				'slug' => $product->get_slug()
			);
		}

		wp_send_json( $response );
	}

	public function ivole_register_blocks_script() {
		wp_register_script(
			'ivole-blocks',
			plugins_url( 'js/blocks.js', __FILE__ ),
			array( 'wp-element', 'wp-i18n', 'wp-data', 'wp-blocks', 'wp-components', 'wp-editor', 'lodash', 'ivole-wc-components' ),
			'3.61',
			true
		);

		wp_register_script(
			'ivole-wc-components',
			plugins_url( 'js/wc-components.js', __FILE__ ),
			array(
				'wp-components',
				'wp-data',
				'wp-element',
				'wp-hooks',
				'wp-i18n',
				'wp-keycodes'
			),
			'3.61',
			true
		);

		wp_register_style(
			'ivole-wc-components',
			plugins_url( 'css/wc-components.css', __FILE__ ),
			array(),
			'3.61'
		);

		wp_register_style(
			'ivole-reviews-grid',
			plugins_url( 'css/reviews-grid.css', __FILE__ ),
			array(),
			'3.61'
		);
	}

	public function ivole_enqueue_block_scripts() {
		global $current_screen;

		wp_register_style( 'ivole-frontend-css', plugins_url( '/css/frontend.css', __FILE__ ), array(), null, 'all' );
		wp_enqueue_style( 'ivole-frontend-css' );

		if ( ( $current_screen instanceof WP_Screen ) && $current_screen->is_block_editor() ) {
			wp_enqueue_script( 'ivole-blocks' );
		}

		wp_enqueue_style( 'ivole-reviews-grid' );
	}

	public function ivole_enqueue_block_scripts_tbadge() {
		global $current_screen;

		wp_register_style( 'ivole-frontend-css', plugins_url( '/css/frontend.css', __FILE__ ), array(), null, 'all' );
		wp_enqueue_style( 'ivole-frontend-css' );

		if ( ( $current_screen instanceof WP_Screen ) && $current_screen->is_block_editor() ) {
			wp_enqueue_script( 'ivole-blocks' );
		}
	}

	private static function compare_dates_sort( $a, $b ) {
		if( 'rating' === Ivole_Reviews_Grid::$sort_order_by ) {
			$rating1 = intval( get_comment_meta( $a->comment_ID, 'rating', true ) );
			$rating2 = intval( get_comment_meta( $b->comment_ID, 'rating', true ) );
			if( 'ASC' === Ivole_Reviews_Grid::$sort_order ) {
				return $rating1 - $rating2;
			} elseif( 'RAND' === Ivole_Reviews_Grid::$sort_order ) {
				return rand( -1, 1 );
			} else {
				return $rating2 - $rating1;
			}
		} else {
			$datetime1 = strtotime( $a->comment_date );
			$datetime2 = strtotime( $b->comment_date );
			if( 'ASC' === Ivole_Reviews_Grid::$sort_order ) {
				return $datetime1 - $datetime2;
			}  elseif( 'RAND' === Ivole_Reviews_Grid::$sort_order ) {
				return rand( -1, 1 );
			} else {
				return $datetime2 - $datetime1;
			}
		}
	}

}

}
