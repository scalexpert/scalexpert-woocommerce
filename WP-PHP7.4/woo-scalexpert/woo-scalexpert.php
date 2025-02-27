<?php

/**
 * Plugin Name: Woocommerce Scalexpert
 * Plugin URI: https://docs.scalexpert.societegenerale.com/apidocs/3mLlrPx3sPtekcQvEEUg/developers-docs/readme
 * Description: Solutions de financement - SG Scalexpert
 * Text Domain: woo-scalexpert
 * Domain Path: /languages
 * Version: 1.7.2-7.4
 * Author: SOCIETE GENERALE
 * Author URI: https://scalexpert.societegenerale.com
 */

/* If this file is called directly, abort. */

if ( !defined( 'WPINC' ) ) {
    die;
}
if ( !defined( 'PLUGIN_DIR' ) ) {
    define( 'PLUGIN_DIR', __DIR__ );
}


// Needed for install process
require_once( PLUGIN_DIR . '/vendor/autoload.php' );
require_once( PLUGIN_DIR . '/Static/autoload.php' );

use wooScalexpert\Controller\Admin\ConfigController;
use wooScalexpert\Controller\Admin\AdminController;
use wooScalexpert\Helper\API\Client;
use wooScalexpert\Helper\Log\LoggerHelper;

class ScalexpertPlugin {

    protected ConfigController $configController;
    protected AdminController  $adminController;
    protected LoggerHelper     $logger;
    protected Client           $apiClient;

    /**
     *
     */
    public function __construct(){
        register_activation_hook( __FILE__, array( 'ScalexpertPlugin', 'install' ) );
        $cronSettings = ( get_option( 'sg_cron_configuration_settings' ) )
            ? get_option( 'sg_cron_configuration_settings' )
            : array(
                "activate_cron" => "",
                "interval_time" => "",
            );

        if ( is_admin() ) {
            $this->adminController = new AdminController();
            add_action( 'admin_enqueue_scripts', array( $this, 'load_scalexpert_admin_scripts' ) );

            try {
                require_once( PLUGIN_DIR . '/Static/autoload.php' );
                $this->apiClient = new Client();
            } catch ( Exception $e ) {
                echo 'Exception reçue : ', $e->getMessage(), "\n";
                exit();
            }
            $this->checkAPIcache();
        }

        add_filter( 'cron_schedules', array( $this, 'sgcron_add_intervals' ) );

        if ( $cronSettings[ 'activate_cron' ] === "1" ) {
            function set_schedule_on_init(){
                $cronSettings = get_option( 'sg_cron_configuration_settings' );
                $interval     = $cronSettings[ 'interval_time' ] ? : 'hourly';

                if ( !wp_next_scheduled( 'cron_update_status_job' ) ) {
                    wp_schedule_event( time(), $interval, 'cron_update_status_job' );
                }
            }

            add_action( 'init', 'set_schedule_on_init' );
            function cron_update_status_function(){
                $wcScalexpertGateway = new WC_Scalexpert_Gateway();
                $wcScalexpertGateway->update_status_orders();
            }

            add_action( 'cron_update_status_job', 'cron_update_status_function' );
        } elseif ( wp_next_scheduled( 'cron_update_status_job' ) ) {
            wp_clear_scheduled_hook( 'cron_update_status_job' );
        }

        function setCronUpdateUrl(){
            if ( isset( $_GET[ 'updateOrder' ] ) ) {
                $wcScalexpertGateway = new WC_Scalexpert_Gateway();
                $wcScalexpertGateway->update_status_orders();
            }
        }

        add_action( 'init', 'setCronUpdateUrl' );
        add_action( 'add_meta_boxes', array( $this, 'custom_order_meta_box' ) );

        /* Loads the plugin's translated strings. */
        load_plugin_textdomain( 'woo-scalexpert', FALSE, plugin_basename( PLUGIN_DIR ) . '/languages' );
    }

    /**
     * @return void
     *
     */
    public function checkAPIcache(){
        $apiDebug = get_option( 'sg_scalexpert_debug' );
    }

    /**
     * Add custom meta box.
     *
     * @return void
     */
    public function custom_order_meta_box(){
        add_meta_box(
            'custom-order-meta-box',
            __( 'Financing SG', 'woo-scalexpert' ),
            array( $this, 'custom_order_meta_box_callback' ),
            'shop_order',
            'side',
            'core'
        );

            add_meta_box(
            'custom-delivery-information',
            __( 'Delivery informations', 'woo-scalexpert' ),
            array( $this, 'custom_delivery_information_callback' ),
            'shop_order',
            'side',
            'core'
        );
    }

    public function custom_delivery_information_callback( $post ) {
        // Get the saved value
        $order = wc_get_order( $post->ID );
        $creditSubscriptionId = $order->get_meta('scalexpert_finID');
        $operator = $order->get_meta('scalexpert_operator');
        $isDelivered = $order->get_meta('scalexpert_isDelivered');
        $scalexpert_consolidatedStatus = $order->get_meta('scalexpert_consolidatedStatus');
        $scalexpert_consolidatedSubstatus = $order->get_meta('scalexpert_consolidatedSubstatus');
        if (!in_array($order->get_meta( 'scalexpert_solution' ), [
            "SCFRLT-TXPS",
            "SCFRLT-TXNO",
            "SCFRLT-TXTS",
            "SCDELT-DXTS",
            "SCDELT-DXCO",
        ])) {
            echo '<p>' . __( 'Confirm delivery not available', 'woo-scalexpert' ) . '</p>';
        } else {
            echo '<p><label for="trackingNumber">' . __( 'Tracking number', 'woo-scalexpert' ) . ' : </label><br>';
            if ($isDelivered) {
                echo '<input type="text" id="scalexpert_tracking_number" name="scalexpert_tracking_number" value="' . $order->get_meta('scalexpert_tracking_number') . '" disabled /></p>';
            } else {
                echo '<input type="text" id="scalexpert_tracking_number" name="scalexpert_tracking_number" value="' . $order->get_meta('scalexpert_tracking_number') . '" /></p>';
            }

            echo '<p><label for="operator">' . __( 'Operator', 'woo-scalexpert' ) . ' :</label><br>';
            if ($isDelivered) {
                echo '<select id="scalexpert_operator_selected" name="scalexpert_operator_selected" disabled>
                          <option value="">' . __( '--Please choose an operator--', 'woo-scalexpert' ) . '</option>
                          <option value="UPS" ' . ($operator === "UPS" ? "selected" : "") . '>' . __( 'UPS', 'woo-scalexpert' ) . '</option>
                          <option value="DHL" ' . ($operator === "DHL" ? "selected" : "") . '>' . __( 'DHL', 'woo-scalexpert' ) . '</option>
                          <option value="CHRONOPOST" ' . ($operator === "CHRONOPOST" ? "selected" : "") . '>' . __( 'CHRONOPOST', 'woo-scalexpert' ) . '</option>
                          <option value="LA_POSTE" ' . ($operator === "LA_POSTE" ? "selected" : "") . '>' . __( 'LA_POSTE', 'woo-scalexpert' ) . '</option>
                          <option value="DPD" ' . ($operator === "DPD" ? "selected" : "") . '>' . __( 'DPD', 'woo-scalexpert' ) . '</option>
                          <option value="RELAIS_COLIS" ' . ($operator === "RELAIS_COLIS" ? "selected" : "") . '>' . __( 'RELAIS_COLIS', 'woo-scalexpert' ) . '</option>
                          <option value="MONDIAL_RELAY" ' . ($operator === "MONDIAL_RELAY" ? "selected" : "") . '>' . __( 'MONDIAL_RELAY', 'woo-scalexpert' ) . '</option>
                          <option value="FEDEX" ' . ($operator === "FEDEX" ? "selected" : "") . '>' . __( 'FEDEX', 'woo-scalexpert' ) . '</option>
                          <option value="GLS" ' . ($operator === "GLS" ? "selected" : "") . '>' . __( 'GLS', 'woo-scalexpert' ) . '</option>
                          <option value="UNKNOW" ' . ($operator === "UNKNOW" ? "selected" : "") . '>' . __( 'UNKNOWN', 'woo-scalexpert' ) . '</option>
                        </select>
                ';
            } else {
                echo '<select id="scalexpert_operator_selected" name="scalexpert_operator_selected">
                          <option value="">' . __( '--Please choose an operator--', 'woo-scalexpert' ) . '</option>
                          <option value="UPS" ' . ($operator === "UPS" ? "selected" : "") . '>' . __( 'UPS', 'woo-scalexpert' ) . '</option>
                          <option value="DHL" ' . ($operator === "DHL" ? "selected" : "") . '>' . __( 'DHL', 'woo-scalexpert' ) . '</option>
                          <option value="CHRONOPOST" ' . ($operator === "CHRONOPOST" ? "selected" : "") . '>' . __( 'CHRONOPOST', 'woo-scalexpert' ) . '</option>
                          <option value="LA_POSTE" ' . ($operator === "LA_POSTE" ? "selected" : "") . '>' . __( 'LA_POSTE', 'woo-scalexpert' ) . '</option>
                          <option value="DPD" ' . ($operator === "DPD" ? "selected" : "") . '>' . __( 'DPD', 'woo-scalexpert' ) . '</option>
                          <option value="RELAIS_COLIS" ' . ($operator === "RELAIS_COLIS" ? "selected" : "") . '>' . __( 'RELAIS_COLIS', 'woo-scalexpert' ) . '</option>
                          <option value="MONDIAL_RELAY" ' . ($operator === "MONDIAL_RELAY" ? "selected" : "") . '>' . __( 'MONDIAL_RELAY', 'woo-scalexpert' ) . '</option>
                          <option value="FEDEX" ' . ($operator === "FEDEX" ? "selected" : "") . '>' . __( 'FEDEX', 'woo-scalexpert' ) . '</option>
                          <option value="GLS" ' . ($operator === "GLS" ? "selected" : "") . '>' . __( 'GLS', 'woo-scalexpert' ) . '</option>
                          <option value="UNKNOW" ' . ($operator === "UNKNOW" ? "selected" : "") . '>' . __( 'UNKNOWN', 'woo-scalexpert' ) . '</option>
                        </select>
                ';
            }
            
            echo '<input type="hidden" id="orderId" name="orderId" value="' . $post->ID . '" />';
            echo '<input type="text" id="scalexpert_creditSubscriptionId" name="scalexpert_creditSubscriptionId" value="' . $creditSubscriptionId . '" hidden/></p>';

            if (
                !$isDelivered
                && $scalexpert_consolidatedStatus === "ACCEPTED"
                && $scalexpert_consolidatedSubstatus === "WAITING FOR THE DELIVERY CONFIRMATION"
            ) {
                echo '<input class="button cancel_order button-primary" type="button" id="scalexpert_deliveryConfirmButton" value="' . __( 'Confirm delivery', 'woo-scalexpert' ) . '" /></p>';
            }
        }
    }

    /**
     * Callback function for custom meta box.
     *
     * @param object $post Post object.
     *
     * @return void
     */
    public function custom_order_meta_box_callback( $post ){

        setlocale( LC_TIME, "fr_FR" );
        $order = wc_get_order( $post->ID );

        if ( $order->get_meta( 'scalexpert_finID' ) && $order->get_meta( 'scalexpert_finID' ) != "ABORTED" ) {
            // Get the saved value  canceledAmount
            $reducedAmount      = esc_attr( get_post_meta( $post->ID, 'SG_reducedAmount', TRUE ) );
            $financedAmount     = esc_attr( get_post_meta( $post->ID, 'SG_financedAmount', TRUE ) );
            $labelAmount2Cancel = __( 'Amount to be cancelled:', 'woo-scalexpert' );
            // Retrocompatibilité
            $financedState     = ( get_post_meta( $post->ID, 'scalexpert_status_bool', TRUE ) != "" ) ? get_post_meta( $post->ID, 'scalexpert_status_bool', TRUE ) : 1;
            $placeHolderAmount = __( 'e.g. 1234.56', 'woo-scalexpert' );
            $status = $this->getFinancialStateName($order->get_meta('scalexpert_consolidatedStatus'));

            echo "<strong>" . __( 'Orderdate:', 'woo-scalexpert' ) . "</strong>" . strftime( '%d-%m-%G', strtotime( $order->get_date_created() ) ) . "<br>";
            echo "<strong>" . __( 'FinID:', 'woo-scalexpert' ) . "</strong>" . $order->get_meta( 'scalexpert_finID' ) . "<br>";
            echo '<input type="hidden" id="scalexpert_finID" name="scalexpert_finID" value="' . $order->get_meta( 'scalexpert_finID' ) . '" />';
            echo "<strong>" . __( 'Solution:', 'woo-scalexpert' ) . "</strong>" . $order->get_meta( 'scalexpert_solution' ) . "<br>";
            echo '<input type="hidden" id="scalexpert_solution" name="scalexpert_solution" value="' . $order->get_meta( 'scalexpert_solution' ) . '" />';
            echo "<strong>" . __( 'Status:', 'woo-scalexpert' ) . "</strong>" . $status . "<br>";
            echo "<strong>" . __( 'Substatus:', 'woo-scalexpert' ) . "</strong>" . __($order->get_meta('scalexpert_consolidatedSubstatus'), 'woo-scalexpert') . "<br>";
            if ( $financedAmount || $reducedAmount == $order->get_total() ) {
                echo "<strong>" . __( 'Financed Amount:', 'woo-scalexpert' ) . "</strong>" . $financedAmount . "€<br>";
                echo "<strong>" . __( 'Cancelled amount:', 'woo-scalexpert' ) . "</strong>" . $reducedAmount . "€<br>";
            } else {
                echo "<strong>" . __( 'Financed Amount:', 'woo-scalexpert' ) . "</strong>" . $order->get_total() . "€<br>";

                if ( !$reducedAmount && $financedState !== '0' ) {

                    // Output the input field
                    echo '<p><label for="SGcanceledAmount">' . $labelAmount2Cancel . '</label> ';
                    echo '<input type="text" id="SGcanceledAmount" name="SGcanceledAmount" value="" placeholder="' . $placeHolderAmount . '"  /><br><br>';
                    echo '<input type="hidden" id="orderID" name="orderID" value="' . $post->ID . '" />';

                    echo '<input class="button cancel_order button-primary" type="button" id="cancelSGButton" name="cancelSGButton" value="' . __( 'Cancel financing', 'woo-scalexpert' ) . '" /></p>';
                }
            }
        } else {
            echo __( 'No SG financing for this order', 'woo-scalexpert' );
        }
    }


    /**
     * @param $schedules
     *
     * @return mixed
     */
    public function sgcron_add_intervals( $schedules ){

        $schedules[ 'scalexpert_updateorder_none' ]           = array(
            'interval' => '',
            'display' => __( 'Make a selection', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_every_minute' ]   = array(
            'interval' => 60,
            'display' => __( 'Every Minute', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_every_5_minute' ] = array(
            'interval' => 300,
            'display' => __( 'Every 5 Minutes', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_halfhourly' ]     = array(
            'interval' => 1800,
            'display' => __( 'Every half an Hour', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_hourly' ]         = array(
            'interval' => 3600,
            'display' => __( 'Every Hour', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_twicedaily' ]     = array(
            'interval' => 43200,
            'display' => __( 'Twice a Day', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_daily' ]          = array(
            'interval' => 86400,
            'display' => __( 'Once a Day', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_weekly' ]         = array(
            'interval' => 604800,
            'display' => __( 'Once a Week', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_fifteendays' ]    = array(
            'interval' => 1296000,
            'display' => __( 'Every two weeks', 'woo-scalexpert' ),
        );
        $schedules[ 'scalexpert_updateorder_monthly' ]        = array(
            'interval' => 2635200,
            'display' => __( 'Monthly', 'woo-scalexpert' ),
        );

        return $schedules;
    }


    /**
     * @param $hook
     *
     * @return void
     */
    public function load_scalexpert_admin_scripts( $hook ){
        // $hook is string value given add_menu_page function.
        if ( ( $hook == 'sg-scalexpert_page_sg-scalexpert-keys' )
            || ( $hook == 'sg-scalexpert_page_sg-scalexpert-debug' )
            || ( $hook == 'sg-scalexpert_page_sg-scalexpert-finances' )
            || ( $hook == 'sg-scalexpert_page_sg-scalexpert-customisation' )
            || ( $hook == 'sg-scalexpert_page_sg-scalexpert-cron-settings' )
        ) {
            wp_enqueue_style( 'scalexpert_admin_css', plugins_url( '/assets/admin-style.css', __FILE__ ) );
            wp_enqueue_script( 'jquery-ui-core', FALSE, array( 'jquery' ) );
            wp_enqueue_script( 'jquery-ui-dialog', FALSE, array( 'jquery' ) );
        }

        wp_enqueue_style( 'scalexpert_admin_scss', plugins_url( '/assets/css/admin.css', __FILE__ ) );
        wp_enqueue_script( 'scalexpert_admin', plugins_url( '/assets/admin-script.js', __FILE__ ) );

    }


    public static function install(){
        $options = array(
            "environment" => "Test",
            "api_key" => "",
            "secret" => ""
        );
        add_option( 'sg_scalexpert_keys', $options );

        $options = array(
            "activate" => "",
        );
        add_option( 'sg_scalexpert_debug', $options );

        $options = array(
            "activate_cron" => "",
        );
        add_option( 'sg_cron_configuration_settings', $options );
    }

    public function orderConfirmation_shortcode_fn( $attributes ){ }

    /**
     * @param $sgFinancialStatus
     *
     * @return string|null
     */
    private function getFinancialStateName($sgFinancialStatus) : ?string{
        switch ($sgFinancialStatus) {
            case 'INITIALIZED':
                $statusName = __( 'Financing request in progress', 'woo-scalexpert' );
                break;
            case 'PRE_ACCEPTED':
                $statusName = __( 'Financing request pre-accepted', 'woo-scalexpert' );
                break;
            case 'ACCEPTED':
                $statusName = __( 'Financing request accepted', 'woo-scalexpert' );
                break;
            case 'REJECTED':
                $statusName = __( 'Financing request rejected', 'woo-scalexpert' );
                break;
            case 'CANCELLED':
                $statusName = __( 'Financing request cancelled', 'woo-scalexpert' );
                break;
            case 'ABORTED':
                $statusName = __( 'Financing request aborted', 'woo-scalexpert' );
                break;
            default:
                $statusName = __( 'A technical error occurred during process, please retry.', 'woo-scalexpert' );
                break;
        }

        return $statusName;
    }
}

$ScalexpertPlugin = new ScalexpertPlugin();


/**
 * Scalexpert
 * on Front pages
 */
class SGScalexpert_WC {

    protected \wooScalexpert\Controller\Front\ProductController $productController;

    /**
     *
     *
     */
    public function __construct(){

        require_once( plugin_dir_path( __FILE__ ) . 'Controller/Front/ProductController.php' );
        $this->productController = new wooScalexpert\Controller\Front\ProductController();
        $this->add_scalexpert_front_filters();
        //$this->load_scalexpert_front_scripts();
        add_action( 'wp_enqueue_scripts', array( $this, 'load_scalexpert_front_scripts' ) );

    }

    /**
     *
     */
    public function load_scalexpert_front_scripts(){

        wp_enqueue_style( 'scalexpert_front_css', plugins_url( '/assets/css/scalexpert.css', __FILE__ ) );
        wp_enqueue_script( 'scalexpert_front_js', plugins_url( '/assets/js/scalexpert.js', __FILE__ ) );
        wp_localize_script( 'scalexpert_front_js', 'sg_recreateCart_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }


    /**
     * @return void
     *
     */
    public function add_scalexpert_front_filters(){

        add_filter( 'woocommerce_template_loader_files', function ( $templates, $template_name ){
            // Capture/cache the $template_name which is a file name like single-product.php
            wp_cache_set( 'scalexpert_wc_main_template', $template_name ); // cache the template name

            return $templates;
        },          10, 2 );

        add_filter( 'template_include', function ( $template ){
            if ( $template_name = wp_cache_get( 'scalexpert_wc_main_template' ) ) {
                wp_cache_delete( 'scalexpert_wc_main_template' ); // delete the cache
                if ( $file = $this->scalexpert_load_wc_template_file( $template_name ) ) {
                    return $file;
                }

            }

            return $template;
        },          11 );

        add_filter( 'wc_get_template_part', function ( $template, $slug, $name ){
            $file = $this->scalexpert_load_wc_template_file( "{$slug}-{$name}.php" );

            return $file ? $file : $template;
        },          10, 3 );

        add_filter( 'wc_get_template', function ( $template, $template_name ){
            $file = $this->scalexpert_load_wc_template_file( $template_name );

            return $file ? $file : $template;
        },          10, 2 );

    }


    /**
     * @param $template_name
     *
     * @return string|void
     *
     */
    public function scalexpert_load_wc_template_file( $template_name ){

        // First Check plugin folder - e.g. wp-content/plugins/woo-scalexpert/woocommerce.
        $file = plugin_dir_path( __FILE__ ) . '/woocommerce/' . $template_name;
        if ( @file_exists( $file ) ) {
            return $file;
        }

        // Then Check theme folder - e.g. wp-content/themes/blabla-theme/woocommerce.
        $file = get_stylesheet_directory() . '/woocommerce/' . $template_name;
        if ( @file_exists( $file ) ) {
            return $file;
        }

    }

}

if ( !is_admin() && !is_login() ) {
    global $SGScalexpert_WC;
    $SGScalexpert_WC = new SGScalexpert_WC();
}


/**
 *
 *
 */
$sg_scalexpert_options = get_option( 'sg_scalexpert_keys' );
if ( isset( $sg_scalexpert_options[ 'activate' ] ) ) {
    add_filter( 'woocommerce_payment_gateways', 'scalexpert_add_gateway_class' );
    add_action( 'plugins_loaded', 'scalexpert_init_gateway_class' );
}


/**
 * @param $gateways
 *
 * @return mixed
 *
 */
function scalexpert_add_gateway_class( $gateways ){

    $sgScalexpertKeys                 = get_option( 'sg_scalexpert_keys' );
    $sgScalexpertConfigurableSettings = get_option( 'sg_scalexpert_configurable_settings' );
    if ( $sgScalexpertKeys[ 'activate' ] == 1 ) {
        $apiClient                     = new wooScalexpert\Helper\API\Client();
        $woocommerceScalexpertSettings = array(
            "enabled" => "yes",
            "testmode" => ( $sgScalexpertKeys[ 'environment' ] == "Test" ) ? "yes" : "no",
            "test_id" => $apiClient->openSslDeCrypt( esc_attr( $sgScalexpertKeys[ 'api_key_test' ] ) ),
            "test_key" => $apiClient->openSslDeCrypt( esc_attr( $sgScalexpertKeys[ 'secret_test' ] ) ),
            "production_id" => $apiClient->openSslDeCrypt( esc_attr( $sgScalexpertKeys[ 'api_key' ] ) ),
            "production_key" => $apiClient->openSslDeCrypt( esc_attr( $sgScalexpertKeys[ 'secret' ] ) ),
            "title" => esc_attr( $sgScalexpertConfigurableSettings[ 'title' ] ),
            "description" => esc_attr( $sgScalexpertConfigurableSettings[ 'description' ] ),
        );
        update_option( "woocommerce_scalexpert_settings", $woocommerceScalexpertSettings );
    }

    $gateways[] = 'WC_Scalexpert_Gateway'; // your class name is here

    return $gateways;
}

function scalexpert_init_gateway_class(){

    class WC_Scalexpert_Gateway extends WC_Payment_Gateway {
        protected LoggerHelper  $logger;
        protected string        $testmode;
        protected string        $private_key;
        protected string        $publishable_key;

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct(){

            require_once( PLUGIN_DIR . '/Helper/Log/Logger.php' );
            $this->logger = new LoggerHelper();

            $this->id                 = 'scalexpert';
            $this->icon               = '';
            $this->has_fields         = TRUE;
            $this->method_title       = 'Scalexpert';
            $this->method_description = 'Paiement en plusieurs fois avec la Société Générale';
            $this->supports           = array(
                'products'
            );

            $this->title           = $this->get_option( 'title' );
            $this->description     = $this->get_option( 'description' );
            $this->enabled         = $this->get_option( 'enabled' );
            $this->testmode        = 'yes' === $this->get_option( 'testmode' );
            $this->private_key     = $this->testmode ? $this->get_option( 'test_key' ) : $this->get_option( 'production_key' );
            $this->publishable_key = $this->testmode ? $this->get_option( 'test_id' ) : $this->get_option( 'production_id' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
            add_action( 'woocommerce_after_order_notes', array( $this, 'scalexpert_checkout_field' ) );

            add_filter( 'woocommerce_available_payment_gateways', array( $this, 'conditional_payment_gateways' ), 10, 1 );

            if (
                array_key_exists('phoneNumberError', $_GET)
                && $_GET['phoneNumberError']
            ) {
                wc_add_notice(__( 'Your phone number is not correct !', 'woo-scalexpert' ), "error");
            }
        }

        /**
         * @param $available_gateways
         *
         * @return mixed
         */
        public function conditional_payment_gateways( $available_gateways ){
            if (
                !get_option('sg_scalexpert_solutions')
                || !array_key_exists('solutions', get_option('sg_scalexpert_solutions'))
            ) {
                return '';
            }
            $solutions = explode(',', get_option('sg_scalexpert_solutions')['solutions']);
            $disable = true;
            foreach ($solutions as $solution) {
                $sgScalexpertActivated = get_option('sg_scalexpert_activated_' . $solution);
                if (
                    $sgScalexpertActivated
                    && array_key_exists('activate', $sgScalexpertActivated)
                    && $sgScalexpertActivated['activate']
                ) {
                    $disable = false;
                }
            }
            if ( $disable ) {
                unset( $available_gateways[ 'scalexpert' ] );
                return $available_gateways;
            }

            global $woocommerce;
            require_once( plugin_dir_path( __FILE__ ) . '/Static/StaticData.php' );
            if ( $woocommerce->cart && ($woocommerce->cart->get_total(null) < SCALEXPERT_LOWERLIMIT || $woocommerce->cart->get_total(null) > SCALEXPERT_UPPERLIMIT )) {
                unset( $available_gateways[ 'scalexpert' ] );
            }

            return $available_gateways;
        }


        /**
         * @return void
         */
        public function scalexpert_checkout_field(){

            woocommerce_form_field( 'scalexpert_finID',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_solution',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_consolidatedStatus',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_consolidatedSubstatus',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_isDelivered',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_operator',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

            woocommerce_form_field( 'scalexpert_tracking_number',
                array(
                    'type' => 'hidden',
                    'required' => 'false',
                    'label' => "",
                    'placeholder' => ""
                )
            );

        }


        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields(){

            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Activer/Désactiver',
                    'label' => 'Activer Scalexpert',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'Ceci contrôle le titre que l\'utilisateur voit lors du paiement.',
                    'default' => 'Payez votre achat en plusieurs fois',
                    'desc_tip' => TRUE,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Cela permet de contrôler la description que l\'utilisateur voit lors de la validation de sa commande.',
                    'default' => 'Payez votre achat en plusieurs fois',
                ),
                'testmode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test API keys.',
                    'default' => 'yes',
                    'desc_tip' => TRUE,
                ),
            );

        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields( $order_id = NULL ){

            global $productController;
            global $woocommerce;

            $total = 0;
            if ( isset( $_REQUEST[ 'key' ] ) ) {
                $order_id = wc_get_order_id_by_order_key( $_REQUEST[ 'key' ] );
                $order    = wc_get_order( $order_id );
                $total    = $order->get_total();
            }
            $order_id = WC()->session->get( 'order_awaiting_payment' );

            $totalOrder             = ( $woocommerce->cart->get_cart_contents_total() != 0 ) ? $woocommerce->cart->get_total(null) : $total;

            // Paiements groupés / non groupés
            do_action( 'woocommerce_credit_card_form_start', $this->id );

            echo '<ul class="list-group list-group-flush">';
            if ( SCALEXPERT_SHOWSOLUTIONS ) {
                $productController->showActifSolutions( 'payment-buttons', "", "", $totalOrder );
            }
            if ( SCALEXPERT_SHOWSIMULATION ) {
                $productController->showActifSolutions( 'payment-simulation-buttons', "", "", $totalOrder );
            }
            echo '</ul>';
            do_action( 'woocommerce_credit_card_form_end', $this->id );
        }

        /**
         * Custom CSS and JS, in most cases require_onced only when you decided to go with a custom credit card form
         */
        public function payment_scripts(){ }


        /**
         * Fields validation, more in Step 5
         */
        public function validate_fields(){ }


        /**
         * @return void
         */
        public function update_status_orders() : void{
            $message = __( 'Starting orders update', 'woo-scalexpert' );
            $orders  = wc_get_orders(
                [
                    'meta_key' => 'scalexpert_finID',
                    'status' => [
                        'wc-on-hold',
                        'wc-pending',
                        'wc-processing'
                    ],
                ]
            );

            $this->logger->logInfo(
                $message,
                [
                    "orders" => implode( ",", array_column( $orders, "id" ) )
                ]
            );

            $updatedOrders = [];
            foreach ( $orders as $order ) {
                if ( $order = $this->get_and_update_status_order( $order ) ) {
                    $updatedOrders[] = $order->get_id();
                }
            }

            $message = __( 'No orders to update', 'woo-scalexpert' );
            if ( !empty( $updatedOrders ) ) {
                $message = __( 'Orders updated', 'woo-scalexpert' );
                $message .= ' : ' . implode( $updatedOrders );
            }
            $this->logger->logInfo( $message );
        }

        /**
         * @param null $order
         *
         * @return mixed|null
         */
        private function get_and_update_status_order( $order = NULL ){
            if ( !$order ) {
                return NULL;
            }

            $orderData   = $order->get_data();
            $orderStatus = $order->get_status();

            if (
                $orderData[ 'payment_method' ] !== "scalexpert"
                || !( $scalexpertFinID = $order->get_meta( 'scalexpert_finID' ) )
                || in_array( $orderStatus, [ 'failed', 'completed' ] )
            ) {
                return NULL;
            }

            $orderSubStatus = $order->get_meta( 'scalexpert_consolidatedSubstatus' );

            $apiClient = new \wooScalexpert\Helper\API\Client;
            $endpoint  = SCALEXPERT_ENDPOINT_SUBSCRIPTION . $scalexpertFinID;
            $result    = $apiClient->sendRequest(
                "GET",
                $endpoint,
                array(),
                array(),
                array(),
                array(),
                TRUE );

            if (
                is_array( $result )
                && $sgFinancialStatus = $result[ 'contentsDecoded' ][ 'consolidatedStatus' ]
            ) {
                $newOrderStatus = $this->getWcStatusByScalexperStatus( $sgFinancialStatus );
                $newTextStatus = $this->getFinancialStateName( $sgFinancialStatus );

                $order->update_status($newOrderStatus);

                if (
                    ( $sgFinancialSubStatus = $result[ 'contentsDecoded' ][ 'consolidatedSubstatus' ] )
                    && $orderSubStatus !== $sgFinancialSubStatus
                ) {
                    update_post_meta($order->get_id(), 'scalexpert_consolidatedSubstatus', $result[ 'contentsDecoded' ][ 'consolidatedSubstatus' ]);
                }

                update_post_meta($order->get_id(), 'scalexpert_consolidatedStatus', $result[ 'contentsDecoded' ][ 'consolidatedStatus' ]);

                if ( $orderStatus !== $newOrderStatus ) {
                    $order->add_order_note( $newTextStatus );

                    if ( $sgFinancialStatus == "ACCEPTED" ) {
                        update_post_meta( $order->get_id(), 'scalexpert_status_bool', 1 );
                    } else {
                        update_post_meta( $order->get_id(), 'scalexpert_status_bool', 0 );
                    }

                    return $order;
                }
            }

            return NULL;
        }

        /**
         * @param $sgFinancialStatus
         *
         * @return string|null
         */
        private function getFinancialStateName($sgFinancialStatus) : ?string{
            switch ($sgFinancialStatus) {
                case 'INITIALIZED':
                    $statusName = __( 'Financing request in progress', 'woo-scalexpert' );
                    break;
                case 'PRE_ACCEPTED':
                    $statusName = __( 'Financing request pre-accepted', 'woo-scalexpert' );
                    break;
                case 'ACCEPTED':
                    $statusName = __( 'Financing request accepted', 'woo-scalexpert' );
                    break;
                case 'REJECTED':
                    $statusName = __( 'Financing request rejected', 'woo-scalexpert' );
                    break;
                case 'CANCELLED':
                    $statusName = __( 'Financing request cancelled', 'woo-scalexpert' );
                    break;
                case 'ABORTED':
                    $statusName = __( 'Financing request aborted', 'woo-scalexpert' );
                    break;
                default:
                    $statusName = __( 'A technical error occurred during process, please retry.', 'woo-scalexpert' );
                    break;
            }

            return $statusName;
        }

        /**
         * @param $scalexpertStatus
         *
         * @return string|null
         */
        private function getWcStatusByScalexperStatus( $scalexpertStatus ) : ?string{
            switch ( $scalexpertStatus ) {
                case 'INITIALIZED':
                case 'PRE_ACCEPTED':
                    $status = 'on-hold';
                    break;
                case 'ACCEPTED':
                    $status = 'processing';
                    break;
                case 'REJECTED':
                case 'ABORTED':
                    $status = 'failed';
                    break;
                case 'CANCELLED':
                    $status = 'cancelled';
                    break;
                default:
                    $status = __( 'A technical error occurred during process, please retry.', 'woo-scalexpert' );
                    break;
            }

            return $status;
        }

        /**
         * @param $order_id
         *
         * @return void
         */
        public function update_scalexpert( $order_id ){

            $response = array();

            if ( !$order_id ) {
                return;
            }

            /**
             * https://api.scalexpert.societegenerale.com/baas/prod/e-financing/api/v1/subscriptions/{creditSubscriptionId}
             * Getting an instance of the order object
             */
            $order       = wc_get_order( $order_id );
            $orderData   = $order->get_data();
            $orderStatus = $order->get_status();

            if ( $orderData[ 'payment_method' ] != "scalexpert" ) {
                return;
            }

            try {
                $scalexpertFinID = $order->get_meta( 'scalexpert_finID' );
                $scalexpertFinID = ( $scalexpertFinID != "" ) ? $scalexpertFinID : get_post_meta( $order_id, "scalexpert_finID", TRUE );
                $apiClient       = new \wooScalexpert\Helper\API\Client;
                $endpoint        = SCALEXPERT_ENDPOINT_SUBSCRIPTION . $scalexpertFinID;
                $result          = $apiClient->sendRequest(
                    "GET",
                    $endpoint,
                    array(),
                    array(),
                    array(),
                    array(),
                    TRUE );

                $sgFinancialStatus    = $result[ 'contentsDecoded' ][ 'consolidatedStatus' ];
                $newOrderStatus       = $this->getWcStatusByScalexperStatus( $sgFinancialStatus );
                $newTextStatus        = $this->getFinancialStateName( $sgFinancialStatus );
                $order->update_status( $newOrderStatus );

                if ( $sgFinancialStatus !== $order->get_meta('scalexpert_consolidatedStatus') ) {
                    $order->add_order_note( $newTextStatus );
                    update_post_meta( $order_id, 'scalexpert_consolidatedStatus', $sgFinancialStatus );

                    if ( $sgFinancialSubStatus = $result[ 'contentsDecoded' ][ 'consolidatedSubstatus' ] ) {
                        update_post_meta( $order_id, 'scalexpert_consolidatedSubstatus',  $sgFinancialSubStatus );
                    }

                    if ( $sgFinancialStatus == "ACCEPTED" ) {
                        update_post_meta( $order_id, 'scalexpert_status_bool', 1 );
                    } else {
                        update_post_meta( $order_id, 'scalexpert_status_bool', 0 );
                    }
                }

                $response[ 'TextStatus' ]      = $newTextStatus;
                $response[ 'OrderStatus' ]     = $newOrderStatus;
                $response[ 'FinancialStatus' ] = $sgFinancialStatus;
                $response[ 'API' ]             = "TRUE";

            } catch ( Exception $e ) {

                $response[ 'TextStatus' ]      = $e->getMessage();
                $response[ 'OrderStatus' ]     = "";
                $response[ 'FinancialStatus' ] = $sgFinancialStatus;
                $response[ 'API' ]             = "FALSE";
            }

            return $response;

        }


        /**
         * We're processing the payments here, everything about it is in Step 5
         *
         * todo : ShippingRegion / ProductDescriptions
         *
         */
        public function process_payment( $order_id ){

            global $woocommerce;

            $apiClient    = new \wooScalexpert\Helper\API\Client;
            $commande     = wc_get_order( $order_id );
            $commandeData = $commande->get_data();
            $basketItems  = $this->getBasketItems( $woocommerce->cart->get_cart(), $order_id );
            $endpoint     = SCALEXPERT_ENDPOINT_SUBSCRIPTION;
            $redirectURL  = $this->get_return_url( $commande );
            $finID        = NULL;
            $Phone        = $this->formatPhoneNumber( $commandeData[ 'billing' ][ 'phone' ], $commandeData[ 'shipping' ][ 'country' ] ? : $commandeData[ 'billing' ][ 'country' ] );

            $genderDefault                = "MR";
            $billingPhone                 = $Phone[ 'number' ];
            $shippingCountry              = ( $commandeData[ 'shipping' ][ 'country' ] != "" ) ? $commandeData[ 'shipping' ][ 'country' ] : $commandeData[ 'billing' ][ 'country' ];
            $shippingPhone                = ( $commandeData[ 'shipping' ][ 'phone' ] != "" ) ? $this->formatPhoneNumber( $commandeData[ 'shipping' ][ 'phone' ], $shippingCountry ) : $billingPhone;
            $shippingStreetName           = ( $commandeData[ 'shipping' ][ 'address_1' ] != "" ) ? $commandeData[ 'shipping' ][ 'address_1' ] : $commandeData[ 'billing' ][ 'address_1' ];
            $shippingStreetNameComplement = ( $commandeData[ 'shipping' ][ 'address_2' ] != "" ) ? $commandeData[ 'shipping' ][ 'address_2' ] : $commandeData[ 'billing' ][ 'address_2' ];
            $shippingZipCode              = ( $commandeData[ 'shipping' ][ 'postcode' ] != "" ) ? $commandeData[ 'shipping' ][ 'postcode' ] : $commandeData[ 'billing' ][ 'postcode' ];
            $shippingCityName             = ( $commandeData[ 'shipping' ][ 'city' ] != "" ) ? $commandeData[ 'shipping' ][ 'city' ] : $commandeData[ 'billing' ][ 'city' ];
            $shippingRegionName           = ( $commandeData[ 'shipping' ][ 'state' ] != "" ) ? $commandeData[ 'shipping' ][ 'state' ] : $commandeData[ 'billing' ][ 'state' ];
            $shippingCountryCode          = ( $commandeData[ 'shipping' ][ 'country' ] != "" ) ? $commandeData[ 'shipping' ][ 'country' ] : $commandeData[ 'billing' ][ 'country' ];

            $checkOut = array(
                'solutionCode' => $_POST[ 'solutionCode' ],
                'merchantBasketId' => $commandeData[ 'cart_hash' ],
                'merchantGlobalOrderId' => "" . $order_id, // leave "". added API expects strings not integers
                'merchantBuyerId' => "" . $commandeData[ 'customer_id' ], // leave "". added API expects strings not integers
                'financedAmount' => floatval( $commandeData[ 'total' ] ),
                'merchantUrls' => array(
                    'confirmation' => $redirectURL,
                ),
                'buyers' => array(
                    array(
                        'deliveryMethod' => 'NC',
                        'vip' => FALSE,
                        'contact' =>
                            array(
                                'lastName' => $commandeData[ 'billing' ][ 'last_name' ],
                                'firstName' => $commandeData[ 'billing' ][ 'first_name' ],
                                'commonTitle' => $genderDefault,
                                'email' => $commandeData[ 'billing' ][ 'email' ],
                                'mobilePhoneNumber' => $billingPhone,
                                'professionalTitle' => 'NC',
                            ),
                        'contactAddress' =>
                            array(
                                'locationType' => 'MAIN_ADDRESS',
                                //'streetNumber'         => 0,
                                'streetNumberSuffix' => 'NC',
                                'streetName' => $commandeData[ 'billing' ][ 'address_1' ],
                                'streetNameComplement' => $commandeData[ 'billing' ][ 'address_2' ],
                                'zipCode' => $commandeData[ 'billing' ][ 'postcode' ],
                                'cityName' => $commandeData[ 'billing' ][ 'city' ],
                                'regionName' => "France",
                                'countryCode' => $commandeData[ 'billing' ][ 'country' ],
                            ),
                        'billingContact' =>
                            array(
                                'lastName' => $commandeData[ 'billing' ][ 'last_name' ],
                                'firstName' => $commandeData[ 'billing' ][ 'first_name' ],
                                'commonTitle' => $genderDefault,
                                'email' => $commandeData[ 'billing' ][ 'email' ],
                                'mobilePhoneNumber' => $billingPhone,
                                'professionalTitle' => 'NC',
                            ),
                        'billingAddress' =>
                            array(
                                'locationType' => 'BILLING_ADDRESS',
                                //'streetNumber'         => 0,
                                'streetNumberSuffix' => 'NC',
                                'streetName' => $commandeData[ 'billing' ][ 'address_1' ],
                                'streetNameComplement' => $commandeData[ 'billing' ][ 'address_2' ],
                                'zipCode' => $commandeData[ 'billing' ][ 'postcode' ],
                                'cityName' => $commandeData[ 'billing' ][ 'city' ],
                                'regionName' => "France",
                                'countryCode' => $commandeData[ 'billing' ][ 'country' ],
                            ),
                        'deliveryContact' =>
                            array(
                                'lastName' => ( $commandeData[ 'shipping' ][ 'last_name' ] != "" ) ? $commandeData[ 'shipping' ][ 'last_name' ] : $commandeData[ 'billing' ][ 'last_name' ],
                                'firstName' => ( $commandeData[ 'shipping' ][ 'first_name' ] != "" ) ? $commandeData[ 'shipping' ][ 'first_name' ] : $commandeData[ 'billing' ][ 'first_name' ],
                                'commonTitle' => $genderDefault,
                                'email' => ( $commandeData[ 'shipping' ][ 'email' ] != "" ) ? $commandeData[ 'shipping' ][ 'email' ] : $commandeData[ 'billing' ][ 'email' ],
                                'mobilePhoneNumber' => $shippingPhone,
                                'professionalTitle' => 'NC',
                            ),
                        'deliveryAddress' =>
                            array(
                                'locationType' => 'DELIVERY_ADDRESS',
                                //'streetNumber'         => 0,
                                'streetNumberSuffix' => 'NC',
                                'streetName' => "'" . $shippingStreetName . "'",
                                'streetNameComplement' => "'" . $shippingStreetNameComplement . "'",
                                'zipCode' => $shippingZipCode,
                                'cityName' => "'" . $shippingCityName . "'",
                                'regionName' => "France",
                                'countryCode' => $shippingCountryCode,
                            ),
                    )
                ),
                'basketDetails' => array(
                    'basketItems' => $basketItems
                ),
            );

            /* Phone number is critical */
            if ( !$Phone[ 'error' ] ) {
                try {
                    $result = $apiClient->sendRequest(
                        "POST",
                        $endpoint,
                        array(),
                        array(),
                        array(),
                        $checkOut,
                        TRUE );
                } catch ( Exception $e ) {
                    echo $error = 'Exception reçue : ', $e->getMessage(), "\n";
                    $result[ 'message' ] = $error;
                    $result[ 'result' ]  = 'failure';
                    wp_die( json_encode( $result ) );
                }

                $resultCode           = $result[ 'code' ];
                $finID                = $result[ 'contentsDecoded' ][ 'id' ];
                $redirectURL          = $result[ 'contentsDecoded' ][ 'redirect' ][ 'value' ];
                $result[ 'redirect' ] = $redirectURL;
            } else {
                $message = __( 'Your phone number is not correct !', 'woo-scalexpert' );
                $this->logger->logError( $message, $Phone );
                $result[ 'message' ] = $message;
                $result[ 'result' ]  = 'success';

                $newUrl               = esc_url( wc_get_checkout_url() . "?phoneNumberError=true" );
                $result[ 'redirect' ] = $newUrl;

                wp_die( json_encode( $result ) );
            }

            if ( $redirectURL && $finID ) {
                // Mark as on-hold (Waiting for API Feedback => update_scalexpert)
                $order = new WC_Order( $order_id );
                $order->add_order_note( __( "Financing request initiated", "woo-scalexpert" ) );
                $order->update_meta_data('redirectURL', $redirectURL);
                $order->save();
                update_post_meta( $order_id, 'scalexpert_finID', $finID );
                update_post_meta( $order_id, 'scalexpert_solution', $_POST[ 'solutionCode' ] );
                update_post_meta( $order_id, 'scalexpert_consolidatedStatus', "INITIALIZED" );
                update_post_meta( $order_id, 'scalexpert_consolidatedSubstatus', "SUBSCRIPTION IN PROGRESS" );
                $woocommerce->cart->empty_cart();
                $result[ 'result' ] = 'success';
                wp_die( json_encode( $result ) );

            } elseif ( $result[ 'errorCode' ] != "" ) {

                if ( defined( WP_DEBUG_SGAPI ) && WP_DEBUG_SGAPI == TRUE ) {
                    $this->scalexpert_debug( $result );
                }

                $order = new WC_Order( $order_id );
                $order->update_status( $this->getWcStatusByScalexperStatus( 'ABORTED' ), $this->getFinancialStateName( 'ABORTED' ) );
                $woocommerce->cart->empty_cart();

                /* If needed we may here store the API Errors */
                update_post_meta( $order_id, 'scalexpert_finID', 'ABORTED' );
                update_post_meta( $order_id, 'scalexpert_solution', $_POST[ 'solutionCode' ] );
                add_post_meta( $order_id, 'scalexpert_status_bool', 0 );

                $public_view_order_url = esc_url( $order->get_view_order_url() );
                /* Return success is necessary to manage failure of API submissions */
                $result[ 'result' ]   = 'success';
                $result[ 'redirect' ] = $public_view_order_url;
                /*  */

                wp_die( json_encode( $result ) );

            }

        }

        /**
         * @param $basket
         * @param $orderID
         *
         * @return array
         */
        public function getBasketItems( $basket = array(), $orderID ){

            $basketItems     = array();
            $attributesNames = get_option( 'sg_scalexpert_configurable_settings' );
            $attributMarque  = ( sanitize_title( $attributesNames[ 'attrmarque' ] ) ) ? sanitize_title( $attributesNames[ 'attrmarque' ] ) : "NC";
            $attributModel   = ( sanitize_title( $attributesNames[ 'attrmodel' ] ) ) ? sanitize_title( $attributesNames[ 'attrmodel' ] ) : "NC";

            foreach ( $basket as $item ) {
                $product    = wc_get_product( $item[ 'product_id' ] );
                $attributes = $product->get_attributes();
                $model      = ( $attributModel != 'NC' && isset( $attributes[ $attributModel ] ) ) ? $attributes[ $attributModel ]->get_data()[ 'value' ] : 'NC';
                $brandName  = ( $attributMarque != 'NC' && isset( $attributes[ $attributMarque ] ) ) ? $attributes[ $attributMarque ]->get_data()[ 'value' ] : 'NC';

                $basketItem    = array(
                    "id" => "'" . $item[ 'product_id' ] . "'",
                    "quantity" => $item[ 'quantity' ],
                    "model" => $model,
                    "label" => $product->get_data()[ 'name' ],
                    "price" => round($item[ 'line_total' ] + $item[ 'line_subtotal_tax' ], 2),
                    "currencyCode" => "EUR",
                    "orderId" => "'" . $orderID . "'",
                    "brandName" => $brandName,
                    "description" => "NC",
                    "specifications" => "NC",
                    "category" => "NC",
                    "sku" => "NC",
                    "isFinanced" => TRUE
                );
                $basketItems[] = $basketItem;
            }

            return $basketItems;
        }


        /**
         * @param $phoneNumber
         * @param $country
         * On ne controle pas au bout comme demandé #145132
         *
         * @return array
         */
        private function formatPhoneNumber( $phoneNumber, $country ) : array
        {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

            try {
                $phoneNumber = $phoneUtil->parse( $phoneNumber, $country );
            } catch ( \libphonenumber\NumberParseException $e ) {
                $this->logger->logError( $e->getMessage(), [ 'phoneNumber' => $phoneNumber ] );
                return [
                    'error' => TRUE,
                    'number' => $phoneNumber,
                ];
            }

            if ( !$phoneUtil->isValidNumber( $phoneNumber ) ) {
                return [
                    'error' => TRUE,
                    'number' => $phoneNumber,
                ];
            }

            return [
                'error' => FALSE,
                'number' => str_replace( " ", "", $phoneUtil->format( $phoneNumber, \libphonenumber\PhoneNumberFormat::INTERNATIONAL ) ),
            ];
        }

        /**
         * Not needed yet
         */
        public function webhook(){ }


        public function scalexpert_debug( $var ){
            /**
             * TODO : More consistent Debug Output
             */
            print "<pre>\n";
            print_r( $var );
            print "</pre>\n";
            die();
        }

    }

}

function showSimulationBeforeAddToCartButton() {
    $activate = get_option('sg_scalexpert_keys')['activate'];
    $blockPosition = get_option( "sg_scalexpert_design" );
    if ($activate && !empty( $blockPosition ) && $blockPosition[ 'blocposition' ] === 'over') {
        global $product;
        global $productController;

        echo '<!--  SG Paiement Simulation -->';
        $productController->showSimulation4Product( wc_get_price_including_tax($product), get_locale() );
        echo "<br>";
        echo '<!--  SG Paiement Simulation -->';
    }
}
add_action('woocommerce_before_add_to_cart_button', 'showSimulationBeforeAddToCartButton', 999);

function showSimulationAfterAddToCartButton() {
    $activate = get_option('sg_scalexpert_keys')['activate'];
    $blockPosition = get_option( "sg_scalexpert_design" );
    if ($activate && !empty( $blockPosition ) && $blockPosition[ 'blocposition' ] === 'under') {
        global $product;
        global $productController;

        echo '<!--  SG Paiement Simulation -->';
        $productController->showSimulation4Product( wc_get_price_including_tax($product), get_locale() );
        echo '<!--  SG Paiement Simulation -->';
    }
}
add_action('woocommerce_after_add_to_cart_button', 'showSimulationAfterAddToCartButton', 999);

function showSimulationBeforeCartTable() {
    $activate = get_option('sg_scalexpert_keys')['activate'];
    $blockPosition = get_option( "sg_scalexpert_design" );
    if ($activate && !empty( $blockPosition ) && $blockPosition[ 'blocposition_cart' ] === 'over') {
        global $woocommerce;
        global $productController;

        echo '<!--  SG Paiement Simulation -->';
        echo '<div class="sep-Simulations-Cart">';
        $productController->showSimulation4Cart($woocommerce->cart->total);
        echo '</div>';
        echo '<!--  SG Paiement Simulation -->';
    }
}
add_action('woocommerce_before_cart_table', 'showSimulationBeforeCartTable', 999);

function showSimulationAfterCartTable() {
    $activate = get_option('sg_scalexpert_keys')['activate'];
    $blockPosition = get_option( "sg_scalexpert_design" );
    if ($activate && !empty( $blockPosition ) && $blockPosition[ 'blocposition_cart' ] === 'under') {
        global $woocommerce;
        global $productController;

        echo '<!--  SG Paiement Simulation -->';
        echo '<div class="sep-Simulations-Cart">';
        $productController->showSimulation4Cart($woocommerce->cart->total);
        echo '</div>';
        echo '<!--  SG Paiement Simulation -->';
    }
}
add_action('woocommerce_after_cart_table', 'showSimulationAfterCartTable', 999);

function ajaxShowSimulation4Product()
{
    global $productController;

    include_once('Controller/Front/ProductController.php');

    if (is_null($productController)) {
        $productController = new wooScalexpert\Controller\Front\ProductController();
    }

    if (isset($_POST['price']) && isset($_POST['productId'])) {
        $price = sanitize_text_field($_POST['price']);
        $productId = intval($_POST['productId']);
        $productController->showSimulation4Product($price, get_locale(), $productId);
    }

    wp_die();
}

add_action( "wp_ajax_sg_solutionView", "ajaxShowSimulation4Product" );
add_action( "wp_ajax_nopriv_sg_solutionView", "ajaxShowSimulation4Product" );

add_action( 'template_redirect', 'force_user_login_after_redirection', 10 );
function force_user_login_after_redirection() {
    // Vérifiez si nous sommes sur la page "order-received" et que l'utilisateur n'est pas connecté
    if ( is_order_received_page() && !is_user_logged_in() ) {
        $orderId = wc_get_order_id_by_order_key($_REQUEST['key']);
        $order = wc_get_order($orderId);

        if (
            ($user_id = $order->get_user()->ID)
            && gettype($user_id) === 'integer'
            && !empty($order->get_meta('redirectURL'))
            && str_starts_with($order->get_meta('redirectURL'), $_SERVER['HTTP_REFERER'])
        ) {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );

            // Ajoutez un paramètre pour éviter les redirections infinies
            if ( !isset( $_GET['logged-in'] ) ) {
                // Redirection vers la même page en ajoutant un paramètre pour éviter une boucle infinie
                wp_redirect( add_query_arg( 'logged-in', 'true', $_SERVER['REQUEST_URI'] ) );
                exit;
            }
        }
    }
}