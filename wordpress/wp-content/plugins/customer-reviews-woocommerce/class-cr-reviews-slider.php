<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Reviews_Slider' ) ) {

/**
 * Class for reviews slider shortcode and block.
 *
 * @since 3.61
 */
final class CR_Reviews_Slider {

	private static $sort_order_by;
	private static $sort_order;

	/**
	 * Constructor.
	 *
	 * @since 3.61
	 */
	public function __construct() {
		$this->register_shortcode();

		$shortcode_enabled_slider = get_option( 'ivole_reviews_shortcode', 'no' );
		$shortcode_enabled_tbadge = get_option( 'ivole_reviews_verified', 'no' );

		if( 'no' !== $shortcode_enabled_slider || 'no' !== $shortcode_enabled_tbadge ) {
			add_action( 'init', array( $this, 'register_slider_script' ) );
			add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_scripts' ) );
		}
	}

	public function register_shortcode() {
		add_shortcode( 'cusrev_reviews_slider', array( $this, 'render_reviews_slider_shortcode' ) );
	}

	public function render_reviews_slider( $attributes ) {
		if ( get_option( 'ivole_reviews_shortcode', 'no' ) === 'no' ) {
      return '';
    }
		wp_enqueue_script( 'cr-reviews-slider' );
		$max_reviews = $attributes['count'];
		$order_by = $attributes['sort_by'] === 'date' ? 'comment_date_gmt' : 'rating';
		$order = $attributes['sort'];
		$inactive_products = $attributes['inactive_products'];
		$avatars = $attributes['avatars'];

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
					CR_Reviews_Slider::$sort_order_by = $order_by;
					CR_Reviews_Slider::$sort_order = $order;
					usort( $reviews, array( "CR_Reviews_Slider", "compare_dates_sort" ) );
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
		$stars_style = "color:" . $attributes['color_stars'] . ";";

		// slider settings for JS
		$slider_settings = array(
			'infinite'          => true,
			'dots'              => true,
			'slidesToShow'      => $attributes['slides_to_show'],
			'slidesToScroll'    => 1,
			'respondTo'					=> 'min',
			'adaptiveHeight'    => true,
			'autoplay'          => $attributes['autoplay'],
			'responsive'        => array(
				array(
					'breakpoint'    => 800,
					'settings'      => array(
						'slidesToShow'   => 2
					)
				),
				array(
					'breakpoint'    => 650,
					'settings'      => array(
						'slidesToShow'   => 1
					)
				),
				array(
					'breakpoint'    => 450,
					'settings'      => array(
						'arrows'				 => false,
						'slidesToShow'   => 1
					)
				)
			)
		);

		$id = uniqid( 'cr-reviews-slider-' );

		$template = wc_locate_template(
			'reviews-slider.php',
			'customer-reviews-woocommerce',
			'templates/'
		);
		ob_start();
		include( $template );
		return ob_get_clean();
	}

	public function render_reviews_slider_shortcode( $attributes ) {
		$shortcode_enabled = get_option( 'ivole_reviews_shortcode', 'no' );
		if( $shortcode_enabled === 'no' ) {
			return;
		} else {
			// Convert shortcode attributes
			$attributes = shortcode_atts( array(
				'slides_to_show' => 3,
				'count' => 5,
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
				'color_stars' => '#6bba70',
				'shop_reviews' => 'false',
				'count_shop_reviews' => 1,
				'inactive_products' => false,
				'autoplay' => false,
				'avatars' => true
			), $attributes, 'cusrev_reviews_slider' );

			$attributes['slides_to_shows'] = absint( $attributes['slides_to_show'] ) >= absint( $attributes['count'] ) ? absint( $attributes['count'] ) : absint( $attributes['slides_to_show'] );
			$attributes['count'] = absint( $attributes['count'] );
			$attributes['show_products'] = ( $attributes['show_products'] !== 'false' && boolval( $attributes['count'] ) );
			$attributes['product_links'] = ( $attributes['product_links'] !== 'false' );
			$attributes['shop_reviews'] = ( $attributes['shop_reviews'] !== 'false' && boolval( $attributes['count_shop_reviews'] ) );
			$attributes['count_shop_reviews'] = absint( $attributes['count_shop_reviews'] );
			$attributes['inactive_products'] = ( $attributes['inactive_products'] === 'true' );
			$attributes['autoplay'] = ( $attributes['autoplay'] === 'true' );
			$attributes['avatars'] = ( $attributes['avatars'] !== 'false' );

			if ( ! is_array( $attributes['categories'] ) ) {
				$attributes['categories'] = array_filter( array_map( 'trim', explode( ',', $attributes['categories'] ) ) );
			}

			if ( ! is_array( $attributes['products'] ) ) {
				$attributes['products'] = array_filter( array_map( 'trim', explode( ',', $attributes['products'] ) ) );
			}

			if( $attributes['slides_to_shows'] <= 0 ) {
				$attributes['slides_to_shows'] = 1;
			}

			return $this->render_reviews_slider( $attributes );
		}
	}

	public function register_slider_script() {
		wp_register_script(
			'cr-reviews-slider',
			plugins_url( 'js/slick.min.js', __FILE__ ),
			array( 'jquery' ),
			'3.106',
			true
		);
		wp_register_style(
			'ivole-reviews-grid',
			plugins_url( 'css/reviews-grid.css', __FILE__ ),
			array(),
			'3.61'
		);
	}

	public function enqueue_block_scripts() {
		global $current_screen;
		wp_enqueue_style( 'ivole-reviews-grid' );
	}

	private static function compare_dates_sort( $a, $b ) {
		if( 'rating' === CR_Reviews_Slider::$sort_order_by ) {
			$rating1 = intval( get_comment_meta( $a->comment_ID, 'rating', true ) );
			$rating2 = intval( get_comment_meta( $b->comment_ID, 'rating', true ) );
			if( 'ASC' === CR_Reviews_Slider::$sort_order ) {
				return $rating1 - $rating2;
			} elseif( 'RAND' === CR_Reviews_Slider::$sort_order ) {
				return rand( -1, 1 );
			} else {
				return $rating2 - $rating1;
			}
		} else {
			$datetime1 = strtotime( $a->comment_date );
			$datetime2 = strtotime( $b->comment_date );
			if( 'ASC' === CR_Reviews_Slider::$sort_order ) {
				return $datetime1 - $datetime2;
			}  elseif( 'RAND' === CR_Reviews_Slider::$sort_order ) {
				return rand( -1, 1 );
			} else {
				return $datetime2 - $datetime1;
			}
		}
	}

}

}
