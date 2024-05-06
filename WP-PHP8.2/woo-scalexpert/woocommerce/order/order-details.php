<?php
	/**
	 * Order details
	 *
	 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details.php.
	 *
	 * HOWEVER, on occasion WooCommerce will need to update template files and you
	 * (the theme developer) will need to copy the new files to your theme to
	 * maintain compatibility. We try to do this as little as possible, but it does
	 * happen. When this occurs the version of the template file will be bumped and
	 * the readme will list any important changes.
	 *
	 * @see     https://docs.woocommerce.com/document/template-structure/
	 * @package WooCommerce\Templates
	 * @version 4.6.0
	 */
	/**  SG Paiement en plusieurs fois */
	require_once( PLUGIN_DIR . '/Static/StaticData.php' );
	$solutionnames = SCALEXPERTSOLUTIONS;
	/**  SG Paiement en plusieurs fois */
	
	defined( 'ABSPATH' ) || exit;
	$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( ! $order ) {
		return;
	}
	$order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
	$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
	$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
	$downloads             = $order->get_downloadable_items();
	$show_downloads        = $order->has_downloadable_item() && $order->is_download_permitted();
	
	if ( $show_downloads ) {
		wc_get_template(
			'order/order-downloads.php',
			array(
				'downloads'  => $downloads,
				'show_title' => TRUE,
			)
		);
	}


?>
	<section class="woocommerce-order-details">
		<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>
		
		<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'Order details', 'woocommerce' ); ?></h2>
		<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
			<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
				do_action( 'woocommerce_order_details_before_order_table_items', $order );
				
				foreach ( $order_items as $item_id => $item ) {
					$product = $item->get_product();
					
					wc_get_template(
						'order/order-details-item.php',
						array(
							'order'              => $order,
							'item_id'            => $item_id,
							'item'               => $item,
							'show_purchase_note' => $show_purchase_note,
							'purchase_note'      => $product ? $product->get_purchase_note() : '',
							'product'            => $product,
						)
					);
				}
				
				do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
			</tbody>
			<tfoot>
			<?php
				foreach ( $order->get_order_item_totals() as $key => $total ) {
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
						<td>
							<?php
								
								/**
								 * SG Paiement en plusieurs fois
								 */
								if ( ( 'payment_method' === $key ) && $order->get_payment_method() == "scalexpert" ) {
									$productController  = new \wooScalexpert\Controller\Front\ProductController();
									$scalexpertSolution = $order->get_meta( 'scalexpert_solution' );
									$DesignSolution     = get_option( 'sg_scalexpert_design_' . $scalexpertSolution );
									$CommunicationKit   = $productController->getCommunicationKit( $scalexpertSolution );
									
									print ( $DesignSolution['bloc_title'] != "" ) ? "<div class='scalexpert_title'>" . $DesignSolution['bloc_title'] . "</div>" : $CommunicationKit['visualTitle'];
									print "<div class='scalexpert_status'>" . $order->get_meta( 'scalexpert_status' ) . "</div>";
								} else {
									echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								/** SG Paiement en plusieurs fois */
							?></td>
					</tr>
					<?php
				}
			?>
			
			<?php if ( $order->get_customer_note() ) : ?>
				<tr>
					<th><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
			<?php endif; ?>
			</tfoot>
		</table>
		
		<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
	</section>


<?php
	
	$orderData   = $order->get_data();
	$orderStatus = $order->get_status();
	if ( $order->get_payment_method() == "scalexpert" && $orderStatus == "failed" ) {
		$scalexpertFinID = $order->get_meta( 'scalexpert_finID' );
		$notes           = wc_get_order_notes( [ 'order_id' => $order_id ] );
		WC()->cart->empty_cart();
		
		print "<section class='sg-account'>";
		print "<div class='sg-account-text'>";
		print __( "Financing application abandoned by the customer or following a technical incident", "woo-scalexpert" );
		print "</div>";
		
		
		/**
		 * Pour l'instant standby jusqu'à validation/livraison ticket #145132
		 */
		$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
		?>
		<form id="sg_newOrder" action="<?= $cart_page_url ?>" method="post">
			<input type="hidden" name="duplicateBasket" value="<?= $order_id ?>">
			<button class="button"
			        name="newBasket"
			        id="newBasket"
			        data-orderId="<?= $order_id ?>"
			        data-errorText="<?= __( "New order creation failed !", "woo-scalexpert" ) ?>"
			>
				<?= __( " Order again ", "woo-scalexpert" ) ?>
			</button>
		</form>
		</section>
		<?php
		
		
	}


?>

<?php
	/**
	 * Action hook fired after the order details.
	 *
	 * @param WC_Order $order Order data.
	 *
	 * @since 4.4.0
	 */
	do_action( 'woocommerce_after_order_details', $order );
	
	if ( $show_customer_details ) {
		wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
	}
	
	