<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Ivole_Reminders_Admin_Menu' ) ):

/**
 * Reminders admin menu class
 *
 * @since 3.5
 */
class Ivole_Reminders_Admin_Menu {

	/**
     * @var string The slug identifying this menu
     */
    protected $menu_slug;

    /**
     * Constructor
     *
     * @since 3.5
     */
    public function __construct() {
		$this->menu_slug = 'ivole-reviews-reminders';

		add_action( 'admin_menu', array( $this, 'register_reminders_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'include_scripts' ) );
    }

    /**
     * Register the reminders submenu
     *
     * @since 3.5
     */
    public function register_reminders_menu() {
        add_submenu_page(
            'ivole-reviews',
            __( 'Reminders', IVOLE_TEXT_DOMAIN ),
            __( 'Reminders', IVOLE_TEXT_DOMAIN ),
            'manage_options',
            $this->menu_slug,
            array( $this, 'display_reminders_admin_page' )
        );
	}

	/**
	 * Handles bulk and per-reminder actions.
	 *
	 * @since 3.5
	 *
	 * @param string $action The action to process
	 */
	protected function process_actions( $list_table ) {
		$action = $list_table->current_action();

		switch ( $action ) {
			case 'cancel':
			case 'send':
				// Bulk actions
				check_admin_referer( 'bulk-reminders' );

				$orders = ( isset( $_GET['orders'] ) && is_array( $_GET['orders'] ) ) ? $_GET['orders']: array();
				$orders = array_map( 'intval', $orders );
				break;
			case 'cancelreminder':
			case 'sendreminder':
				// Single-reminder actions
				check_admin_referer( 'manage-reminders' );

				$orders = array();
				$order_id = ( isset( $_GET['order_id'] ) ) ? intval( $_GET['order_id'] ): 0;

				if ( $order_id ) {
					$orders[] = $order_id;
				}
		}

		$cancelled = 0;
		$sent = 0;
		foreach ( $orders as $order_id ) {
			switch ( $action ) {
				case 'cancel':
				case 'cancelreminder':
					wp_clear_scheduled_hook( 'ivole_send_reminder', array( $order_id ) );
					$cancelled++;
					break;
				case 'send':
				case 'sendreminder':
					wp_clear_scheduled_hook( 'ivole_send_reminder', array( $order_id ) );
					wp_schedule_single_event( 1, 'ivole_send_reminder', array( $order_id ) );
					$sent++;
			}
		}

		if ( $sent ) {
			wp_cron();
		}

		$redirect_to = remove_query_arg( array( 'reminder' ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $list_table->get_pagenum(), $redirect_to );

		if ( $cancelled ) {
			$redirect_to = add_query_arg( 'cancelled', $cancelled, $redirect_to );
		}

		if ( $sent ) {
			$redirect_to = add_query_arg( 'sent', $sent, $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
	    exit;
	}

	/**
	 * Render the scheduled reminders page
	 *
	 * @since 3.5
	 */
  public function display_reminders_admin_page() {
    $list_table = new Ivole_Reminders_List_Table( [
        'screen' => get_current_screen()
      ] );
    $pagenum  = $list_table->get_pagenum();
    $doaction = $list_table->current_action();

		if ( $list_table->current_action() ) {
			$this->process_actions( $list_table );
    } elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
	    wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	    exit;
    }

    $list_table->prepare_items();

    include plugin_dir_path( __FILE__ ) . 'templates/reminders-admin-page.php';
	}

	/**
	 * Include scripts
	 *
	 * @since 3.5
	 */
	public function include_scripts() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this->menu_slug ) {
			wp_enqueue_script( 'jquery' );
		}
	}

}

endif;
