<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . '/wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'Ivole_Reminders_List_Table' ) ) :

/**
 * Reminders List Table
 *
 * @since 3.5
 */
class Ivole_Reminders_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array(
			'plural'   => 'reminders',
			'singular' => 'reminder',
			'ajax'     => false,
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}

	/**
	 * Fetch a list of reminders
	 *
	 * @since 3.5
	 */
	public function prepare_items() {
		global $search;
		$crons = _get_cron_array();
		$reminders = [];

		$search = ( isset( $_REQUEST['s'] ) ) ? trim( $_REQUEST['s'] ) : '';

		$registered_customers = false;
		if ( 'yes' === get_option( 'ivole_registered_customers', 'no' ) ) {
			$registered_customers = true;
		}

		foreach ( $crons as $timestamp => $hooks ) {
			// $timestamp > 1 is to exclude reminders the plugin is currently sending,
			// these are rescheduled with a timestamp of 1
			if ( isset( $hooks['ivole_send_reminder'] ) && $timestamp > 1 ) {
				foreach ( $hooks['ivole_send_reminder'] as $hash => $event ) {
					$order_id = $event['args'][0];

					$order = wc_get_order( $order_id );
					if( $order ) {
						$customer_name = '';
						$customer_email = '';
						$order_number = $order_id;
						$user = null;
						if ( method_exists( $order, 'get_billing_email' ) ) {
							// Woocommerce version 3.0 or later
							$user = $order->get_user();

							if ( $registered_customers ) {
								if ( $user ) {
									$customer_email = $user->user_email;
								} else {
									$customer_email = $order->get_billing_email();
								}
							} else {
								$customer_email = $order->get_billing_email();
							}

							$order_number = $order->get_order_number();
							$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
						} else {
							// Woocommerce before version 3.0
							$user_id = get_post_meta( $order_id, '_customer_user', true );

							if ( $user_id ) {
								$user = get_user_by( 'id', $user_id );
							}

							if ( $registered_customers ) {
								if ( $user ) {
									$customer_email = $user->user_email;
								} else {
									$customer_email = get_post_meta( $order_id, '_billing_email', true );
								}
							} else {
								$customer_email = get_post_meta( $order_id, '_billing_email', true );
							}

							$customer_name = get_post_meta( $order_id, '_billing_first_name', true ) . ' ' . get_post_meta( $order_id, '_billing_last_name', true );
							$order_number = $order->get_order_number();
						}

						if( $search ) {
							if( stripos( $customer_name, $search ) !== false || stripos( $customer_email, $search ) !== false ||
						 		stripos( $order_id, $search ) !== false ) {
									$reminders[] = array(
										'timestamp'      => $timestamp,
										'order_id'       => $order_id,
										'order_number'   => $order_number,
										'customer_name'  => $customer_name,
										'customer_email' => $customer_email
									);
							}
						} else {
							$reminders[] = array(
								'timestamp'      => $timestamp,
								'order_id'       => $order_id,
								'order_number'   => $order_number,
								'customer_name'  => $customer_name,
								'customer_email' => $customer_email
							);
						}
					}
				}
			}
		}

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable, 'order' );

		$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'order_number';
		$order = ( isset( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc';

		$per_page = $this->get_per_page();
		$page = $this->get_pagenum();
		$start = ( $page - 1 ) * $per_page;

		usort( $reminders, function( $reminder_a, $reminder_b ) use ( $orderby, $order ) {
			if ( $reminder_a[$orderby] == $reminder_b[$orderby] ) {
				return 0;
			}

			$test = ( $order === 'asc' ) ? ( $reminder_a[$orderby] < $reminder_b[$orderby] ): ( $reminder_a[$orderby] > $reminder_b[$orderby] );

			return $test ? -1: 1;
		} );

		if ( is_array( $reminders ) ) {
			$this->items = array_slice( $reminders, $start, $per_page );
		}

		$this->set_pagination_args( array(
			'total_items' => count( $reminders ),
			'per_page' => $per_page,
		) );
	}

	/**
	 * Returns the amount of reminders displayed per page. Default 30.
	 *
	 * @since 3.5
	 *
	 * @return int
	 */
	public function get_per_page() {
		$reminders_per_page = $this->get_items_per_page( 'reminders_per_page', 30 );

		return $reminders_per_page;
	}

	/**
	 * Prints the content displayed if there are no reminders.
	 *
	 * @since 3.5
	 */
	public function no_items() {
		if( 'cr' === get_option( 'ivole_scheduler_type' ) ) {
			_e( 'The plugin is configured to use CR Cron for scheduling review reminders (<a href="' . admin_url( 'admin.php?page=ivole-reviews-settings' ) . '" title="Plugin Settings">\'Reminders Scheduler\' setting</a>). Please log in to your account on <a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">CR website</a> to view and manage the reminders.', IVOLE_TEXT_DOMAIN );
		} else {
			_e( 'There are currently no scheduled review reminders', IVOLE_TEXT_DOMAIN );
		}
	}

	/**
	 * Returns a list of bulk actions
	 *
	 * @since 3.5
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array(
			'cancel' => __( 'Cancel', IVOLE_TEXT_DOMAIN ),
			'send'   => __( 'Send Now', IVOLE_TEXT_DOMAIN )
		);

		return $actions;
	}

	/**
	 * Return the column names
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'		=> '<input type="checkbox" />',
			'order'		=> __( 'Order Number', IVOLE_TEXT_DOMAIN ),
			'customer'	=> __( 'Customer', IVOLE_TEXT_DOMAIN ),
			'scheduled'	=> __( 'Scheduled', IVOLE_TEXT_DOMAIN ),
			'actions'	=> __( 'Actions', IVOLE_TEXT_DOMAIN )
		);
	}

	/**
	 * Returns the columns which are sortable
	 *
	 * @since 3.5
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'order'		=> array( 'order_number', false ),
			'customer'	=> array( 'customer_name', false ),
			'scheduled'	=> array( 'timestamp', false )
		);
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @since 3.5
	 *
	 * @return string Name of the default primary column
	 */
	protected function get_default_primary_column_name() {
		return 'order';
	}

	/**
	 * Print the list table
	 *
	 * @since 3.5
	 */
	public function display() {
		wp_nonce_field( "fetch-list-" . get_class( $this ), '_ajax_fetch_list_nonce' );

		$this->display_tablenav( 'top' );

		$this->screen->render_screen_reader_content( 'heading_list' );

?>
<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<thead>
		<tr>
			<?php $this->print_column_headers(); ?>
		</tr>
	</thead>

	<tbody id="the-reminder-list" data-wp-lists="list:reminder">
		<?php $this->display_rows_or_placeholder(); ?>
	</tbody>

	<tfoot>
		<tr>
			<?php $this->print_column_headers( false ); ?>
		</tr>
	</tfoot>

</table>
<?php
	}

	/**
	 * Print a single reminder row
	 *
	 * @since 3.5
	 *
	 * @param array $reminder
	 */
	public function single_row( $reminder ) {
		echo '<tr id="reminder-' . $reminder['order_id'] . '" class="reminder-row">';
		$this->single_row_columns( $reminder );
		echo "</tr>\n";
	}

	/**
	 * Print checkbox column
	 *
	 * @since 3.5
	 *
	 * @param array $reminder The reminder.
	 */
	public function column_cb( $reminder ) {
		?>
			<label class="screen-reader-text" for="cb-select-<?php echo $reminder['order_id']; ?>"><?php _e( 'Select reminder', IVOLE_TEXT_DOMAIN ); ?></label>
			<input class="reminder-checkbox" id="cb-select-<?php echo $reminder['order_id']; ?>" type="checkbox" name="orders[]" value="<?php echo $reminder['order_id']; ?>" />
		<?php
	}

	/**
	 * Print the order column
	 *
	 * @since 3.5
	 *
	 * @param array $reminder The reminder.
	 */
	public function column_order( $reminder ) {
		?>
		<a href="<?php echo esc_url( get_edit_post_link( $reminder['order_id'] ) ); ?>"><?php echo $reminder['order_id']; ?></a>
		<?php
	}

	/**
	 * Print the customer column
	 *
	 * @since 3.5
	 *
	 * @param array $reminder The reminder.
	 */
	public function column_customer( $reminder ) {
		?>
		<strong><?php echo $reminder['customer_name']; ?></strong>
		<br>
		<a href="<?php echo 'mailto:' . $reminder['customer_email']; ?>"><?php echo $reminder['customer_email']; ?></a>
		<?php
	}

	/**
	 * Print the scheduled column
	 *
	 * @since 3.5
	 *
	 * @param array $reminder The reminder.
	 */
	public function column_scheduled( $reminder ) {
		echo date( 'Y-m-d ' . get_option( 'time_format' ), $reminder['timestamp'] );
	}

	/**
	 * Print the actions column
	 *
	 * @since 3.5
	 *
	 * @param array $reminder The reminder.
	 */
	public function column_actions( $reminder ) {
		$out = '';
		$url = admin_url( 'admin.php?page=ivole-reviews-reminders' );

		$cancel_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'cancelreminder',
					'order_id' => $reminder['order_id']
				),
				$url
			),
			'manage-reminders'
		);

		$send_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'sendreminder',
					'order_id' => $reminder['order_id']
				),
				$url
			),
			'manage-reminders'
		);
		?>
		<a href="<?php echo $cancel_url; ?>" class="action-link cancel" style="color: #a00;" aria-label="<?php echo esc_attr__( 'Cancel', IVOLE_TEXT_DOMAIN ); ?>"><?php _e( 'Cancel', IVOLE_TEXT_DOMAIN ); ?></a>
		&nbsp;|&nbsp;
		<a href="<?php echo $send_url; ?>" class="action-link send" aria-label="<?php echo esc_attr__( 'Send Now', IVOLE_TEXT_DOMAIN ); ?>"><?php _e( 'Send Now', IVOLE_TEXT_DOMAIN ); ?></a>
		<?php
	}
}


endif;
