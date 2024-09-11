<?php
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
?>

<?php
	if ( ! empty( $solution['installments'][0]['amount'] ) ) {
		?>

        <div class="sep-Simulations-solution-infoMonthlyPayment">
			<?php
				if ( ! empty( $solution['hasFeesOnFirstInstallment'] ) ) {
					?>
                    soit un <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label">1er prélèvement</span> de
                    <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value"><?php echo $solution['installments'][0]['amountFormatted'] ?? '' ?></span>
					<?php
					if ( ! empty( $solution['totalCost'] ) ) {
						echo '(frais inclus)';
					}
					?>
                    puis <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label"><?php echo( sizeof( $solution['installments'] ) - 1 ) ?> prélèvements</span> de
                    <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value"><?php echo( $solution['installments'][1]['amountFormatted'] ?? '' ) ?></span>
					<?php
				} else {
					if ( ! empty( $isModal ) ) {
						?>
                        soit <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-label"><?php echo $solution['duration'] ?? '' ?> prélèvements</span> de
                        <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value"><?php echo $solution['installments'][0]['amountFormatted'] ?? '' ?></span>
						<?php
					} else {
						?>
                        soit <span class="sep-color sep-Simulations-solution-infoMonthlyPayment-value"><?php echo $solution['installments'][0]['amountFormatted'] ?? '' ?> / mois</span>
						<?php
					}
				}
			?>
        </div>
	<?php } ?>
