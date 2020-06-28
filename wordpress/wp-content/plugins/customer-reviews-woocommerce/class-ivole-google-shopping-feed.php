<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for generating Google Shopping Reviews XML feed
 *
 * @since 3.47
 */
class Ivole_Google_Shopping_Feed {

	const FEED_FILE = 'product_reviews.xml';

	/**
	 * @var string The path to the feed file
	 */
	private $file_path;

	/**
	 * @var array Full mapping of feed fields to woocommerce fields
	 */
	private $field_map;

	public function __construct( $field_map = array() ) {
		$this->file_path = IVOLE_CONTENT_DIR . '/' . self::FEED_FILE;
		$this->field_map = $field_map;
	}

	/**
	 * Generates the XML feed file
	 *
	 * @since 3.47
	 */
	public function generate() {
		$reviews = $this->get_review_data();

		// Exit if there are no reviews
		if ( count( $reviews ) < 1 ) {
			return;
		}

		// Exit if XML library is not available
		if( ! class_exists( 'XMLWriter' ) ) {
			return;
		}

		$xml_writer = new XMLWriter();
		$xml_writer->openURI( $this->file_path );
		if( !$xml_writer ) {
			//no write access in the folder
			return;
		}
		$xml_writer->setIndent( true );
		$xml_writer->startDocument( '1.0', 'UTF-8' );

		// <feed>
		$xml_writer->startElement( 'feed' );
		$xml_writer->startAttribute( 'xmlns:vc' );
		$xml_writer->text( 'http://www.w3.org/2007/XMLSchema-versioning' );
		$xml_writer->endAttribute();
		$xml_writer->startAttribute( 'xmlns:xsi' );
		$xml_writer->text( 'http://www.w3.org/2001/XMLSchema-instance' );
		$xml_writer->endAttribute();
		$xml_writer->startAttribute( 'xsi:noNamespaceSchemaLocation' );
		$xml_writer->text( 'http://www.google.com/shopping/reviews/schema/product/2.2/product_reviews.xsd' );
		$xml_writer->endAttribute();
		// <version>
		$xml_writer->startElement( 'version' );
		$xml_writer->text( '2.2' );
		$xml_writer->endElement();
		// <aggregator>
		$xml_writer->startElement( 'aggregator' );
		// <name>
		$xml_writer->startElement( 'name' );
		$xml_writer->text( 'CR' );
		$xml_writer->endElement();
		$xml_writer->endElement();
		// <publisher>
		$xml_writer->startElement( 'publisher' );
		// <name>
		$xml_writer->startElement( 'name' );
		$blog_name = get_option( 'ivole_shop_name', '' );
		$blog_name = empty( $blog_name ) ? get_option( 'blogname' ) : $blog_name;
		$xml_writer->text( $blog_name );
		$xml_writer->endElement();
		$xml_writer->endElement();

		// <reviews>
		$xml_writer->startElement( 'reviews' );
		foreach ( $reviews as $review ) {
			// <review>
			$xml_writer->startElement( 'review' );

			// <review_id>
			$xml_writer->startElement( 'review_id' );
			$xml_writer->text( $review->id );
			$xml_writer->endElement();

			// <reviewer>
			$xml_writer->startElement( 'reviewer' );
			// <name>
			$xml_writer->startElement( 'name' );
			$xml_writer->startAttribute( 'is_anonymous' );
			$xml_writer->text( $review->is_anon ? 'true': 'false' );
			$xml_writer->endAttribute();
			$xml_writer->text( $review->author );
			$xml_writer->endElement();
			$xml_writer->endElement();

			// <review_timestamp>
			$xml_writer->startElement( 'review_timestamp' );
			$xml_writer->text( $review->date );
			$xml_writer->endElement();

			// <content>
			$xml_writer->startElement( 'content' );
			$xml_writer->text( $review->content );
			$xml_writer->endElement();

			// <review_url>
			$xml_writer->startElement( 'review_url' );
			$xml_writer->startAttribute( 'type' );
			$xml_writer->text( 'group' );
			$xml_writer->endAttribute();
			$xml_writer->text( get_permalink( $review->post_id ) );
			$xml_writer->endElement();

			if ( count( $review->images ) > 0 ) {
				// <reviewer_images>
				$xml_writer->startElement( 'reviewer_images' );

				foreach ( $review->images as $image_url ) {
					// <reviewer_image>
					$xml_writer->startElement( 'reviewer_image' );
					$xml_writer->startElement( 'url' );
					$xml_writer->text( $image_url );
					$xml_writer->endElement();
					$xml_writer->endElement();
				}

				$xml_writer->endElement();
			}

			// <ratings>
			$xml_writer->startElement( 'ratings' );
			// <overall>
			$xml_writer->startElement( 'overall' );
			$xml_writer->startAttribute( 'min' );
			$xml_writer->text( '1' );
			$xml_writer->endAttribute();
			$xml_writer->startAttribute( 'max' );
			$xml_writer->text( '5' );
			$xml_writer->endAttribute();
			$xml_writer->text( $review->rating );
			$xml_writer->endElement();
			$xml_writer->endElement();

			// <products>
			$xml_writer->startElement( 'products' );
			// <product>
			$xml_writer->startElement( 'product' );
			// <product_ids>
			$xml_writer->startElement( 'product_ids' );

			if ( ! empty( $review->gtins ) ) {
				$xml_writer->startElement( 'gtins' );
				foreach( $review->gtins as $gtin ) {
					$xml_writer->startElement( 'gtin' );
					$xml_writer->text( $gtin );
					$xml_writer->endElement();
				}
				$xml_writer->endElement();
			}

			if ( ! empty( $review->mpns ) ) {
				$xml_writer->startElement( 'mpns' );
				foreach( $review->mpns as $mpn ) {
					$xml_writer->startElement( 'mpn' );
					$xml_writer->text( $mpn );
					$xml_writer->endElement();
				}
				$xml_writer->endElement();
			}

			if ( ! empty( $review->skus ) ) {
				$xml_writer->startElement( 'skus' );
				foreach( $review->skus as $sku ) {
					$xml_writer->startElement( 'sku' );
					$xml_writer->text( $sku );
					$xml_writer->endElement();
				}
				$xml_writer->endElement();
			}

			if ( ! empty( $review->brands ) ) {
				$xml_writer->startElement( 'brands' );
				foreach( $review->brands as $brand ) {
					$xml_writer->startElement( 'brand' );
					$xml_writer->text( $brand );
					$xml_writer->endElement();
				}
				$xml_writer->endElement();
			}

			$xml_writer->endElement(); // </product_ids>
			// <product_url>
			$xml_writer->startElement( 'product_url' );
			$xml_writer->text( get_permalink( $review->post_id ) );
			$xml_writer->endElement();

			$xml_writer->endElement(); // <product>
			$xml_writer->endElement(); // </products>

			$xml_writer->endElement(); // </review>
		}

		$xml_writer->endElement(); // </reviews>
		$xml_writer->endElement(); // </feed>

		$xml_writer->endDocument();
		$xml_writer->flush();
		unset( $xml_writer );
	}

	/**
	 * Fetches reviews to include in the feed.
	 *
	 * @since 3.47
	 *
	 * @return array
	 */
	protected function get_review_data() {
		global $wpdb;
		$reviews = array();
		//WPML integration
		//fetch IDs of reviews (cannot use get_comments due to WPML that adds a hook to filter comments by language)
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$query_ids = "SELECT comms.comment_ID FROM $wpdb->comments comms " .
			"INNER JOIN $wpdb->posts psts ON comms.comment_post_ID = psts.ID " .
			"INNER JOIN $wpdb->commentmeta commsm ON comms.comment_ID = commsm.comment_id " .
			"WHERE comms.comment_approved = '1' AND psts.post_type = 'product' AND commsm.meta_key = 'rating'";
			$reviews_ids = $wpdb->get_col( $query_ids );
			if( $reviews_ids ) {
				$reviews_ids = array_map( 'intval', $reviews_ids );
				foreach ( $reviews_ids as $review_id ) {
					if ( $temp_r = get_comment( $review_id ) ) {
						$reviews[] = $temp_r;
					}
				}
			}
		} else {
			$reviews = get_comments( array(
				'post_type' => 'product',
				'status'    => 'approve',
				'meta_key'	=> 'rating',
				'update_comment_meta_cache' => true,
				'update_comment_post_cache' => true
			) );
		}

		$reviews = array_map( function( $review ) {
			$_review = new stdClass;
			$_review->images = array();

			$images = get_comment_meta( $review->comment_ID, 'ivole_review_image' );
			if ( count( $images ) > 0 ) {
				foreach ( $images as $image ) {
					$_review->images[] = $image['url'];
				}
			} else {
				$images = get_comment_meta( $review->comment_ID, 'ivole_review_image2' );
				foreach ( $images as $image ) {
					$_review->images[] = wp_get_attachment_url( $image );
				}
			}

			$_review->is_anon = empty( $review->comment_author );
			$_review->author  = ! empty( $review->comment_author ) ? $review->comment_author : __( 'Anonymous', IVOLE_TEXT_DOMAIN );
			//hide full names because Google don't accept them
			if( !$_review->is_anon ) {
				$_review->author = trim( $_review->author );
				if( strpos( $_review->author, ' ' ) !== false ) {
					$parts = explode( ' ', $_review->author );
					if( count( $parts ) > 1 ) {
						$lastname  = array_pop( $parts );
						$firstname = implode( ' ', $parts );
						$_review->author = $firstname . ' ' . mb_substr( $lastname, 0, 1 ) . '.';
					}
				}
			}

			$_review->id      = $review->comment_ID;
			if( 'yes' === get_option( 'ivole_google_encode_special_chars', 'no' ) ) {
				$_review->author = mb_convert_encoding( htmlspecialchars( $_review->author, ENT_QUOTES | ENT_XML1, 'UTF-8' ), 'HTML-ENTITIES' );
			} else {
				$_review->author = htmlspecialchars( $_review->author, ENT_QUOTES | ENT_XML1, 'UTF-8' );
			}
			$_review->post_id = $review->comment_post_ID;
			$_review->rating  = get_comment_meta( $review->comment_ID, 'rating', true );
			$_review->date    = date( 'c', strtotime( $review->comment_date ) );
			if( 'yes' === get_option( 'ivole_google_encode_special_chars', 'no' ) ) {
				$_review->content = mb_convert_encoding( htmlspecialchars( $review->comment_content, ENT_XML1, 'UTF-8' ), 'HTML-ENTITIES' );
				//$_review->content = mb_convert_encoding( esc_html( $review->comment_content ), 'HTML-ENTITIES' );
			} else {
				$_review->content = htmlspecialchars( $review->comment_content, ENT_XML1, 'UTF-8' );
				//$_review->content = esc_html( $review->comment_content );
			}
			$_review->content = trim( $_review->content );
			$_review->gtins   = Ivole_Google_Shopping_Feed::get_field( $this->field_map['gtin'], $review );
			$_review->mpns    = Ivole_Google_Shopping_Feed::get_field( $this->field_map['mpn'], $review );
			$_review->skus    = Ivole_Google_Shopping_Feed::get_field( $this->field_map['sku'], $review );
			$_review->brands  = Ivole_Google_Shopping_Feed::get_field( $this->field_map['brand'], $review );
			//check if a static brand was specified
			if( !$_review->brands || count( $_review->brands ) == 0 ) {
				if( 'yes' === get_option( 'ivole_google_encode_special_chars', 'no' ) ) {
					$_review->brands = array( mb_convert_encoding( htmlspecialchars( trim( get_option( 'ivole_google_brand_static', '' ) ), ENT_XML1, 'UTF-8' ), 'HTML-ENTITIES' ) );
				} else {
					$_review->brands = array( htmlspecialchars( trim( get_option( 'ivole_google_brand_static', '' ) ), ENT_XML1, 'UTF-8' ) );
				}
			}

			return $_review;
		}, $reviews );

		//error_log( print_r( $reviews, true ) );

		//remove reviews that don't have comments (because Google don't accept them)
		$reviews = array_filter( $reviews, function( $review ) {
			if( isset( $review->content ) && strlen( $review->content ) > 0 ) {
				return true;
			} else {
				return false;
			}
		} );

		return $reviews;
	}

	/**
	 * Returns true if Google Shopping Reviews XML feed is enabled
	 *
	 * @since 3.47
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return ( get_option( 'ivole_google_generate_xml_feed', 'no' ) === 'yes' );
	}

	/**
	 * Schedules the job to generate the feed
	 *
	 * @since 3.47
	 */
	public function activate() {
		// Check to ensure that the wp-content/uploads/cr directory exists
		if ( ! is_dir( IVOLE_CONTENT_DIR ) ) {
			@mkdir( IVOLE_CONTENT_DIR, 0755 );
		}

		do_action( 'ivole_generate_feed' );

		if ( ! wp_next_scheduled( 'ivole_generate_feed' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'ivole_generate_feed' );
		}
	}

	/**
	 * Stops the generation of the feed and deletes the feed file
	 *
	 * @since 3.47
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'ivole_generate_feed' );

		if ( file_exists( $this->file_path ) ) {
			@unlink( $this->file_path );
		}
	}

	/**
	 * Returns the value of a field
	 *
	 * @since 3.47
	 *
	 * @param string $field The name of the field to return a value for
	 * @param WP_Comment $review The review to get the field value for
	 *
	 * @return string
	 */
	public static function get_field( $field, $review ) {
		$field_type = strstr( $field, '_', true );
		$field_key = substr( strstr( $field, '_' ), 1 );
		$exclude_parents = ( get_option( 'ivole_google_exclude_variable_parent', 'yes' ) === 'yes' );
		$value = array();
		$temp = '';
		switch ( $field_type ) {
			case 'product':
				$product = wc_get_product( $review->comment_post_ID );
				$func = 'get_' . $field_key;
				$temp = $product->$func();
				if( !empty( $temp ) ) {
					$value[] = $temp;
				}
				//if a product is variable, reviews are normally associated with the main product
				//but Google also permits to display reviews related to the main product for variations
				//so, we will add product IDs of variations to the array that will be published in XML feed
				if( $product->is_type( 'variable' ) ) {
					$variations_ids = $product->get_children();
					if( !empty( $variations_ids ) ) {
						if( $exclude_parents ) {
							$value = array();
						}
						foreach( $variations_ids as $variation_id ) {
							$variation = wc_get_product( $variation_id );
							$temp = $variation->$func();
							if( !empty( $temp ) ) {
								$value[] = $temp;
							}
						}
					}
				}
				break;
			case 'attribute':
				$product = wc_get_product( $review->comment_post_ID );
				$temp = $product->get_attribute( $field_key );
				if( !empty( $temp ) ) {
					$value[] = $temp;
				}
				//if a product is variable, reviews are normally associated with the main product
				//but Google also permits to display reviews related to the main product for variations
				//so, we will add product IDs of variations to the array that will be published in XML feed
				if( $product->is_type( 'variable' ) ) {
					$variations_ids = $product->get_children();
					if( !empty( $variations_ids ) ) {
						if( $exclude_parents ) {
							$value = array();
						}
						foreach( $variations_ids as $variation_id ) {
							$variation = wc_get_product( $variation_id );
							$temp = $variation->get_attribute( $field_key );
							if( !empty( $temp ) ) {
								$value[] = $temp;
							}
						}
					}
				}
				break;
			case 'meta':
				$temp = get_post_meta( $review->comment_post_ID, $field_key, true );
				if( !empty( $temp ) ) {
					$value[] = $temp;
				}
				//if a product is variable, reviews are normally associated with the main product
				//but Google also permits to display reviews related to the main product for variations
				//so, we will add product IDs of variations to the array that will be published in XML feed
				$product = wc_get_product( $review->comment_post_ID );
				if( $product->is_type( 'variable' ) ) {
					$variations_ids = $product->get_children();
					if( !empty( $variations_ids ) ) {
						if( $exclude_parents ) {
							$value = array();
						}
						foreach( $variations_ids as $variation_id ) {
							$temp = get_post_meta( $variation_id, $field_key, true );
							if( !empty( $temp ) ) {
								$value[] = $temp;
							}
						}
					}
				}
				break;
			case 'tags':
				$product = wc_get_product( $review->comment_post_ID );
				$temp = $product->get_tag_ids();
				if( $temp && is_array( $temp ) && count( $temp ) > 0 ) {
					$tag_name = get_term( $temp[0], 'product_tag' );
					if( $tag_name && $tag_name->name ) {
						$value[] = $tag_name->name;
					}
				}
				break;
		}

		return $value;
	}

}
