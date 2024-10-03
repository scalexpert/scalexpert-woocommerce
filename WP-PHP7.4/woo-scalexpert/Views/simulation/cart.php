<?php
    /**
     * Copyright © Scalexpert.
     * This file is part of Scalexpert plugin for Wordpress / Woocommerce.
     *
     * @author    Société Générale
     * @copyright Scalexpert
     */
?>
<div class="sep-Simulations-Product">
    <?php
        if ( !empty( $simulations ) ) {
            global $isSimulation;
            $solutions        = $simulations;
            $md5GroupSolution = md5( 'all' . sizeof( $solutions ) . rand( 0, 100 ) );
            $isSimulation     = "cart";
            $cartTotal        = $price;
            
            include_once( plugin_dir_path( __FILE__ ) . 'solution.php' );
            include_once( plugin_dir_path( __FILE__ ) . 'modalSimulation.php' );
        }
    ?>
</div>

