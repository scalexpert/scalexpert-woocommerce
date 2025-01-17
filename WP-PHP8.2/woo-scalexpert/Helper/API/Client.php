<?php
	
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	namespace wooScalexpert\Helper\API;
	
	use GuzzleHttp;
	use wooScalexpert\Helper\Log\LoggerHelper;
	
	
	/**
	 *
	 */
	class Client {
		
		protected GuzzleHttp\Client   $guzzleClient;
		protected LoggerHelper        $logger;
		protected SimulationFormatter $formatter;
		
		const scope_financing = 'e-financing:rw';
		const scope_insurance = 'insurance:rw';
		protected array $scalexpertOptions;
		private string  $appBearer;
		private         $_appIdentifier;
		private         $_appKey;
		
		private $_type;
		
		private $_appBearer;
		
		/**
		 *
		 */
		public function __construct() {
			
			require_once( PLUGIN_DIR . '/Helper/API/SimulationFormatter.php' );
			require_once( PLUGIN_DIR . '/Helper/Log/Logger.php' );
			require( PLUGIN_DIR . '/Static/StaticData.php' );
			
			$this->_appBearer        = "";
			$this->scalexpertOptions = $this->getScalexpertOptions();
			$this->_type             = ( isset( $this->scalexpertOptions['environment'] ) ) ? $this->scalexpertOptions['environment'] : "Test";
			$apiKeyField             = ( 'Production' === $this->_type ) ? 'api_key' : "api_key_test";
			$apiSecretField          = ( 'Production' === $this->_type ) ? 'secret' : "secret_test";
			$this->_appIdentifier    = $this->openSslDeCrypt( get_option( 'sg_scalexpert_keys' )[ $apiKeyField ] );
			$this->_appKey           = $this->openSslDeCrypt( get_option( 'sg_scalexpert_keys' )[ $apiSecretField ] );
			$this->guzzleClient      = new GuzzleHttp\Client();
			$this->logger            = new LoggerHelper();
			$this->formatter         = new SimulationFormatter();
			$this->getBearer( self::scope_financing );
			
			add_action( "wp_ajax_sg_checkKey", array( $this, "sg_checkKey" ) );
			add_action( "wp_ajax_nopriv_sg_checkKey", array( $this, "sg_checkKey" ) );
			
            add_action( "wp_ajax_scalexpert_confirmDelivery", array( $this, "scalexpert_confirmDelivery" ) );

			add_action( "wp_ajax_sg_cancelFinancing", array( $this, "sg_cancelFinancing" ) );
            add_action( "wp_ajax_nopriv_sg_cancelFinancing", array( $this, "sg_cancelFinancing" ) );
			
			add_action( "wp_ajax_sg_recreateCart", array( $this, "sg_recreateCart" ) );
			add_action( "wp_ajax_nopriv_sg_recreateCart", array( $this, "sg_recreateCart" ) );
			
		}
		
		
		/**
		 * @param $scope
		 * @
		 *
		 * @return bool
		 */
		public function getBearer( $scope = NULL ) : bool {
			
			if ( get_transient( "_appBearer" ) ) {
				return TRUE;
			}
			
			$response = $this->sendRequest(
				'POST',
				SCALEXPERT_ENDPOINT_AUTH,
				[
					'grant_type' => 'client_credentials',
					'scope'      => $scope ?? '',
				],
				[],
				[ 'Authorization' => 'Basic ' . base64_encode( $this->_appIdentifier . ':' . $this->_appKey ) ],
				[],
				TRUE
			);
			
			if ( ! empty( $response['contentsDecoded']['access_token'] ) ) {
				$this->_appBearer = $response['contentsDecoded']['access_token'];
				Set_transient( "_appBearer", $this->_appBearer, 3600 );
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		
		/**
		 * @param string $method
		 * @param string $endpoint
		 * @param array  $formParams
		 * @param array  $query
		 * @param array  $headers
		 * @param array  $json
		 * @param bool   $isBearerToken
		 *
		 * @return array
		 */
		public function sendRequest(
			string $method = "",
			string $endpoint = "",
			array $formParams = array(),
			array $query = array(),
			array $headers = array(),
			array $json = array(),
			bool $isBearerToken = FALSE
		) : array {
			if ( ! $isBearerToken && empty( get_transient( "_appBearer" ) ) ) {
				$this->getBearer( self::scope_financing );
			}
			
			if ( ! empty( $formParams ) ) {
				$options['form_params'] = $formParams;
			}
			
			if ( ! empty( $query ) ) {
				$options['query'] = $query;
			}
			
			if ( ! empty( $headers ) ) {
				$options['headers'] = $headers;
			} else {
				$options['headers'] = [ 'Authorization' => 'Bearer ' . $this->getAppBearer() ];
			}
			
			if ( ! empty( $json ) ) {
				$options['json'] = $json;
			}
			
			$response = [];
			$uniqueId = uniqid();
			
			try {
				$temporaryOptions = $options;
				unset( $temporaryOptions['headers'] );
				
				$this->logger->logInfo(
					sprintf( '%s Request %s %s (environment=%s)', $uniqueId, $method, $endpoint, $this->_type ),
					$temporaryOptions
				);
				
				$BaseUrl          = $this->getBaseUrl();
				$guzzleResponse   = $this->guzzleClient->request(
					$method,
					$BaseUrl . $endpoint,
					$options
				);
				$responseBody     = $guzzleResponse->getBody();
				$responseContents = $responseBody->getContents();
				
				$response = [
					'code'            => $guzzleResponse->getStatusCode(),
					'content'         => $responseContents,
					'contentsDecoded' => []
				];
				
				$this->logger->logInfo(
					sprintf( '%s Response %s (environment=%s)', $uniqueId, $endpoint, $this->_type ),
					! $isBearerToken ? $response : []
				);
				
				if ( ! empty( $responseContents ) ) {
					$responseContentsDecoded     = json_decode( $responseContents, TRUE );
					$response['contentsDecoded'] = $responseContentsDecoded;
				}
			} catch ( GuzzleHttp\Exception\ClientException $e ) {
				
				$errorCode    = $e->getCode();
				$errorMessage = $e->getResponse()->getBody()->getContents();
				
				$response = array(
					'errorCode'    => $errorCode,
					'errorMessage' => $errorMessage,
				);
				
				$this->logger->logError(
					sprintf( '%s Error client %s (environment=%s)', $uniqueId, $endpoint, $this->_type ),
					[
						'errorCode'    => $errorCode,
						'errorMessage' => $errorMessage,
					]
				);
			} catch ( GuzzleHttp\Exception\ServerException $e ) {
				
				$errorCode    = $e->getCode();
				$errorMessage = $e->getResponse()->getBody()->getContents();
				
				$response = array(
					'errorCode'    => $errorCode,
					'errorMessage' => $errorMessage,
				);
				
				$this->logger->logError(
					sprintf( '%s Error server %s (environment=%s)', $uniqueId, $endpoint, $this->_type ),
					[
						'errorCode'    => $errorCode,
						'errorMessage' => $errorMessage,
					]
				);
			}
			
			return $response;
		}
		
		
		/**
		 * @return string|null
		 */
		public function getAppBearer() : ?string {
			return get_transient( "_appBearer" );
		}
		
		/**
		 * @param string|null $appBearer
		 */
		public function setAppBearer( ?string $appBearer ) : void {
			$this->_appBearer = $appBearer;
		}

        /**
         * @return void
         */
        public function scalexpert_confirmDelivery()
        {
            $creditSubscriptionId = $_POST['creditSubscriptionId'];
            $operator = $_POST['operator'];
            $trackingNumber = $_POST['trackingNumber'];
            $orderId = $_POST['orderId'];
            $order = wc_get_order( $orderId );

            update_post_meta($order->get_id(), 'scalexpert_operator', $operator);
            update_post_meta($order->get_id(), 'scalexpert_tracking_number', $trackingNumber);

            $endpoint = str_replace("{creditSubscriptionId}", $creditSubscriptionId, SCALEXPERT_ENDPOINT_CONFIRM_DELIVERY);

            $response = $this->sendRequest(
                'POST',
                $endpoint,
                [],
                [],
                [],
                [
                    "isDelivered" => true,
                    "trackingNumber" => $trackingNumber,
                    "operator" => $operator
                ]
            );

            if (isset($response['errorMessage'])) {
                update_post_meta($order->get_id(), 'scalexpert_isDelivered', false);
                $return = [
                    'status' => $response['errorCode'],
                    'message' => json_decode($response['errorMessage']),
                ];
                $this->logger->logError("Error confirmDelivery - return response : " . $response['errorMessage']);
            } elseif (isset($response['error'])) {
                update_post_meta($order->get_id(), 'scalexpert_isDelivered', false);
                $return = [
                    'status' => $response['error'],
                    'message' => json_decode($response['message']),
                ];
                $this->logger->logError("Error confirmDelivery - return response : " . $response['message']);
            } else {
                update_post_meta($order->get_id(), 'scalexpert_isDelivered', true);
                $return = [
                    'status' => $response['code'],
                    'message' => json_decode($response['content']),
                ];
                $this->logger->logInfo(print_r($return, true));
            }

            wp_die(json_encode($return));
        }
		
		/**
		 * @param $amount
		 * @param $country
		 *
		 * @return array
		 *
		 */
		public function getFinancialSolutions( $amount = NULL, $country = "", $format = "front" ) : array {
			
			$eFinancingAmounts   = ( $amount != NULL ) ? [ $amount ] : [ "500", "1000" ];
			$eFinancingCountries = [ "FR" ];
			$financialSolutions  = [];
			
			foreach ( $eFinancingAmounts as $eFinancingAmount ) {
				foreach ( $eFinancingCountries as $eFinancingCountry ) {
					$endpoint = SCALEXPERT_ENDPOINT_ELIGIBLE_SOLUTIONS . "?financedAmount=$eFinancingAmount&buyerBillingCountry=$eFinancingCountry";
					
					$response = $this->sendRequest(
						'GET',
						$endpoint,
						[],
						[
							'financedAmount'      => $eFinancingAmount,
							'buyerBillingCountry' => $eFinancingCountry,
						]
					);
					/**
					 * We need the raw data for the simulator purpose
					 */
					if ( $format == "raw" ) {
						return $response;
					}
					
					/**
					 * Formated Data for the SolutionCTAs in product
					 * and cart
					 */
					if ( $format == "front" ) {
						if ( ! empty( $response['contentsDecoded']['solutions'] ) ) {
							foreach ( $response['contentsDecoded']['solutions'] as $solution ) {
								$financialSolutions[ $solution['solutionCode'] ] = $this->formatSolution(
									$solution,
									$eFinancingCountry,
									'financial'
								);
							}
						}
					}
				}
			}
			
			return $financialSolutions;
		}
		
		
		/**
		 * @param $response
		 * @param $catID
		 * @param $solutionCode
		 *
		 * @return array
		 */
		public function getEligibleSimulationsForFront( $response = array(), $catID = "", $solutionCode = "", $isProduct = FALSE ) : array {
			
			$eligibleSimulations = [];
			if ( empty( $response['hasError'] ) ) {
				foreach ( $response['contentsDecoded']['solutionSimulations'] as $solution ) {
					$eligibleSimulations[ $solution['solutionCode'] ] = $solution;
				}
			}
			
			if ( $eligibleSimulations ) {
				foreach ( $eligibleSimulations as $key => $eligibleSimulation ) {
					$activated = get_option( 'sg_scalexpert_activated_' . $eligibleSimulation['solutionCode'] );
					if ( ! isset( $activated['activate'] ) ) {
						unset( $eligibleSimulations[ $key ] );
					}
					if ( $isProduct ) { // we are on a product page
						$design = get_option( 'sg_scalexpert_design_' . $eligibleSimulation['solutionCode'] );
						if ( empty( $design['activate'] ) ) {
							unset( $eligibleSimulations[ $key ] );
						}
					}
					if ( $solutionCode == "" ) { // we are in checkout
						$excluded = get_option( 'sg_scalexpert_design_' . $eligibleSimulation['solutionCode'] );
						$excluded = ( ! empty( $excluded['exclude_cats'] ) ) ? explode( ",", $excluded['exclude_cats'] ) : [];
						if ( in_array( $catID, $excluded ) ) {
							unset( $eligibleSimulations[ $key ] );
						}
					}
					if ( $solutionCode != "" && $eligibleSimulation['solutionCode'] != $solutionCode ) { // we are in checkout
						unset( $eligibleSimulations[ $key ] );
					}
				}
			}
			
			return $eligibleSimulations;
		}
		
		
		/**
		 * @param $solution
		 * @param $buyerBillingCountry
		 * @param $solutionType
		 *
		 * @return array
		 */
		public function formatSolution( $solution, $buyerBillingCountry, $solutionType ) : array {
			return [
				'solutionCode'                => $solution['solutionCode'] ?? '',
				'visualTitle'                 => $solution['communicationKit']['visualTitle'] ?? '',
				'visualDescription'           => $solution['communicationKit']['visualDescription'] ?? '',
				'visualInformationIcon'       => $solution['communicationKit']['visualInformationIcon'] ?? '',
				'visualAdditionalInformation' => $solution['communicationKit']['visualAdditionalInformation'] ?? '',
				'visualLegalText'             => $solution['communicationKit']['visualLegalText'] ?? '',
				'visualTableImage'            => $solution['communicationKit']['visualTableImage'] ?? '',
				'visualLogo'                  => $solution['communicationKit']['visualLogo'] ?? '',
				'visualInformationNoticeURL'  => $solution['communicationKit']['visualInformationNoticeURL'] ?? '',
				'visualProductTermsURL'       => $solution['communicationKit']['visualProductTermsURL'] ?? '',
				'buyerBillingCountry'         => $buyerBillingCountry,
				'countryFlag'                 => sprintf( '/img/flags/%s.jpg', strtolower( $buyerBillingCountry ) ),
				'type'                        => $solutionType,
			];
		}
		
		
		/**
		 * @param $eFinancingAmount
		 * @param $eFinancingCountry
		 * @param $categoryID
		 *
		 * @return array
		 */
		public function getSimulateFinancing4Product( $eFinancingAmount = NULL, $eFinancingCountry = "FR", $categoryID, bool $isCart = FALSE ) {
			$transient         = NULL;
			$normalize         = array();
			$simulateFinancing = array(
				'buyerBillingCountry' => $eFinancingCountry,
				"financedAmount"      => floatval( $eFinancingAmount ),
				"solutionCodes"       => array()
			);
			/**
			 * Caching of API Response
			 */
			$response = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertSimulation_" . $eFinancingAmount . "_" . $eFinancingCountry ) : NULL;
			if ( empty( $response ) ) {
				try {
					$response = $this->sendRequest( 'POST', SCALEXPERT_ENDPOINT_SIMULATION, array(), array(), array(), $simulateFinancing, TRUE );
				} catch ( \Exception $e ) {
					print_r( $e->getMessage() );
				}
				if ( $response['code'] == 200 ) {
					$transient = ( SCALEXPERT_APICACHE ) ? Set_transient( "scalexpertSimulation_" . $eFinancingAmount . "_" . $eFinancingCountry, $response, SCALEXPERT_TRANSIENTS ) : NULL;
				}
			}
			/**
			 * We alert some people if necessary
			 */
			if ( empty( $response['code'] ) && $transient === NULL ) {
				$this->alertAPIstate( $response );
			}
            if ( $isCart ) {
                return $response;
            }
			/** @var
			 * $customizeProduct : We now format the API response if there is some
			 */
			$eligibleSolutions = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertSolutions_" . $eFinancingAmount . "_" . $eFinancingCountry ) : NULL;
			if ( ! $eligibleSolutions ) {
				$eligibleSolutions = $this->getFinancialSolutions( $eFinancingAmount, $eFinancingCountry, "raw" );
				$transient         = ( SCALEXPERT_APICACHE ) ? Set_transient( "scalexpertSolutions_" . $eFinancingAmount . "_" . $eFinancingCountry, $eligibleSolutions, SCALEXPERT_TRANSIENTS ) : NULL;
			}
			$eligibleSimulations = $this->getEligibleSimulationsForFront( $response, $categoryID, "", TRUE );
			$designData          = $this->formatter->buildDesignData( $eligibleSimulations, $eligibleSolutions, FALSE );
			$normalize           = $this->formatter->normalizeSimulations( $response, $designData['designSolutions'], FALSE, TRUE );

			return $normalize;
		}
		
		
		/**
		 * @param $eFinancingAmount
		 * @param $eFinancingCountry
		 * @param $solution
		 *
		 * @return array
		 */
		public function getSimulateFinancing4Checkout( $eFinancingAmount = NULL, $eFinancingCountry = "FR", $solution = "", $isCart = FALSE ) {
			
			$simulateFinancing = array(
				'buyerBillingCountry' => $eFinancingCountry,
				"financedAmount"      => floatval( $eFinancingAmount ),
				"solutionCodes"       => array( $solution )
			);
			/**
			 * Caching of API Response
			 */
			$response = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertCheckOutSimulation_" . $eFinancingAmount . "_" . $solution . "_" . $eFinancingCountry ) : NULL;
			if ( ! $response ) {
				try {
					$response = $this->sendRequest( 'POST', SCALEXPERT_ENDPOINT_SIMULATION, array(), array(), array(), $simulateFinancing, TRUE );
				} catch ( \Exception $e ) {
					print_r( $e->getMessage() );
				}
				if ( $response['code'] == 200 && SCALEXPERT_APICACHE ) {
					Set_transient( "scalexpertCheckOutSimulation_" . $eFinancingAmount . "_" . $solution . "_" . $eFinancingCountry, $response, SCALEXPERT_TRANSIENTS );
				}
			}
			/**
			 * We alert some people if necessary
			 */
			if ( ! empty( $response['errorCode'] ) ) {
				$this->alertAPIstate( $response['errorCode'] );
			}
            if ( $isCart ) {
                return $response;
            }
			/** @var
			 * $customizeProduct : We now format the API response if there is some
			 */
			$eligibleSolutions = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertSolutions_" . $eFinancingAmount . "_" . $eFinancingCountry ) : NULL;
			if ( ! $eligibleSolutions ) {
				$eligibleSolutions = $this->getFinancialSolutions( $eFinancingAmount, $eFinancingCountry, "raw" );
				if ( SCALEXPERT_APICACHE ) {
					Set_transient( "scalexpertSolutions_" . $eFinancingAmount . "_" . $eFinancingCountry, $eligibleSolutions, SCALEXPERT_TRANSIENTS );
				}
			}
			$eligibleSimulations = $this->getEligibleSimulationsForFront( $response, "", $solution );
			$designData          = $this->formatter->buildDesignData( $eligibleSimulations, $eligibleSolutions, TRUE );
			
			return $this->formatter->normalizeSimulations( $response, $designData['designSolutions'], FALSE, TRUE );
			
		}

        /**
         * @param float $eFinancingAmount
         * @param string $eFinancingCountry
         * @param array $solution
         * @return array|null
         */
        public function getSimulateFinancing4Cart(float $eFinancingAmount, string $eFinancingCountry = "FR", array $solution = []): ?array
        {

            $simulateFinancing = array(
                'buyerBillingCountry' => $eFinancingCountry,
                "financedAmount" => $eFinancingAmount,
                "solutionCodes" => $solution
            );
            /**
             * Caching of API Response
             */
            $response = ( SCALEXPERT_APICACHE ) ? get_transient( "scalexpertCartSimulation_" . $eFinancingAmount . "_" . implode("_", $solution) . "_" . $eFinancingCountry ) : NULL;
            if ( !$response ) {
                try {
                    $response = $this->sendRequest( 'POST', SCALEXPERT_ENDPOINT_SIMULATION, array(), array(), array(), $simulateFinancing, TRUE );
                } catch ( \Exception $e ) {
                    print_r( $e->getMessage() );
                }
                if ( $response[ 'code' ] == 200 && SCALEXPERT_APICACHE ) {
                    Set_transient( "scalexpertCartSimulation_" . $eFinancingAmount . "_" . implode("_", $solution) . "_" . $eFinancingCountry, $response, SCALEXPERT_TRANSIENTS );
                }
            }
            /**
             * We alert some people if necessary
             */
            if ( !empty( $response[ 'errorCode' ] ) ) {
                $this->alertAPIstate( $response[ 'errorCode' ] );
            }
            return $response;
        }
		
		
		/**
		 * @param $appIdentifier
		 * @param $appKey
		 * @param $type
		 *
		 * @return void
		 */
		public function setCredentials( $appIdentifier, $appKey, $type ) : void {
			$this->_appIdentifier = $appIdentifier;
			$this->_appKey        = $appKey;
			$this->_type          = $type;
		}
		
		
		/**
		 * @return string
		 */
		private function getBaseUrl() : string {
			if ( 'Production' == $this->_type ) {
				return URLAPIPROD;
			}
			
			return URLAPIUAT;
		}
		
		
		/**
		 * @param $simple_string
		 *
		 * @return false|string
		 * TODO : find the right cipher
		 */
		public function openSslCrypt( $simple_string ) {
			
			$ciphering      = "AES-128-CBC";
			$options        = 0;
			$encryption_iv  = AUTH_SALT;
			$encryption_key = AUTH_KEY;
			$encryption     = @openssl_encrypt( $simple_string, $ciphering, $encryption_key, $options, $encryption_iv );
			
			return $encryption;
		}
		
		
		/**
		 * @param $encryption
		 *
		 * @return false|string
		 * TODO : find the right cipher
		 */
		public function openSslDeCrypt( $encryption ) {
			
			$ciphering      = "AES-128-CBC";
			$options        = 0;
			$decryption_iv  = AUTH_SALT;
			$decryption_key = AUTH_KEY;
			$decryption     = @openssl_decrypt( $encryption, $ciphering, $decryption_key, $options, $decryption_iv );
			
			return $decryption;
		}
		
		/**
		 * @return false|mixed|null
		 *
		 */
		public function getScalexpertOptions() {
			$options = array();
			if ( get_option( 'sg_scalexpert_keys' ) ) {
				return get_option( 'sg_scalexpert_keys' );
			} else {
				$options = array(
					"environment" => "Test",
					"api_key"     => "",
					"secret"      => ""
				);
				add_option( 'sg_scalexpert_keys', $options );
				
				return $options;
			}
		}
		
		
		/**
		 * @return void
		 * @throws JsonException
		 */
		public function sg_checkKey() {
			
			$environment = $_POST['environment'];
			$appKey      = $_POST['apiKey'];
			$appSecret   = $_POST['apiSecret'];
			
			$this->_type = $environment;
			
			$response = $this->sendRequest(
				'POST',
				SCALEXPERT_ENDPOINT_AUTH,
				[
					'grant_type' => 'client_credentials',
				],
				[],
				[ 'Authorization' => 'Basic ' . base64_encode( $appKey . ':' . $appSecret ) ],
				[],
				TRUE
			);

            $res = false;

            if ( !empty( $response[ 'contentsDecoded' ][ 'access_token' ] ) ) {
                $res = true;
            }

            wp_die(json_encode($res));
		}
		
		
		/**
		 * @return void
		 *
		 */
		public function sg_cancelFinancing() {
			
			$amount2Cancel   = $_POST['cancelAmount'];
			$amount2Cancel   = str_replace( ",", ".", $amount2Cancel );
			$scalexpertFinID = $_POST['finID'];
			$orderID         = $_POST['orderID'];
			$order           = wc_get_order( $orderID );
			
			if ( $scalexpertFinID ) {
				
				//https://api.scalexpert.uatc.societegenerale.com/baas/uatc/e-financing/api/v1/subscriptions/{creditSubscriptionId}/_cancel
				$apiClient         = new \wooScalexpert\Helper\API\Client;
				$endpoint          = SCALEXPERT_ENDPOINT_SUBSCRIPTION . $scalexpertFinID . "/_cancel";
				$cancelInformation = array(
					'cancelledAmount'     => floatval( $amount2Cancel ),
					'cancelledItem'       => "order",
					'cancellationReason'  => "NC",
					'cancellationContact' => array(
						'lastName'    => $order->get_billing_last_name(),
						'firstName'   => $order->get_billing_first_name(),
						'email'       => $order->get_billing_email(),
						'phoneNumber' => $order->get_billing_phone(),
					)
				
				);
				
				
				try {
					$result = $apiClient->sendRequest(
						"POST",
						$endpoint,
						array(),
						array(),
						array(),
						$cancelInformation,
						TRUE );
					
					$resultCode           = $result['code'];
					$resultContent        = $result['content'];
					$resultFinancedAmount = $result['contentsDecoded']['financedAmount'];
					$resultStatus         = $this->getFinancialStateName( "CANCELLED" );
					
					if (
						$resultCode == 200
						&& ! isset( $result['errorCode'] )
						&& $result['contentsDecoded']['status'] === "ACCEPTED"
					) {
						
						update_post_meta( $order->get_id(), 'SG_financedAmount', floatval( $resultFinancedAmount ) );
						update_post_meta( $order->get_id(), 'SG_reducedAmount', floatval( $amount2Cancel ) );
						update_post_meta( $order->get_id(), 'scalexpert_status', $resultStatus );
						update_post_meta( $order->get_id(), 'scalexpert_consolidatedSubstatus', __( "CANCELED BY CUSTOMER", 'woo-scalexpert' ) );
						$order->add_order_note( __( 'New financed amount: ', 'woo-scalexpert' ) . floatval( $resultFinancedAmount ) . "€" );
                        $order->update_status( 'cancelled' );
						wp_die( json_encode( __( 'Financed amount reduced by: ', 'woo-scalexpert' ) . floatval( $amount2Cancel ) . "€" ) );
						
					} else {
						
						/**
						 * Error possibility / API Response properly formatted
						 */
						if ( isset( $result['errorCode'] ) && $result['errorCode'] == 400 ) {
							
							$errorMessage = json_decode( $result['errorMessage'] );
							if ( $errorMessage->errorCode == "INVALID_STATUS" ) {
								$message = $this->tradAPIErrorMessage( $errorMessage->errorMessage );
								wp_die( json_encode( $message ) );
							}
							if ( $errorMessage->errorCode == "REQUEST_VALIDATION_ERROR" ) {
								$message = $this->tradAPIErrorMessage( $errorMessage->errorMessage );
								wp_die( json_encode( $message ) );
							}
							
							$errorMessage = explode( "{", $errorMessage->errorMessage );
							$errorMessage = explode( '}', $errorMessage[1] );
							$errorMessage = $errorMessage[0];
							$errorMessage = str_replace( "<EOL><EOL>", "", $errorMessage );
							$errorMessage = json_decode( "{" . $errorMessage . "}" );
							
							if ( $errorMessage->error == "error_forbidden_doublerequest" ) {
								$order->add_order_note( $errorMessage->message );
								update_post_meta( $order->get_id(), 'SG_financedAmount', floatval( $order->get_total() ) );
								update_post_meta( $order->get_id(), 'SG_reducedAmount', floatval( 0 ) );
							}
							$message = $this->tradAPIErrorMessage( $errorMessage->message );
							wp_die( json_encode( $message ) );
						}
						
						/**
						 * Error possibility / No proper formatted API response
						 */
						$errorMessage = explode( "{", $errorMessage->errorMessage );
						$errorMessage = explode( '}', $errorMessage[1] );
						$errorMessage = $errorMessage[0];
						$errorMessage = str_replace( "<EOL><EOL>", "", $errorMessage );
						$errorMessage = json_decode( "{" . $errorMessage . "}" );
						wp_die( json_encode( $errorMessage->message ) );
						
					}
					
				} catch ( Exception $e ) {
					echo $error = 'Exception received : ', $e->getMessage(), "\n";
					$result['message'] = $error;
					$result['result']  = 'failure';
					wp_die( json_encode( $result ) );
				}
				
				
			}
			
			wp_die( $amount2Cancel );
			
		}
		
		/**
		 * @param $message
		 *
		 * @return mixed
		 */
		public function tradAPIErrorMessage( $message ) {
			
			switch ( $message ) {
				case "Final Amount is less than minimum authorized amount":
					$message = __( "Final Amount is less than minimum authorized amount", "woo-scalexpert" );
					break;
				case "The credit subscription is already cancelled.":
					$message = __( "The credit subscription is already cancelled.", "woo-scalexpert" );
					break;
				case "cancelledAmount should be strictly positive":
					$message = __( "cancelledAmount should be strictly positive", "woo-scalexpert" );
					break;
				case "The cancelledAmount is greater than the financedAmount. You can only request cancellation with cancelledAmount equal or lower than the financedAmount.":
					$message = __( "The cancelledAmount is greater than the financedAmount. You can only request cancellation with cancelledAmount equal or lower than the financedAmount.", "woo-scalexpert" );
					break;
				default :
					return $message;
					break;
			}
			
			return $message;
		}
		
		/**
		 * Recreate Cart for new Order
		 *
		 * @param $order_id
		 *
		 * @return void
		 * @throws Exception
		 */
		public function sg_recreateCart() {
			
			$original_order_id = $_POST['orderID'];
			$newCart           = NULL;
			$cart_page_url     = "";
			
			WC()->cart->empty_cart();
			$original_order = wc_get_order( $original_order_id );
			
			foreach ( $original_order->get_items() as $item_key => $item ) {
				
				$item_data    = $item->get_data();
				$product_id   = $item_data['product_id'];
				$variation_id = $item_data['variation_id'];
				$quantity     = $item_data['quantity'];
				
				$newCart = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
				
			}
			$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
			if ( $newCart ) {
				wp_die( $cart_page_url );
			} else {
				wp_die( FALSE );
			}
		}
		
		
		/**
		 * @return bool
		 *
		 */
		public function activationPossible() : bool {
			
			$scalexpertOptions = $this->scalexpertOptions;
			
			$activationPossible = FALSE;
			$api_key            = ( isset( $scalexpertOptions['api_key'] ) ) ? $scalexpertOptions['api_key'] : NULL;
			$secret             = ( isset( $scalexpertOptions['secret'] ) ) ? $scalexpertOptions['secret'] : NULL;
			$api_key_test       = ( isset( $scalexpertOptions['api_key_test'] ) ) ? $scalexpertOptions['api_key_test'] : NULL;
			$secret_test        = ( isset( $scalexpertOptions['secret_test'] ) ) ? $scalexpertOptions['secret_test'] : NULL;
			
			if ( ( $api_key && $secret ) || ( $api_key_test && $secret_test ) ) {
				$activationPossible = TRUE;
			} else {
				$scalexpertOptions['activate']     = "";
				$scalexpertOptions['api_key']      = $this->openSslDeCrypt( $scalexpertOptions['api_key'] );
				$scalexpertOptions['api_key_test'] = $this->openSslDeCrypt( $scalexpertOptions['api_key_test'] );
				update_option( 'sg_scalexpert_keys', $scalexpertOptions );
			}
			
			return $activationPossible;
		}
		
		
		/**
		 * @param $sgFinancialStatus
		 *
		 * @return string|null
		 */
		private function getFinancialStateName( $sgFinancialStatus ) : ?string {
			switch ( $sgFinancialStatus ) {
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
		
		public function alertAPIstate( $response ) {
			/**
			 * Only for monitoring on UAT DS
			 */
			if ( URLAPIUAT == 'https://api.scalexpert.hml.societegenerale.com/baas/uat/' ) {
				print $msg = print_r( $response, 1 );
				mail( "omure@datasolution", "API SG Down", $response['errorMessage'] . $msg );
			}
		}
		
		
	}