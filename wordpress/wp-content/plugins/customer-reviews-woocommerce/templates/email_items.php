<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border: 1px solid #eee; border-collapse: collapse;">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Product', 'ivole' ); ?></th>
			<th class="td" scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Quantity', 'ivole' ); ?></th>
			<th class="td" scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Price', 'ivole' ); ?></th>
			<th class="td" scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Review', 'ivole' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if( true === $ivole_test ) {
			?>
			<tr>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
					Item 1 Test
				</td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">2</td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo wc_price( 15, get_woocommerce_currency() ); ?></td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo '<a href="' . get_permalink( woocommerce_get_page_id( 'shop' ) ) . '" title="' . __( 'Review', 'ivole' ) . '">' . __( 'Review', 'ivole' ) . '</a>'; ?></td>
			</tr>
			<tr>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;">
					Item 2 Test
				</td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;">1</td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo wc_price( 150, get_woocommerce_currency() ); ?></td>
				<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo '<a href="' . get_permalink( woocommerce_get_page_id( 'shop' ) ) . '" title="' . __( 'Review', 'ivole' ) . '">' . __( 'Review', 'ivole' ) . '</a>'; ?></td>
			</tr>
			<?php
		} else {
			if( $items ) {
				foreach ( $items as $item_id => $item ) :

					// check if an item needs to be skipped because none of categories it belongs to has been enabled for reminders
					if( $enabled_for === 'categories' ) {
						$skip = true;
						$categories = get_the_terms( $item['product_id'], 'product_cat' );
						foreach ( $categories as $category_id => $category ) {
							if( in_array( $category->term_id, $enabled_categories ) ) {
								$skip = false;
								break;
							}
						}
						if( $skip ) {
							continue;
						}
					}
					$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
					//$item_meta    = new WC_Order_Item_Meta( $item, $_product );

					if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
						?>
						<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
							<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;"><?php

								// Product name
								echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false );

								// Variation
								// if ( ! empty( $item_meta->meta ) ) {
								// 	echo '<br/><small>' . nl2br( $item_meta->display( true, true, '_' ) ) . '</small>';
								// }

							?></td>
							<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item ); ?></td>
							<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td>
							<td class="td" style="text-align:left; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo '<a href="' . get_permalink( $item['product_id'] ) . '" title="' . __( 'Review', 'ivole' ) . '">' . __( 'Review', 'ivole' ) . '</a>'; ?></td>
						</tr>
						<?php
					}
				endforeach;
			}
		}
		?>
	</tbody>
	<tfoot>
	</tfoot>
</table>
<br/>
