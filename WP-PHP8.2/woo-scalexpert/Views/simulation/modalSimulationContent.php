<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress / Woocommerce.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */
?>

<?php
if (!empty($solution)) {
    ?>
    <div class="sep-Simulations-solution modal-dialog"
         data-id="<?php echo $md5Solution ?? ''; ?>"
         role="document"
    >
        <div class="modal-content">
            <div class="modal-header">
                <div class="h2 modal-header-title">
                    <?php echo $titleSolution; ?>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="sep-SimulationsModal-mainContent">
                    <div class="sep-SimulationsModal-mainContent-left">
                        <?php echo $solution['designConfiguration']['visualAdditionalInformation'] ?? ''; ?>
                    </div>
                    <div class="sep-SimulationsModal-mainContent-right">
                        <div class="sep-SimulationsModal-mainContent-right-content">
                            <div class="sep-SimulationsModal-mainContent-right-top">
                                Simulez votre paiement

                                <?php
                                if (!empty($solution['designConfiguration']['displayLogo'])) {
                                    ?>
                                    <img class="-logo"
                                         src="<?php echo $solution['designConfiguration']['visualLogo'] ?? '' ?>"
                                         alt="Logo <?php echo strip_tags($titleSolution) ?? '' ?>"
                                    >
                                <?php } ?>
                            </div>

                            <?php
                            $isModal = TRUE;
                            include(plugin_dir_path(__FILE__) . 'listSimulations.php');
                            include(plugin_dir_path(__FILE__) . 'infoMonthlyPayment.php');
                            include(plugin_dir_path(__FILE__) . 'listInstallments.php');
                            ?>
                        </div>
                    </div>
                </div>

                <div class="part_bottom">
                    <?php echo $solution['designConfiguration']['visualLegalText'] ?? '' ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
