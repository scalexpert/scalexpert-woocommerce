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
	$md5                             = md5( $CommunicationKit['solutionCode'] . $CommunicationKit['buyerBillingCountry'] . $CommunicationKit['type'] );
	if ( isset( $DesignSolution['showlogo_cart'] ) ) {
		$showlogoCart = $DesignSolution['showlogo_cart'];
	}

?>

<li class="list-group-item sep_financialSolution">
	<?php if ( $showlogoCart == 1 ) { ?>
        <img src="<?= $CommunicationKit['visualLogo'] ?>"
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
         data-idmodal="#<?= $md5 ?>"
    >

    <input type="radio" name="solutionCode" value="<?=  $CommunicationKit['solutionCode'] ?>">
    <div id="<?= $md5 ?>"
         class="sep_contentModal modal fade product-comment-modal"
         role="dialog"
         aria-hidden="true"
    >
        <div class="modal-dialog"
             role="document"
        >
            <div class="modal-content">
                <div class="modal-header">
                    <div class="h2 modal-header-title">
                        <img src="<?= plugin_dir_url( __DIR__ ) ?>/assets/img/borrow.svg"
                             alt="Emprunter"
                        >
						<?= $visualTitle ?>
                    </div>
                    <button type="button"
                            class="close"
                            data-dismiss="modal"
                            aria-label="Fermer"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
					<?= $CommunicationKit['visualAdditionalInformation'] ?>
					<?= $CommunicationKit['visualLegalText'] ?>
                </div>
            </div>
        </div>
    </div>
</li>