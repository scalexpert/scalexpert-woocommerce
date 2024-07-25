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
	
	
	class ProductController {
		
		public static array  $eFinancingAmounts   = [ "500", "1000" ];
		public static array  $eFinancingCountries = [ "FR" ];
		public static string $scope               = 'e-financing';
		private array        $solutionnames;
		private Client       $apiclient;
		
		
		/**
		 *
		 */
		public function __construct() {
			require( PLUGIN_DIR . '/Static/StaticData.php' );
			
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
		public function showActifSolutions( $template = "product-buttons", $position = "under", $categories = array(), $price = NULL ) {
			
			global $product;
			$price     = ( $price != NULL ) ? $price : $product->get_price();
			$productID = ( $template != "payment-buttons" ) ? $product->get_id() : "";
			
			$config = get_option( 'sg_scalexpert_keys' );
			if ( ! isset( $config['activate'] ) ) {
				return FALSE;
			}
			
			if ( $template == 'payment-buttons' ) {
				$solutions = $this->getEligibleSolutions( $price, $productID, 'nocache' );
			} else {
				$solutions = $this->getEligibleSolutions( $price, $productID, '' );
			}
			$groupFinancingSolution = get_option( "sg_scalexpert_group_financing_solution" );
			
			
			echo '<!-- begin /Views/' . $template . '.php -->';
			if ( ! $solutions && $template == 'payment-buttons' ) {
				echo __( 'Cart value or product not eligible for Scalexpert financing !', 'woo-scalexpert' );
			} else {
				foreach ( $solutions as $solution ) {
					$solution = $solution['solutionCode'];
					$actif    = array();
					$actif    = get_option( 'sg_scalexpert_activated_' . $solution );
					if ( isset( $actif['activate'] ) && $actif['activate'] == 1 ) {
						$solutionname     = $this->getTitleBySolution( $solution, 'solutionName' );
						$CommunicationKit = $this->getCommunicationKit( $solution );
						$DesignSolution   = get_option( 'sg_scalexpert_design_' . $solution );
						include( plugin_dir_path( __FILE__ ) . '../../Views/' . $template . '.php' );
					}
				}
			}
			//payment_box payment_method_scalexpert
			// Paiements groupés / non groupés
			if ( $groupFinancingSolution['group_financing_solution'] != 1 ) {
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
			echo '<!-- end ' . plugin_dir_path( __FILE__ ) . '/Views/' . $template . '.php -->';
		}
		
		
		/**
		 * @param $solutionCode
		 *
		 * @return mixed
		 *
		 */
		public function getCommunicationKit( $solutionCode ) {
			$CommunicationKit = get_transient( $solutionCode );
			if ( ! $CommunicationKit ) {
				$this->setCommunicationKitTransients();
				$CommunicationKit = get_transient( $solutionCode );
			}
			
			return $CommunicationKit;
		}
		
		
		/**
		 * @return void
		 *
		 */
		public function setCommunicationKitTransients() {
			$scalexpertActivated = get_option( 'sg_scalexpert_keys' );
			if ( isset( $scalexpertActivated['activate'] ) ) {
				$eFinancingSolutions = $this->getEligibleSolutions();
				foreach ( $eFinancingSolutions as $solutionCode => $solution ) {
					Set_transient( $solutionCode, $solution, SCALEXPERT_TRANSIENTS );
				}
			}
		}
		
		/**
		 * @param $price
		 * @param $productID
		 *
		 * @return array
		 */
		public function getEligibleSolutions( $price = NULL, $productID = NULL ) : array {
			
			if ( $price && $productID ) {
				$solutions = get_transient( "scalexpertProduct_" . $productID );
				if ( ! $solutions ) {
					require_once( PLUGIN_DIR . '/Helper/API/Client.php' );
					$this->apiclient = new Client();
					try {
						$apiCall = $this->apiclient->getFinancialSolutions( $price );
						Set_transient( "scalexpertProduct_" . $productID, $apiCall, SCALEXPERT_TRANSIENTS );
						
						return $solutions = get_transient( "scalexpertProduct_" . $productID );
					} catch ( Exception $e ) {
						echo 'Exception reçue : ', $e->getMessage(), "\n";
					}
					$solutions = array();
				}
				
				return $solutions;
			}
			
			require_once( PLUGIN_DIR . '/Helper/API/Client.php' );
			$this->apiclient = new Client();
			
			return $this->apiclient->getFinancialSolutions( $price );
		}
		
		
		/**
		 * @param $solution
		 * @param $output
		 *
		 * @return string
		 */
		public function getTitleBySolution( $solution, $output = NULL ) : string {
			
			$data = $this->solutionnames;
			
			if ( isset( $data[ $solution ] ) && $output == "solutionName" ) {
				//if ( $output == "solutionName" ) {
				return $data[ $solution ];
			}
			
			if ( isset( $data[ $solution['solutionCode'] ] ) ) {
				return "<img src='" . plugins_url( '/woo-scalexpert/assets/img/' . strtolower( $solution['marketCode'] ) . '.jpg' ) . "'> " . $data[ $solution['solutionCode'] ];
			}
			
			return '';
		}
		
		
	}
	
	global $productController;
	$productController = new ProductController();