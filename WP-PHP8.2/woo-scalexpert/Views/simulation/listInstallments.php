<?php
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	global $product;
    $financedAmountFormatted = ( isset( $isSimulation ) && $isSimulation == "product") ? wc_price(wc_get_price_including_tax($product)) : $cartTotal;
	
	/*print "<pre>";
	print_r( $solution );
	print "</pre>";*/
	
	if ( ! empty( $solution ) && ! empty( $solution['installments'] ) ) {
		?>
        <div class="sep-Simulations-installments">
            <div class="sep-Simulations-installments-total sep-Simulations-installments-item">
            <span class="sep-Simulations-installments-item-label">
                Montant total dû
            </span>
                <span class="sep-Simulations-installments-item-value">
                <?= $solution['dueTotalAmountFormatted'] ?>
            </span>
            </div>
			
			<?php foreach ( $solution['installments'] as $iteration => $installment ) {
				$iteration += 1;
				if ( ! empty( $installment ) ) {
					?>
                    <div class="sep-Simulations-installments-item">
                    <span class="sep-Simulations-installments-item-label">
                        <?php if ( ! empty( $solution['isLongFinancingSolution'] ) ) { ?>
                            Payer en <?= $solution['duration'] ?> fois
                        <?php } else {
	                        if ( $iteration == 1 ) {
		                        ?>
                                Aujourd'hui
	                        <?php } else { ?>
		                        <?= $iteration ?> ème prélèvement
	                        
	                        <?php }
                        } ?>
                    </span>
                        <span class="sep-Simulations-installments-item-value">
                        <?= $installment['amountFormatted'] ?>
                    </span>
                    </div>
					
					<?php
				}
			} ?>

            <div class="sep-Simulations-installments-mentions">
                <span>Montant du financement :&nbsp;<?= $financedAmountFormatted ?>.</span>&nbsp;
                <span>TAEG FIXE :&nbsp;<?= $solution['effectiveAnnualPercentageRateFormatted'] ?>.</span>&nbsp;
				
				<?php
					if ( ! empty( $solution['isLongFinancingSolution'] ) ) {
						?>
                        <span>Taux débiteur fixe :&nbsp;<?= $solution['nominalPercentageRateFormatted'] ?>.</span>&nbsp;
                        Coût du crédit :&nbsp;<?= $solution['totalCostFormatted'] ?>.<br/>
					<?php } ?>
				
				<?php
					if ( ! empty( $solution['isLongFinancingSolution'] ) ) {
						?>
                        <span>Frais de dossier :&nbsp;<?= $solution['feesAmountFormatted'] ?>.</span>&nbsp;
					<?php } else { ?>
                        <span>Frais : <?= $solution['feesAmountFormatted'] ?>.</span>&nbsp;
					<?php } ?>

                <span>Montant total dû :&nbsp;<?= $solution['dueTotalAmountFormatted'] ?>.</span>&nbsp;
            </div>
        </div>
	<?php }
 
