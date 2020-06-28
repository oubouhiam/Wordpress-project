<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('Ivole_Trust_Badge')) :

    class Ivole_Trust_Badge
    {

        /**
         * @var array holds the current shorcode attributes
         */
        public $shortcode_atts;
        protected $lang;

        public function __construct( $shortcode_atts )
        {
            $defaults = array(
                'type' => 'sl',
                'border' => 'yes',
                'color' => ''
            );
            if ( isset( $shortcode_atts['type'] ) ) {
                $type = str_replace( ' ', '', $shortcode_atts['type'] );
                $type = strtolower( $type );
                $allowed_types = array( 'sl', 'slp', 'sd', 'sdp', 'wl', 'wlp', 'wd', 'wdp' );
                if( in_array( $type, $allowed_types ) ) {
                  $shortcode_atts['type'] = $type;
                } else {
                  $shortcode_atts['type'] = null;
                }
            }
            if ( isset( $shortcode_atts['border'] ) ) {
                $border = str_replace( ' ', '', $shortcode_atts['border'] );
                $border = strtolower( $border );
                $allowed_borders = array( 'yes', 'no' );
                if( in_array( $border, $allowed_borders ) ) {
                  $shortcode_atts['border'] = $border;
                } else {
                  $shortcode_atts['border'] = 'yes';
                }
            }
            if ( isset( $shortcode_atts['color'] ) ) {
                $color = str_replace( ' ', '', $shortcode_atts['color'] );
                $color = strtolower( $color );
                if( preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $color ) ) {
                  $shortcode_atts['color'] = $color;
                } else {
                  $shortcode_atts['color'] = '';
                }
            }
            $this->shortcode_atts = shortcode_atts($defaults, $shortcode_atts);
            $this->lang = Ivole_Trust_Badge::get_badge_language();
            // load styles and js
            $this->ivole_style();
        }

        public function show_trust_badge()
        {
          $l_suffix = '';
          $site_lang = '';
          if( 'en' !== $this->lang ) {
            $l_suffix = '-' . $this->lang;
            $site_lang = $this->lang . '/';
          }
          $color = '';
          if( 0 < strlen( $this->shortcode_atts['color'] ) ) {
            $color = '" style="background-color:' . $this->shortcode_atts['color'] . ';';
          }
          $class_img = 'ivole-trustbadgefi-' . $this->shortcode_atts['type'] . ' ivole-trustbadgefi-b' . $this->shortcode_atts['border'];
          $return = '<div id="ivole_trustbadgef_' . $this->shortcode_atts['type'] . '" class="ivole-trustbadgef-' . $this->shortcode_atts['type'] . '">';
          $return .= '<a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() ) . '" rel="nofollow" target="_blank">';
          if( 'wdp' === $this->shortcode_atts['type'] ) {
            $return .= '<picture><source media="(min-width: 500px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . '">';
            $return .= '<source media="(min-width: 10px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . 'wdpm' . $l_suffix . '.png' . '">';
            $return .= '<img id="ivole_trustbadgefi_' . $this->shortcode_atts['type'] . '" class="' . $class_img . '" src="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '">';
            $return .= '</picture>';
          } elseif ('wd' === $this->shortcode_atts['type']) {
            $return .= '<picture><source media="(min-width: 500px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . '">';
            $return .= '<source media="(min-width: 10px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . 'wdm' . $l_suffix . '.png' . '">';
            $return .= '<img id="ivole_trustbadgefi_' . $this->shortcode_atts['type'] . '" class="' . $class_img . '" src="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '">';
            $return .= '</picture>';
          } elseif ('wlp' === $this->shortcode_atts['type']) {
            $return .= '<picture><source media="(min-width: 500px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . '">';
            $return .= '<source media="(min-width: 10px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . 'wlpm' . $l_suffix . '.png' . '">';
            $return .= '<img id="ivole_trustbadgefi_' . $this->shortcode_atts['type'] . '" class="' . $class_img . '" src="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '">';
            $return .= '</picture>';
          } elseif ('wl' === $this->shortcode_atts['type']) {
            $return .= '<picture><source media="(min-width: 500px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . '">';
            $return .= '<source media="(min-width: 10px)" srcset="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . 'wlm' . $l_suffix . '.png' . '">';
            $return .= '<img id="ivole_trustbadgefi_' . $this->shortcode_atts['type'] . '" class="' . $class_img . '" src="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '">';
            $return .= '</picture>';
          } else {
            $return .= '<img id="ivole_trustbadgefi_' . $this->shortcode_atts['type'] . '" class="' . $class_img . '" src="' . 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $this->shortcode_atts['type'] . $l_suffix . '.png' . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '">';
          }
          $return .= '</a></div>';
          return $return;
        }

        public function ivole_style()
        {
            wp_register_style('ivole-frontend-css', plugins_url('/css/frontend.css', __FILE__), array(), null, 'all');
            wp_enqueue_style('ivole-frontend-css');
        }

        /**
         * Registers the trustbadge block
         *
         * @since 3.53
         */
        public static function register_block() {
            // Only register the block if the WP is at least 5.0, or gutenberg is installed.
            if ( function_exists( 'register_block_type' ) ) {
                register_block_type( 'ivole/cusrev-trustbadge', array(
                    'attributes' => array(
                        'badge_size' => array(
                            'type' => 'string',
                            'enum' => array( 'small', 'wide' ),
                            'default' => 'small'
                        ),
                        'badge_style' => array(
                            'type' => 'string',
                            'enum' => array( 'light', 'dark' ),
                            'default' => 'light'
                        ),
                        'store_rating' => array(
                            'type' => 'boolean',
                            'default' => false
                        ),
                        'badge_border' => array(
                            'type' => 'boolean',
                            'default' => true
                        ),
                        'badge_color' => array(
                            'type' => 'string',
                            'default' => '#ffffff'
                        )
                    ),
                    'render_callback' => array( self::class, 'render_block' )
                ) );
            }
        }

        /**
         * Render the trust_badges block
         *
         * @since 3.53
         *
         * @param array $block_attributes An array of block attributes
         *
         * @return string
         */
        public static function render_block( $block_attributes ) {
            // If trust badges are not enabled, display nothing.
            if ( get_option( 'ivole_reviews_verified', 'no' ) === 'no' ) {
                return '';
            }

            $badge_type = $block_attributes['badge_size'] === 'small' ? 's' : 'w';
            $badge_type .= $block_attributes['badge_style'] === 'light' ? 'l' : 'd';
            $badge_type .= $block_attributes['store_rating'] ? 'p' : '';

            $badge_border = $block_attributes['badge_border'] ? 'yes': 'no';

            $badge_color = $block_attributes['badge_color'];
            $color = str_replace( ' ', '', $badge_color );
            $color = strtolower( $badge_color );
            if( preg_match( '/#([a-f0-9]{3}){1,2}\b/i', $color ) ) {
              $color = '" style="background-color:' . $color . ';';
            } else {
              $color = '';
            }

            $l_suffix = '';
            $site_lang = '';
            $lng = Ivole_Trust_Badge::get_badge_language();
            if( 'en' !== $lng ) {
              $l_suffix = '-' . $lng;
              $site_lang = $lng . '/';
            }

            $verified_reviews_page = get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
            $badge_img_src = 'https://www.cusrev.com/badges/' . Ivole_Email::get_blogurl() . '-' . $badge_type . $l_suffix . '.png';

            $class_img = 'ivole-trustbadgefi-' . $badge_type . ' ivole-trustbadgefi-b' . $badge_border;
            $return = '<div id="ivole_trustbadgef_' . $badge_type . '" class="ivole-trustbadgef-' . $badge_type . '">';
            $return .= '<a href="https://www.cusrev.com/' . $site_lang . 'reviews/' . $verified_reviews_page . '" rel="nofollow" target="_blank"><img id="ivole_trustbadgefi_' . $badge_type . '" class="' . $class_img . '" src="' . $badge_img_src . $color . '" alt="' . __( 'Trust Badge', IVOLE_TEXT_DOMAIN ) . '"></a>';
            $return .= '</div>';

            return $return;
        }

        public static function get_badge_language() {
          $language = 'en';
          $blog_language = get_bloginfo( 'language', 'display' );
          if( is_string( $blog_language ) ) {
            $blog_language = substr( $blog_language, 0, 2 );
            if( 2 === strlen( $blog_language ) ) {
              $language = strtolower( $blog_language );
            }
          }
          return $language;
        }

    }

endif;
