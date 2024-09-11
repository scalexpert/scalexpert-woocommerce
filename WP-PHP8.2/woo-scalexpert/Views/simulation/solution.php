<?php
/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for Wordpress / Woocommerce.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */


if (!empty($solutions)) { ?>

    <div class="sep-Simulations-Product-groupSolution sep-Simulations-groupSolution"
         data-id="<?php echo $md5GroupSolution; ?>">
        <?php
        foreach ($solutions as $key => $solution) {
            $titleSolution = $solution['designConfiguration']['visualTitle'] ?? "";
            $md5Solution = md5(($solution['designConfiguration']['solutionCode'] ?? '') . strip_tags($titleSolution) . ($solution['duration'] ?? ''));
            ?>

            <div class="sep-Simulations-Product-solution sep-Simulations-solution"
                 data-id="<?php echo $md5Solution; ?>"
            >
                <span class="sep-Simulations-solution-top">
                    <?php echo $titleSolution;

                    if (!empty($solution['designConfiguration']['visualInformationIcon'])) {
                        ?>
                        <a data-modal="sep_openModal" href="#<?php echo $md5GroupSolution; ?>" title="Information">
                            <img class="sep-Simulations-solution-i"
                                 src="<?php echo $solution['designConfiguration']['visualInformationIcon']; ?>"
                                 alt="Information"
                                 width="16"
                                 height="16"
                            >
                        </a>
                        <?php
                    }
                    if (!empty($solution['designConfiguration']['displayLogo'])) {
                        ?>
                        <img class="sep-Simulations-solution-logo"
                             src="<?php echo $solution['designConfiguration']['visualLogo'] ?? '' ?>"
                             alt="Logo <?php echo strip_tags($titleSolution) ?>"
                        >
                    <?php } ?>
                </span>
                <div class="sep-Simulations-solution-infoSimulation">
                    <?php
                    $isModal = FALSE;
                    $current = $key;
                    include(plugin_dir_path(__FILE__) . 'listSimulations.php');
                    include(plugin_dir_path(__FILE__) . 'infoMonthlyPayment.php');
                    ?>
                </div>

                <?php if (!empty($solution['designConfiguration']['visualDescription'])) { ?>
                    <div class="sep-Simulations-solution-visualDescription">
                        <?php echo $solution['designConfiguration']['visualDescription'] ?? '' ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
<?php } ?>
