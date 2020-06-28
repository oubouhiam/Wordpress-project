<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Scheduled Review Reminders', IVOLE_TEXT_DOMAIN ); ?></h1>

    <?php
    if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
        echo '<span class="subtitle">';
        /* translators: %s: search keywords */
        printf(
            __( 'Search results for &#8220;%s&#8221;', IVOLE_TEXT_DOMAIN ),
            wp_html_excerpt( esc_html( wp_unslash( $_REQUEST['s'] ) ), 50, '&hellip;' )
        );
        echo '</span>';
    }
    ?>

    <hr class="wp-header-end">
    <p><?php esc_attr_e( 'A list of review reminders scheduled by the plugin to be sent in the future.', IVOLE_TEXT_DOMAIN ); ?></p>

    <?php
    if( 'cr' === get_option( 'ivole_scheduler_type', 'wp' ) ) {
      if( $list_table->get_pagination_arg( 'total_items' ) > 0 ) {
        echo '<div style="background-color:#90ee90;padding:3px 5px;margin:1em 0;font-weight:bold;">CR Cron is used for scheduling reminders (view <a href="' . admin_url( 'admin.php?page=ivole-reviews-settings' ) . '" title="Plugin Settings">settings</a>). The table below might not display all reminders. Please log in to your account on <a href="https://www.cusrev.com/login.html" target="_blank" rel="noopener noreferrer">CR website</a> to view and manage the reminders.</div>';
      } else {
        echo '<div style="background-color:#90ee90;padding:3px 5px;margin:1em 0;font-weight:bold;">CR Cron is used for scheduling reminders.</div>';
      }
    }
    ?>

    <?php $list_table->views(); ?>

    <form id="reminders-form" method="get">
        <?php $list_table->search_box( __( 'Search Reminders', IVOLE_TEXT_DOMAIN ), 'reminders' ); ?>
        <input type="hidden" name="page" value="ivole-reviews-reminders" />
        <input type="hidden" name="_total" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'total_items' ) ); ?>" />
        <input type="hidden" name="_per_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'per_page' ) ); ?>" />
        <input type="hidden" name="_page" value="<?php echo esc_attr( $list_table->get_pagination_arg( 'page' ) ); ?>" />

        <?php if ( isset( $_REQUEST['paged'] ) ) { ?>
            <input type="hidden" name="paged" value="<?php echo esc_attr( absint( $_REQUEST['paged'] ) ); ?>" />
        <?php } ?>

        <?php $list_table->display(); ?>
	</form>

	<script type="text/javascript">
		jQuery(function($) {
			var ivole_confirm_cancel = function() {
				return confirm("<?php esc_attr_e( 'Are you sure you want to cancel the review reminder(s)?', IVOLE_TEXT_DOMAIN ); ?>");
			};

			var ivole_confirm_send = function() {
				return confirm("<?php esc_attr_e( 'Are you sure you want to send the review reminder(s) now?', IVOLE_TEXT_DOMAIN ); ?>");
			};

			$('.action-link.cancel').on('click', function(event) {
				if (!ivole_confirm_cancel()) {
					event.preventDefault();
				}
			});

			$('.action-link.send').on('click', function(event) {
				if (!ivole_confirm_send()) {
					event.preventDefault();
				}
			});

			$('#reminders-form').on('submit', function(event) {
				var action = $('#bulk-action-selector-top').val();
				var selected = $('input.reminder-checkbox:checked');

				if (selected.length) {
					if (action == 'cancel') {
						if (!ivole_confirm_cancel()) {
							event.preventDefault();
						}
					} else if (action == 'send') {
						if (!ivole_confirm_send()) {
							event.preventDefault();
						}
					}
				}
			});
		});
	</script>
</div>
