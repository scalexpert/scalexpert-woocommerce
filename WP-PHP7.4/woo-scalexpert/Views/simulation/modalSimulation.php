<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress / Woocommerce.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */
?>
<?php if (!empty($solutions)) { ?>
    <div id="<?php echo $md5GroupSolution ?? ''; ?>"
         class="sep_contentModal modal fade product-comment-modal sep-SimulationsModal"
         role="dialog"
         aria-hidden="true"
         data-id="<?php echo $md5GroupSolution ?? ''; ?>"
    >
        <div class="modal-dialog" role="document">
            <div class="sep-Simulations-groupSolution"
                 data-id="<?php echo $md5GroupSolution ?? ''; ?>">
                <?php
                foreach ($solutions as $key => $solution) {
                    if (!empty($solution)) {
                        $titleSolution = $solution['designConfiguration']['visualTitle'] ?? '';
                        $md5Solution = md5(($solution['designConfiguration']['solutionCode'] ?? '') . strip_tags($titleSolution) . ($solution['duration'] ?? ''));
                        $current = $key;
                        include(plugin_dir_path(__FILE__) . 'modalSimulationContent.php');
                    }
                }
                ?>
            </div>
        </div>
    </div>
<?php } ?>
