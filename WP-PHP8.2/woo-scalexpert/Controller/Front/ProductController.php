<?php

/**
 * Copyright © Scalexpert.
 * This file is part of Scalexpert plugin for WordPress.
 *
 * @author    Société Générale
 * @copyright Scalexpert
 */


namespace wooScalexpert\Controller\Front;

use GuzzleHttp\Exception\GuzzleException;
use wooScalexpert\Helper\API\Client;
use wooScalexpert\Helper\API\SimulationFormatter;


class ProductController
{

    public static array $eFinancingAmounts = ["500", "1000"];
    public static array $eFinancingCountries = ["FR"];
    public static string $scope = 'e-financing';
    private array $solutionnames;
    private Client $apiclient;


    /**
     *
     */
    public function __construct()
    {
        require(PLUGIN_DIR . '/Static/StaticData.php');

        $this->solutionnames = SCALEXPERTSOLUTIONS;

    }


    /**
     * @param $template
     * @param $position
     * @param $categories
     * @param $price
     *
     * @return false|void
     * @throws GuzzleException
     */
    public function showActifSolutions($template = "product-buttons", $position = "under", $categories = array(), $price = NULL)
    {

        global $product;
        $price = ($price != NULL) ? $price : $product->get_price();
        $productID = ($template != "payment-buttons" && $template != "payment-simulation-buttons") ? $product->get_id() : "";

        $config = get_option('sg_scalexpert_keys');
        if (!isset($config['activate'])) {
            return FALSE;
        }

        if ($template == 'payment-buttons' || $template == "payment-simulation-buttons") {
            $solutions = $this->getEligibleSolutions($price, "", SCALEXPERT_APICACHE);
            $solutions = $this->eligibleSolutions4CheckOut($solutions);
        } else {
            $solutions = $this->getEligibleSolutions($price, $productID, '');
        }
        $groupFinancingSolution = get_option("sg_scalexpert_group_financing_solution");


        echo '<!-- begin /Views/' . $template . '.php -->';
        if (!$solutions && $template == 'payment-buttons') {
            echo __('Cart value or product not eligible for Scalexpert financing !', 'woo-scalexpert');
        } else {
            foreach ($solutions as $solution) {
                $solution = $solution['solutionCode'];
                $actif = array();
                $actif = get_option('sg_scalexpert_activated_' . $solution);
                if (isset($actif['activate']) && $actif['activate'] == 1) {
                    $solutionname = $this->getTitleBySolution($solution, 'solutionName');
                    $CommunicationKit = $this->getCommunicationKit($solution);
                    $DesignSolution = get_option('sg_scalexpert_design_' . $solution);
                    if (SCALEXPERT_SHOWSIMULATION) {
                        $locale = explode('_', get_locale());
                        $locale = strtoupper($locale[0]);
                        $simulation = $this->apiclient->getSimulateFinancing4Checkout($price, $locale, $solution);
                        $cartTotal = wc_price($price);
                    }
                    include(plugin_dir_path(__FILE__) . '../../Views/' . $template . '.php');
                }
            }
        }
        // Paiements groupés / non groupés
        if ($groupFinancingSolution['group_financing_solution'] != 1) {
            ?>
            <script>
                jQuery(document).ready(function () {
                    jQuery("div.payment_method_scalexpert").attr("style", "block");
                });
                jQuery("input[name=solutionCode]:radio").click(function () {
                    jQuery("#payment_method_scalexpert").attr('checked', true);
                });
            </script>
            <?php
        }
        echo '<!-- end ' . plugin_dir_path(__FILE__) . '/Views/' . $template . '.php -->';
    }


    /**
     * @param $price
     * @param string $locale
     *
     * @return void
     */
    public function showSimulation4Product($price = NULL, string $locale = "FR"): void
    {
        global $product;
        global $post;

        $terms = get_the_terms($post->ID, 'product_cat');
        foreach ($terms as $term) {
            $product_cat_id = $term->term_id;
            break;
        }

        $locale = explode('_', $locale);
        $locale = strtoupper($locale[0]);
        $simulations = (SCALEXPERT_APICACHE) ? get_transient("scalexpertProductSimulation_" . $product->get_id() . "_" . $locale) : NULL;

        if (empty($simulations)) {
            $this->apiclient = new Client();
            try {
                $price = ($price != NULL) ? $price : $product->get_price();
                $simulations = $this->apiclient->getSimulateFinancing4Product($price, $locale, $product_cat_id);
            } catch (Exception $e) {
                echo 'Exception reçue : ', $e->getMessage(), "\n";
            }
            asort($simulations);
            $t = (SCALEXPERT_APICACHE) ? Set_transient("scalexpertProductSimulation_" . $product->get_id() . "_" . $locale, $simulations, SCALEXPERT_TRANSIENTS) : NULL;

        }
        /**
         * Sometimes simulations won't show up ...
         */
        $simulations = (SCALEXPERT_APICACHE) ? get_transient("scalexpertProductSimulation_" . $product->get_id() . "_" . $locale) : $simulations;

        echo '<!-- begin /Views/productFinancialSimulationContent.php -->';
        include_once(plugin_dir_path(__FILE__) . '../../Views/productFinancialSimulationContent.php');
        echo '<!-- end /Views/productFinancialSimulationContent.php -->';

    }

    /**
     * @param $price
     * @return void
     */
    public function showSimulation4Cart( $price = NULL ): void
    {
        $this->apiclient = new Client();
        $formatter = new SimulationFormatter();
        $solutions       = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertCart_" . $price ) : NULL;
        if ( empty( $solutions ) ) {
            $solutions = $this->apiclient->getFinancialSolutions( floatval( $price ), "", "raw" );
            if (SCALEXPERT_APICACHE) {
                Set_transient("scalexpertCart_" . $price, $solutions, SCALEXPERT_TRANSIENTS);
            }
        }
        $solutions = $this->eligibleSolutions4Cart( $solutions[ 'contentsDecoded' ] );

        $eligibleSimulations = array();
        $solutionCodes = [];
        foreach ( $solutions[ "contentsDecoded" ][ "solutions" ] as $key => $solution ) {
            $solutionCodes[] = $solution[ 'solutionCode' ];
        }
        $simulationCart = $this->apiclient->getSimulateFinancing4Cart( floatval( $price ), "FR", $solutionCodes );
        foreach ($simulationCart['contentsDecoded']['solutionSimulations'] as $simulation) {
            $eligibleSimulations[$simulation['solutionCode']] = $simulation;
        }
        $designData  = $formatter->buildDesignData( $eligibleSimulations, $solutions, FALSE, TRUE );
        $simulations = $formatter->normalizeSimulations( $simulationCart, $designData[ 'designSolutions' ], FALSE, TRUE );

        asort( $simulations );

        $isSimulation = "cart";
        $cartTotal    = $price;
        echo '<!-- begin /Views/productFinancialSimulationContent.php -->';
        include_once( plugin_dir_path( __FILE__ ) . '../../Views/cartFinancialSimulationContent.php' );
        echo '<!-- end /Views/productFinancialSimulationContent.php -->';
    }


    /**
     * @param $solutionCode
     *
     * @return mixed
     *
     */
    public function getCommunicationKit($solutionCode)
    {
        $CommunicationKit = get_transient($solutionCode);
        if (!$CommunicationKit) {
            $this->setCommunicationKitTransients();
            $CommunicationKit = get_transient($solutionCode);
        }

        return $CommunicationKit;
    }


    /**
     * @return void
     *
     */
    public function setCommunicationKitTransients()
    {
        $scalexpertActivated = get_option('sg_scalexpert_keys');
        if (isset($scalexpertActivated['activate'])) {
            $eFinancingSolutions = $this->getEligibleSolutions();
            foreach ($eFinancingSolutions as $solutionCode => $solution) {
                Set_transient($solutionCode, $solution, SCALEXPERT_TRANSIENTS);
            }
        }
    }


    /**
     * @param $price
     * @param $productID
     * @param $apiCache
     *
     * @return array
     */
    public function getEligibleSolutions($price = NULL, $productID = NULL, $apiCache = FALSE): array
    {

        if ($price && $productID) {
            $solutions = ($apiCache) ? get_transient("scalexpertProduct_" . $productID) : NULL;
            if (empty($solutions)) {
                require_once(PLUGIN_DIR . '/Helper/API/Client.php');
                $this->apiclient = new Client();
                try {
                    $apiCall = $this->apiclient->getFinancialSolutions($price);
                    $r = ($apiCache) ? Set_transient("scalexpertProduct_" . $productID, $apiCall, SCALEXPERT_TRANSIENTS) : $apiCache;
                    if (!$apiCache) {
                        return $apiCall;
                    } else {
                        return $solutions = get_transient("scalexpertProduct_" . $productID);
                    }
                } catch (Exception $e) {
                    echo 'Exception reçue : ', $e->getMessage(), "\n";
                }
                $solutions = array();
            }

            return $solutions;
        }

        require_once(PLUGIN_DIR . '/Helper/API/Client.php');
        $this->apiclient = new Client();

        return $this->apiclient->getFinancialSolutions($price);
    }

    /**
     * @param array $solutions
     * @return false|mixed
     */
    public function eligibleSolutions4Cart(array $solutions = array() ): mixed
    {

        $eligible = array();
        unset( $solutions[ 'content' ] );

        $checkOutProdCats = array();
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $terms = get_the_terms( $cart_item[ 'product_id' ], 'product_cat' );
            foreach ( $terms as $term ) {
                $checkOutProdCats[] = $term->term_id;
            }
        }

        foreach ( $solutions [ 'solutions' ] as $key => $solution ) {
            $actif = get_option( 'sg_scalexpert_activated_' . $solution[ 'solutionCode' ] );
            if ( empty( $actif[ 'activate' ] ) ) {
                unset( $solutions [ 'solutions' ] [ $key ] );
            }
            $excluded = get_option( 'sg_scalexpert_design_' . $solution[ 'solutionCode' ] );
            if ( empty( $excluded[ 'activateCart' ] ) ) {
                unset( $solutions [ 'solutions' ] [ $key ] );
            }
            $excluded = ( !empty( $excluded[ 'exclude_cats' ] ) ) ? explode( ",", $excluded[ 'exclude_cats' ] ) : [];
            $result   = array_intersect( $checkOutProdCats, $excluded );
            if ( $result ) {
                unset( $solutions [ 'solutions' ] [ $key ] );
            }
        }

        $eligible[ 'contentsDecoded' ] = $solutions;
        return $eligible;
    }

    /**
     * @param $solutions
     *
     * @return mixed
     */
    public function eligibleSolutions4CheckOut($solutions)
    {

        $checkOutProdCats = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $terms = get_the_terms($cart_item['product_id'], 'product_cat');
            foreach ($terms as $term) {
                $checkOutProdCats[] = $term->term_id;
            }
        }

        foreach ($solutions as $key => $solution) {
            $actif = get_option('sg_scalexpert_activated_' . $key);
            if (empty($actif['activate'])) {
                unset($solutions[$key]);
            }
            $excluded = get_option('sg_scalexpert_design_' . $key);
            $excluded = (!empty($excluded['exclude_cats'])) ? explode(",", $excluded['exclude_cats']) : [];
            $result = array_intersect($checkOutProdCats, $excluded);
            if ($result) {
                unset($solutions[$key]);
            }
        }

        return $solutions;

    }


    /**
     * @param $solution
     * @param $output
     *
     * @return string
     */
    public function getTitleBySolution($solution, $output = NULL): string
    {

        $data = $this->solutionnames;
        if (isset($data[$solution]) && $output == "solutionName") {
            //if ( $output == "solutionName" ) {
            return $data[$solution];
        }

        if (isset($data[$solution['solutionCode']])) {
            return "<img src='" . plugins_url('/woo-scalexpert/assets/img/' . strtolower($solution['marketCode']) . '.jpg') . "'> " . $data[$solution['solutionCode']];
        }

        return '';
    }


}

global $productController;
$productController = new ProductController();