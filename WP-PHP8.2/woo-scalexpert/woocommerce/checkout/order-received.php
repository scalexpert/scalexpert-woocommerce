<?php
/**
 * "Order received" message.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.3.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
	
	if ( $order ) {
		
		/**  SG Paiement en plusieurs fois */
		$classNotice = "success";
		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		
		$orderData   = $order->get_data();
		$orderStatus = $order->get_status();
		if ( $orderData['payment_method'] == "scalexpert" ) {
			$wcScalexpert = new WC_Scalexpert_Gateway();
			$apiClient    = new \wooScalexpert\Helper\API\Client;
			$API_CALL     = $wcScalexpert->update_scalexpert( $order->get_id() );
			$classNotice  = ( ( $API_CALL['API'] == TRUE ) && ( $API_CALL['FinancialStatus'] != "REJECTED" ) ) ? "success" : "error";
		}
		/**  SG Paiement en plusieurs fois */
		
		if ( $order->has_status( 'failed' ) ){
			$message = apply_filters(
				'woocommerce_thankyou_order_received_text',
				esc_html( __( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ) ),
				$order
			);
		}else{
			$message = apply_filters(
				'woocommerce_thankyou_order_received_text',
				esc_html( __( 'Thank you. Your order has been received.', 'woocommerce' ) ),
				$order
			);
		}
		
		?>
		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
			<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $message;
			?>
		</p>

<?php
	}else{
		?>
		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
			<?php
				
				$message = apply_filters(
					'woocommerce_thankyou_order_received_text',
					esc_html( __( 'Please connect to see your order information.', 'woocommerce' ) ),
					$order
				);
				
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $message;
			?>
		</p>
		<?php
	}
	

