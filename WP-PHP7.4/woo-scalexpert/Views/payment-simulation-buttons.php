<?php
	
	/**
	 * DesignController
	 */
	global $productController;
	$DesignSolution['showlogo_cart'] = 1;
	$showlogoCart                    = 0;
	$solutionname                    = $productController->getTitleBySolution( $CommunicationKit['solutionCode'], 'solutionName' );
	$DesignSolution                  = get_option( 'sg_scalexpert_design_' . $CommunicationKit['solutionCode'] );
	$visualTitle                     = ( $DesignSolution['payment_title'] != "" ) ? $DesignSolution['payment_title'] : $CommunicationKit['visualTitle'];
	
	if ( isset( $DesignSolution['showlogo_cart'] ) ) {
		$showlogoCart = $DesignSolution['showlogo_cart'];
	}
	
	if ( ! empty( $simulation ) ) {
		$isSimulation     = "checkout";
		$md5GroupSolution = md5( 'all' . sizeof( $solutions ) . rand( 0, 100 ) );
		$solutions        = $simulation;
		$current          = array_key_first( $simulation );
		$solution         = array_shift( $simulation );
		$md5Solution      = md5( ( $solution['designConfiguration']['solutionCode'] ?? '' ) . strip_tags( ( $solution['designConfiguration']['visualTitle'] ?? '' ) ) . ( $solution['duration'] ?? '' ) );
	}
?>

<li class="list-group-item sep_financialSolution sep-Simulations">
    <div class="sep_financialSolution-content">
        <input type="radio" name="solutionCode" value="<?=  $CommunicationKit['solutionCode'] ?>" <?php if ($nbSolutions === 1) { ?> checked <?php } ?>>
        <div class="sep_financialSolution-content-wrapper">
            <div class="sep_financialSolution-content-top">
				<?php if ( $showlogoCart == 1 ) { ?>
                    <img class="sep_financialSolution-content-logo"
                         src="<?= $CommunicationKit['visualLogo'] ?>"
                         alt="<?= $DesignSolution['payment_title'] ?>">
				<?php } ?>

                <div class="sep_financialSolution-title">
					<?= $visualTitle ?>
                </div>

                <img class="sep_financialSolution-i"
                     src="<?= $CommunicationKit['visualInformationIcon'] ?>"
                     alt="Information"
                     width="16"
                     height="16"
                     data-modal="sep_openModal"
                     data-idmodal="#<?= $md5GroupSolution ?>">
            </div>
			
			<?php
				if ( empty( $solution['isLongFinancingSolution'] ) ) {
					$isModal = FALSE;
					include( plugin_dir_path( __FILE__ ) . 'simulation/infoMonthlyPayment.php' );
				}
			?>
        </div>
    </div>
	
	<?php if ( ! empty( $solutions ) ) { ?>
        <div id="<?= $md5GroupSolution ?>"
             class="sep_contentModal modal fade product-comment-modal sep-SimulationsModal"
             role="dialog"
             aria-hidden="true"
             data-id="<?php echo $md5GroupSolution ?? ''; ?>"
        >
            <div class="modal-dialog"
                 role="document">
                <div class="sep-Simulations-groupSolution"
                     data-id="<?php echo $md5GroupSolution ?? ''; ?>">
					<?php
						foreach ( $solutions as $key => $solution ) {
							if ( ! empty( $solution ) ) {
								$titleSolution = $solution['designConfiguration']['visualTitle'] ?? '';
								$md5Solution   = md5( ( $solution['designConfiguration']['solutionCode'] ?? '' ) . strip_tags( $titleSolution ) . ( $solution['duration'] ?? '' ) );
								$current       = $key;
								include( plugin_dir_path( __FILE__ ) . 'simulation/modalSimulationContent.php' );
							}
						}
					?>
                </div>
            </div>
        </div>
	<?php } ?>
</li>
