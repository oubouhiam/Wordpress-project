<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Reviews_Admin_Menu' ) ):

require_once('class-cr-custom-questions.php');

/**
 * Reviews admin menu controller class
 */
class Ivole_Reviews_Admin_Menu {

    private $ivole_verified_page = '';
    /**
     * Constructor
     *
     * @since 3.36
     */
    public function __construct() {
        $this->ivole_verified_page = get_option( 'ivole_reviews_verified_page', Ivole_Email::get_blogdomain() );
        add_action( 'admin_menu', array( $this, 'register_reviews_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
        add_filter( 'wp_editor_settings', array( $this, 'wp_editor_settings_filter' ), 10, 2 );
        add_action( 'ivole_admin_review_reply_form', array( $this, 'review_reply' ) );
        add_action( 'wp_ajax_ivole-replyto-comment', array( $this, 'wp_ajax_ivole_replyto_comment' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_review_meta_box' ), 10, 2 );
        add_action( 'edit_comment', array( $this, 'update_cr_review' ), 10, 2 );
    }

    /**
     * Register the top-level admin menu
     *
     * @since 3.36
     */
    public function register_reviews_menu() {
        add_menu_page(
            __( 'Reviews', IVOLE_TEXT_DOMAIN ),
            __( 'Reviews', IVOLE_TEXT_DOMAIN ),
            'moderate_comments',
            'ivole-reviews',
            array( $this, 'display_reviews_admin_page' ),
            'dashicons-star-filled',
            56
        );
    }

    public function display_reviews_admin_page() {
        global $post_id, $wpdb;

        $list_table = new Ivole_Reviews_List_Table( [
            'screen' => get_current_screen()
        ] );

        $pagenum  = $list_table->get_pagenum();
        $doaction = $list_table->current_action();

        if ( $doaction ) {
            check_admin_referer( 'bulk-comments' );
            if ( 'delete_all' == $doaction && ! empty( $_REQUEST['pagegen_timestamp'] ) ) {
                $comment_status = wp_unslash( $_REQUEST['comment_status'] );
                $delete_time    = wp_unslash( $_REQUEST['pagegen_timestamp'] );
                $comment_ids    = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = %s AND %s > comment_date_gmt", $comment_status, $delete_time ) );
                $doaction       = 'delete';
            } elseif ( isset( $_REQUEST['delete_comments'] ) ) {
                $comment_ids = $_REQUEST['delete_comments'];
                $doaction    = ( $_REQUEST['action'] != -1 ) ? $_REQUEST['action'] : $_REQUEST['action2'];
            } elseif ( isset( $_REQUEST['ids'] ) ) {
                $comment_ids = array_map( 'absint', explode( ',', $_REQUEST['ids'] ) );
            } elseif ( wp_get_referer() ) {
                wp_safe_redirect( wp_get_referer() );
                exit;
            }

            $approved = $unapproved = $spammed = $unspammed = $trashed = $untrashed = $deleted = 0;
            $redirect_to = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'spammed', 'unspammed', 'approved', 'unapproved', 'ids' ), wp_get_referer() );
            $redirect_to = add_query_arg( 'paged', $pagenum, $redirect_to );
	        wp_defer_comment_counting( true );

            foreach ( $comment_ids as $comment_id ) { // Check the permissions on each
		        if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
			        continue;
		        }

                switch ( $doaction ) {
			        case 'approve':
				        wp_set_comment_status( $comment_id, 'approve' );
				        $approved++;
				        break;
			        case 'unapprove':
				        wp_set_comment_status( $comment_id, 'hold' );
				        $unapproved++;
				        break;
			        case 'spam':
				        wp_spam_comment( $comment_id );
				        $spammed++;
				        break;
			        case 'unspam':
				        wp_unspam_comment( $comment_id );
				        $unspammed++;
				        break;
			        case 'trash':
				        wp_trash_comment( $comment_id );
				        $trashed++;
				        break;
			        case 'untrash':
				        wp_untrash_comment( $comment_id );
				        $untrashed++;
				        break;
			        case 'delete':
			        	wp_delete_comment( $comment_id );
				        $deleted++;
				        break;
		        }
	        }

            if ( ! in_array( $doaction, array( 'approve', 'unapprove', 'spam', 'unspam', 'trash', 'delete' ), true ) ) {
		        $screen = get_current_screen()->id;
                /**
                 * Fires when a custom bulk action should be handled.
                 *
                 * The redirect link should be modified with success or failure feedback
                 * from the action to be used to display feedback to the user.
                 *
                 * The dynamic portion of the hook name, `$screen`, refers to the current screen ID.
                 *
                 * @since 4.7.0
                 *
                 * @param string $redirect_url The redirect URL.
                 * @param string $doaction     The action being taken.
                 * @param array  $items        The items to take the action on.
                 */
                $redirect_to = apply_filters( "handle_bulk_actions-{$screen}", $redirect_to, $doaction, $comment_ids );
	        }

            wp_defer_comment_counting( false );

	        if ( $approved ) {
		        $redirect_to = add_query_arg( 'approved', $approved, $redirect_to );
            }

	        if ( $unapproved ) {
		        $redirect_to = add_query_arg( 'unapproved', $unapproved, $redirect_to );
            }

	        if ( $spammed ) {
		        $redirect_to = add_query_arg( 'spammed', $spammed, $redirect_to );
	        }

            if ( $unspammed ) {
		        $redirect_to = add_query_arg( 'unspammed', $unspammed, $redirect_to );
            }

	        if ( $trashed ) {
	            $redirect_to = add_query_arg( 'trashed', $trashed, $redirect_to );
            }

	        if ( $untrashed ) {
		        $redirect_to = add_query_arg( 'untrashed', $untrashed, $redirect_to );
            }

	        if ( $deleted ) {
		        $redirect_to = add_query_arg( 'deleted', $deleted, $redirect_to );
            }

	        if ( $trashed || $spammed ) {
		        $redirect_to = add_query_arg( 'ids', join( ',', $comment_ids ), $redirect_to );
            }

	        wp_safe_redirect( $redirect_to );
	        exit;
        } elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
	        wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	        exit;
        }

        $list_table->prepare_items();
        include plugin_dir_path( __FILE__ ) . 'templates/all-reviews-admin-page.php';
    }

    public function include_scripts( $hook ) {
        $assets_version = '3.6';

        if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'ivole-reviews' ) {
            wp_enqueue_script( 'admin-comments' );
            wp_enqueue_script( 'photoswipe', plugins_url( 'assets/js/photoswipe/photoswipe.min.js', WC_PLUGIN_FILE ), array(), $assets_version );
            wp_enqueue_script( 'photoswipe-ui-default', plugins_url( 'assets/js/photoswipe/photoswipe-ui-default.min.js', WC_PLUGIN_FILE ), array(), $assets_version );

            wp_enqueue_style( 'photoswipe', plugins_url( 'assets/css/photoswipe/photoswipe.css', WC_PLUGIN_FILE ), array(), $assets_version );
            wp_enqueue_style( 'photoswipe-default-skin', plugins_url( 'assets/css/photoswipe/default-skin/default-skin.css', WC_PLUGIN_FILE ), array(), $assets_version );

            wp_enqueue_script( 'ivole-all-reviews', plugins_url( 'js/all-reviews.js', __FILE__ ), array( 'jquery' ), $assets_version );
            wp_enqueue_style( 'ivole-all-reviews', plugins_url( 'css/all-reviews.css', __FILE__ ), array(), $assets_version );

            add_action( 'admin_footer', 'woocommerce_photoswipe' );
        }
        if( 'comment.php' === $hook ) {
          wp_enqueue_style( 'ivole_trustbadges_admin_css', plugins_url('css/admin.css', __FILE__) );
          wp_enqueue_style( 'ivole-all-reviews', plugins_url( 'css/all-reviews.css', __FILE__ ), array(), $assets_version );
        }
    }

    public function wp_editor_settings_filter( $settings, $editor_id ) {
      if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === 'ivole-reviews' ) {
        if( isset( $settings ) && array_key_exists( 'quicktags', $settings ) ) {
          $settings['quicktags'] = false;
        }
      }
      return $settings;
    }

    public function review_reply( $position = 1, $checkbox = false, $mode = 'single', $table_row = true ) {
      //this standard function was modified to include a hidden input element with ID = "ivole_action" instead of ID = "action"
      //this is a trick to make the standard WordPress JS file ("edit-comments.js") use a different AJAX function
      //based on the hook "ivole-replyto-comment" instead of "replyto-comment"
      global $wp_list_table;

      if ( ! $wp_list_table ) {
        $wp_list_table = new Ivole_Reviews_List_Table( [
            'screen' => 'ivole-reviews'
        ] );
      }

    ?>
    <form method="get">
    <?php if ( $table_row ) : ?>
    <table style="display:none;"><tbody id="com-reply"><tr id="replyrow" class="inline-edit-row" style="display:none;"><td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="colspanchange">
    <?php else : ?>
    <div id="com-reply" style="display:none;"><div id="replyrow" style="display:none;">
    <?php endif; ?>
      <fieldset class="comment-reply">
      <legend>
        <span class="hidden" id="editlegend"><?php _e( 'Edit Comment' ); ?></span>
        <span class="hidden" id="replyhead"><?php _e( 'Reply to Review', IVOLE_TEXT_DOMAIN ); ?></span>
        <span class="hidden" id="addhead"><?php _e( 'Add new Comment' ); ?></span>
      </legend>

      <div id="replycontainer">
      <label for="replycontent" class="screen-reader-text"><?php _e( 'Comment' ); ?></label>
      <?php
      $quicktags_settings = array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' );
      wp_editor( '', 'replycontent', array( 'media_buttons' => false, 'tinymce' => false, 'quicktags' => $quicktags_settings ) );
      ?>
      </div>

      <div id="edithead" style="display:none;">
        <div class="inside">
        <label for="author-name"><?php _e( 'Name' ) ?></label>
        <input type="text" name="newcomment_author" size="50" value="" id="author-name" />
        </div>

        <div class="inside">
        <label for="author-email"><?php _e('Email') ?></label>
        <input type="text" name="newcomment_author_email" size="50" value="" id="author-email" />
        </div>

        <div class="inside">
        <label for="author-url"><?php _e('URL') ?></label>
        <input type="text" id="author-url" name="newcomment_author_url" class="code" size="103" value="" />
        </div>
      </div>

      <div id="replysubmit" class="submit">
        <div id="ivole_replytocr" class="iv-replytocr">
          <?php $this->publish_reply_to_CR_checkbox( 'ivole_replyto_cr' ); ?>
        </div>
        <p>
          <a href="#comments-form" class="save button button-primary alignright">
            <span id="addbtn" style="display: none;"><?php _e( 'Add Comment' ); ?></span>
            <span id="savebtn" style="display: none;"><?php _e( 'Update Comment' ); ?></span>
            <span id="replybtn" style="display: none;"><?php _e( 'Submit Reply' ); ?></span>
          </a>
          <a href="#comments-form" class="cancel button alignleft"><?php _e( 'Cancel' ); ?></a>
          <span class="waiting spinner"></span>
        </p>
        <br class="clear" />
        <div class="notice notice-error notice-alt inline hidden">
          <p class="error"></p>
        </div>
      </div>

      <input type="hidden" name="action" id="ivole_action" value="ivole-replyto-comment" />
      <input type="hidden" name="comment_ID" id="comment_ID" value="" />
      <input type="hidden" name="comment_post_ID" id="comment_post_ID" value="" />
      <input type="hidden" name="status" id="status" value="" />
      <input type="hidden" name="position" id="position" value="<?php echo $position; ?>" />
      <input type="hidden" name="checkbox" id="checkbox" value="<?php echo $checkbox ? 1 : 0; ?>" />
      <input type="hidden" name="mode" id="mode" value="<?php echo esc_attr($mode); ?>" />
      <?php
        wp_nonce_field( 'replyto-comment', '_ajax_nonce-replyto-comment', false );
        if ( current_user_can( 'unfiltered_html' ) )
          wp_nonce_field( 'unfiltered-html-comment', '_wp_unfiltered_html_comment', false );
      ?>
      </fieldset>
    <?php if ( $table_row ) : ?>
    </td></tr></tbody></table>
    <?php else : ?>
    </div></div>
    <?php endif; ?>
    </form>
    <?php
    }

    public function publish_reply_to_CR_checkbox( $name ) {
      if ( 'yes' === get_option( 'ivole_reviews_verified', 'no' ) ) {
        $licenseKey = get_option( 'ivole_license_key', '' );
        if( strlen( $licenseKey ) > 0 ) {
          echo '<input type="checkbox" id="' . $name . '_checkbox" name="' . $name . '" value="no" />';
          echo '<label for="' . $name . '_checkbox">' . __( 'Publish a copy of your reply to CR (Customer Reviews) portal', IVOLE_TEXT_DOMAIN ) . '</label>';
        } else {
          $url_cr = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '" target="_blank">' . __( 'CR (Customer Reviews) portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
          $url_set1 = '<a href="' . admin_url( 'admin.php?page=ivole-reviews-settings&tab=license-key' ) . '">' . __( 'plugin\'s settings', IVOLE_TEXT_DOMAIN ) . '</a>';
          printf( __( 'A copy of this reply will not be published on %1$s because no license key was entered in the %2$s. A license key (free or professional) is used as a secret token for additional security when communicating with CR service.', IVOLE_TEXT_DOMAIN ), $url_cr, $url_set1 );
        }
      }
      else {
        $url_set2 = '<a href="' . admin_url( 'admin.php?page=ivole-reviews-settings&tab=trust_badges' ) . '">' . __( 'disabled', IVOLE_TEXT_DOMAIN ) . '</a>';
        printf( __( 'A copy of this reply will not be published on CR (Customer Reviews) portal because Verified Reviews Page option is %s.', IVOLE_TEXT_DOMAIN ), $url_set2 );
      }
    }

    public function wp_ajax_ivole_replyto_comment( $action ) {
      if ( empty( $action ) )
    		$action = 'replyto-comment';

    	check_ajax_referer( $action, '_ajax_nonce-replyto-comment' );

    	$comment_post_ID = (int) $_POST['comment_post_ID'];
    	$post = get_post( $comment_post_ID );
    	if ( ! $post )
    		wp_die( -1 );

    	if ( !current_user_can( 'edit_post', $comment_post_ID ) )
    		wp_die( -1 );

    	if ( empty( $post->post_status ) )
    		wp_die( 1 );
    	elseif ( in_array($post->post_status, array('draft', 'pending', 'trash') ) )
    		wp_die( __('ERROR: you are replying to a comment on a draft post.') );

    	$user = wp_get_current_user();
    	if ( $user->exists() ) {
    		$user_ID = $user->ID;
    		$comment_author       = wp_slash( $user->display_name );
    		$comment_author_email = wp_slash( $user->user_email );
    		$comment_author_url   = wp_slash( $user->user_url );
    		$comment_content      = trim( $_POST['content'] );
    		$comment_type         = isset( $_POST['comment_type'] ) ? trim( $_POST['comment_type'] ) : '';
    		if ( current_user_can( 'unfiltered_html' ) ) {
    			if ( ! isset( $_POST['_wp_unfiltered_html_comment'] ) )
    				$_POST['_wp_unfiltered_html_comment'] = '';

    			if ( wp_create_nonce( 'unfiltered-html-comment' ) != $_POST['_wp_unfiltered_html_comment'] ) {
    				kses_remove_filters(); // start with a clean slate
    				kses_init_filters(); // set up the filters
    			}
    		}
    	} else {
    		wp_die( __( 'Sorry, you must be logged in to reply to a comment.' ) );
    	}

    	if ( '' == $comment_content )
    		wp_die( __( 'ERROR: please type a comment.' ) );

    	$comment_parent = 0;
    	if ( isset( $_POST['comment_ID'] ) )
    		$comment_parent = absint( $_POST['comment_ID'] );
    	$comment_auto_approved = false;
    	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

    	// Automatically approve parent comment.
    	if ( !empty($_POST['approve_parent']) ) {
    		$parent = get_comment( $comment_parent );

    		if ( $parent && $parent->comment_approved === '0' && $parent->comment_post_ID == $comment_post_ID ) {
    			if ( ! current_user_can( 'edit_comment', $parent->comment_ID ) ) {
    				wp_die( -1 );
    			}

    			if ( wp_set_comment_status( $parent, 'approve' ) )
    				$comment_auto_approved = true;
    		}
    	}

    	$comment_id = wp_new_comment( $commentdata );

    	if ( is_wp_error( $comment_id ) ) {
    		wp_die( $comment_id->get_error_message() );
    	}

      //error_log( print_r( $_POST, true ) );
      //send a copy of the reply to CR if a checkbox was enabled
      if( isset( $_POST['ivole_replyto_cr'] ) && 'yes' === $_POST['ivole_replyto_cr'] ) {
        $cr_reply = new Ivole_Replies( $comment_id );
      }

    	$comment = get_comment($comment_id);
    	if ( ! $comment ) wp_die( 1 );

    	$position = ( isset($_POST['position']) && (int) $_POST['position'] ) ? (int) $_POST['position'] : '-1';

    	ob_start();
      $wp_list_table = new Ivole_Reviews_List_Table( [
          'screen' => 'ivole-reviews'
      ] );
  		$wp_list_table->single_row( $comment );
    	$comment_list_item = ob_get_clean();

    	$response =  array(
    		'what' => 'comment',
    		'id' => $comment->comment_ID,
    		'data' => $comment_list_item,
    		'position' => $position
    	);

    	$counts = wp_count_comments();
    	$response['supplemental'] = array(
    		'in_moderation' => $counts->moderated,
    		'i18n_comments_text' => sprintf(
    			_n( '%s Comment', '%s Comments', $counts->approved ),
    			number_format_i18n( $counts->approved )
    		),
    		'i18n_moderation_text' => sprintf(
    			_nx( '%s in moderation', '%s in moderation', $counts->moderated, 'comments' ),
    			number_format_i18n( $counts->moderated )
    		)
    	);

    	if ( $comment_auto_approved ) {
    		$response['supplemental']['parent_approved'] = $parent->comment_ID;
    		$response['supplemental']['parent_post_id'] = $parent->comment_post_ID;
    	}

    	$x = new WP_Ajax_Response();
    	$x->add( $response );
    	$x->send();
    }

    public function add_review_meta_box( $post_type, $comment ) {
      if ( 'comment' === $post_type ) {
        if( Ivole_Replies::isReplyForCRReview( $comment ) ) {
          add_meta_box(
            'ivole_cr_meta_box',
            __( 'Customer Reviews (CR)', IVOLE_TEXT_DOMAIN ),
            array( $this, 'render_review_meta_box' ),
            $post_type,
            'normal',
            'default'
          );
        }
        if( 0 === intval( $comment->comment_parent ) ) {
  				$custom_questions = new CR_Custom_Questions();
  				$custom_questions->read_questions( $comment->comment_ID );
  				if( $custom_questions->has_questions() ) {
            add_meta_box(
              'ivole_cr_meta_box_cq',
              __( 'Answers to Custom Questions', IVOLE_TEXT_DOMAIN ),
              array( $this, 'render_review_meta_box_cq' ),
              $post_type,
              'normal',
              'default',
              array( $custom_questions )
            );
          }
  			}
      }
    }

    public function render_review_meta_box( $comment ) {
      if( $comment && $comment->comment_ID ) {

        //if the reply was published to CR, add a label indicating this
  			$ivole_reply = get_comment_meta( $comment->comment_ID, 'ivole_reply', true );
  			if( $ivole_reply ) {
          $shop_page_id = wc_get_page_id( 'shop' );
          wp_nonce_field( 'ivole_update_reply_cr', 'ivole_update_reply_cr_checkbox' );
          if( 201 === $ivole_reply[0] ) {
            wp_nonce_field( 'ivole_update_reply_cr', 'ivole_update_reply_cr_checkbox' );
            $order_product = Ivole_Replies::isReplyForCRReview( $comment );
            if( $shop_page_id == $order_product[1] ) {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            } else {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            }
  					echo '<p class="ivole-reply-published-cr-c"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy of this reply was published on %s', IVOLE_TEXT_DOMAIN ), $url ) . '</p>';
          } elseif( 202 === $ivole_reply[0] ) {
            wp_nonce_field( 'ivole_noupdate_reply_cr', 'ivole_update_reply_cr_checkbox' );
            $order_product = Ivole_Replies::isReplyForCRReview( $comment );
            if( $shop_page_id == $order_product[1] ) {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            } else {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            }
  					echo '<p class="ivole-reply-published-cr-c"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %s. If this copy of the reply is edited, the original reply will NOT be updated. Only the customer can edit the original reply on CR portal.', IVOLE_TEXT_DOMAIN ), $url ) . '</p>';
          } elseif( 203 === $ivole_reply[0] ) {
            wp_nonce_field( 'ivole_noupdate_reply_cr', 'ivole_update_reply_cr_checkbox' );
            $order_product = Ivole_Replies::isReplyForCRReview( $comment );
            if( $shop_page_id == $order_product[1] ) {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            } else {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            }
  					echo '<p class="ivole-reply-published-cr-c"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'This reply was originally posted on %s. This copy of the reply was modified and might be different from the original published on CR portal.', IVOLE_TEXT_DOMAIN ), $url ) . '</p>';
          } elseif( 409 === $ivole_reply[0] ) {
            wp_nonce_field( 'ivole_update_reply_cr', 'ivole_update_reply_cr_checkbox' );
            $order_product = Ivole_Replies::isReplyForCRReview( $comment );
            if( $shop_page_id == $order_product[1] ) {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/s/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            } else {
              $url = '<a href="https://www.cusrev.com/reviews/' . $this->ivole_verified_page . '/p/p-' . $order_product[1] . '/r-' . $order_product[0] . '" target="_blank">' . __( 'CR portal', IVOLE_TEXT_DOMAIN ) . '</a><span class="dashicons dashicons-external"></span>';
            }
  					echo '<p class="ivole-reply-published-cr-c"><span class="dashicons dashicons-yes"></span> ' . sprintf( __( 'A verified copy published on %s could not be updated because it is not the last reply for the review. The verified copy might be different from this reply.', IVOLE_TEXT_DOMAIN ), $url ) . '</p>';
          } else {
            wp_nonce_field( 'ivole_update_reply_cr', 'ivole_update_reply_cr_checkbox' );
            echo '<p class="ivole-reply-published-cr-c"><span class="dashicons dashicons-warning"></span> ' . __( 'Reply could not be published to CR portal. Error ', IVOLE_TEXT_DOMAIN ) . $ivole_reply[0] . ' (' . $ivole_reply[1] . ').</p>';
          }
  			} else {
          wp_nonce_field( 'ivole_publish_reply_cr', 'ivole_publish_reply_cr_checkbox' );
          echo '<p>';
          $this->publish_reply_to_CR_checkbox( 'ivole_editreply_cr' );
          echo '</p>';
        }
      }
    }

    public function render_review_meta_box_cq( $comment, $metabox ) {
      if( isset( $metabox['args'] ) && is_array( $metabox['args'] ) ) {
        if( count( $metabox['args'] ) > 0 ) {
          $metabox['args'][0]->output_questions( false, false );
        }
      }
    }

    public function update_cr_review( $comment_ID, $data ) {
      if( isset( $_POST['ivole_publish_reply_cr_checkbox'] ) && wp_verify_nonce( $_POST['ivole_publish_reply_cr_checkbox'], 'ivole_publish_reply_cr' ) ) {
        if( isset( $_POST['ivole_editreply_cr'] ) && $comment_ID ) {
          $cr_reply = new Ivole_Replies( $comment_ID );
        }
      } elseif( isset( $_POST['ivole_update_reply_cr_checkbox'] ) && wp_verify_nonce( $_POST['ivole_update_reply_cr_checkbox'], 'ivole_update_reply_cr' ) ) {
        $cr_reply = new Ivole_Replies( $comment_ID );
      } elseif( isset( $_POST['ivole_update_reply_cr_checkbox'] ) && wp_verify_nonce( $_POST['ivole_update_reply_cr_checkbox'], 'ivole_noupdate_reply_cr' ) ) {
        $meta = array();
        $meta[] = 203;
        $meta[] = __( 'This reply was originally posted on CR portal. This copy of the reply was modified and might be different from the original reply published on CR portal.', IVOLE_TEXT_DOMAIN );
        update_comment_meta( $comment_ID, 'ivole_reply', $meta );
      }
    }

}

endif;
