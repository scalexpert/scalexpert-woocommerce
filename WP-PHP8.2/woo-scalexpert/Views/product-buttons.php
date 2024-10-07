<?php
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	/**
	 * DesignController
	 */
	$excludeCats = @$DesignSolution['exclude_cats'];
	$excludeCats = explode( ",", $excludeCats );
	$excludeCats = @array_intersect( $excludeCats, $categories );
	if ( @count( $excludeCats ) >= 1 ) {
		return FALSE;
	}
	if ( $DesignSolution['activate'] != 1 || $DesignSolution['blocposition'] != $position ) {
		return FALSE;
	}
	$visualTitle = ( $DesignSolution['bloc_title'] != "" ) ? $DesignSolution['bloc_title'] : $CommunicationKit['visualTitle'];
	$md5         = md5( $CommunicationKit['solutionCode'] . $CommunicationKit['buyerBillingCountry'] . $CommunicationKit['type'] );
?>

<div class="sep_main_productsButtons">
    <a class="sep_main_productsButtons-content"
       data-modal="sep_openModal"
       href="#<?= $md5 ?>"
       data-idmodal="#<?= $md5 ?>">
            <span class="sep_main_productsButtons-title">
                 <?= $visualTitle ?>
                <img class="sep_main_productsButtons-i"
                     src="<?= $CommunicationKit['visualInformationIcon'] ?>"
                     alt="Plus d'information"
                     width="16"
                     height="16">
            </span>
		<?php if ( $DesignSolution['showlogo'] == 1 ) { ?>
            <img class="sep_main_productsButtons-logo"
                 src="<?= $CommunicationKit['visualLogo'] ?>"
                 alt="<?= $DesignSolution['bloc_title'] ?>"
            >
		<?php } ?>
    </a>
</div>

<div id="<?= $md5 ?>"
     class="sep_contentModal modal fade product-comment-modal"
     role="dialog"
     aria-hidden="true"
>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="h2 modal-header-title">
                    <img src="<?= plugin_dir_url( __DIR__ ) ?>/assets/img/borrow.svg" alt="Emprunter">
					<?= $visualTitle ?>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
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

