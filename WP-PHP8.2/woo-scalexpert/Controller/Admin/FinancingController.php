<?php
	
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	namespace wooScalexpert\Controller\Admin;
	
	//use JsonException;
	use wooScalexpert\Helper\API\Client;
	
	
	class FinancingController {
		
		protected Client $apiClient;
		private array    $eFinancingSolutions;
		private array    $missingSolution;
		public const    PAGE_NAME = "finances";
		
		
		/**
		 * @throws JsonException
		 *
		 */
		public function __construct() {
			
			if ( is_admin() && isset( $_REQUEST['page'] )
                || (
                    array_key_exists('option_page', $_POST)
                    && $_POST['option_page'] == 'sg_scalexpert_finances_group'
                )
            ) {
				require_once( PLUGIN_DIR . '/Static/autoload.php' );
                require( PLUGIN_DIR . '/Static/StaticData.php' );
				$this->apiClient = new Client();
				try {
					$this->eFinancingSolutions = $this->apiClient->getFinancialSolutions();
					$this->missingSolution     = $this->getMissingSolution( $this->eFinancingSolutions, 'financials' );
					add_action( 'admin_init', array( $this, 'sg_scalexpert_financepage_init' ) );
				} catch ( Exception $e ) {
					echo 'Exception reçue : ', $e->getMessage(), "\n";
					$this->eFinancingSolutions = array();
				}
			}
			
		}
		
		/**
		 * @param $existingSolution
		 * @param $type
		 *
		 * @return array
		 *
		 */
		public function getMissingSolution( $existingSolution, $type ) : array {
			
			$solutionGrouped = SCALEXPERTGROUPEDSOLUTIONS;
			if ( ! isset( $solutionGrouped[ $type ] ) ) {
				return [];
			}
			
			$missingSolution = [];
			foreach ( $solutionGrouped[ $type ] as $solutionGroup ) {
				$found = FALSE;
				foreach ( $solutionGroup as $solutionCode ) {
					if ( in_array( $solutionCode, array_keys( $existingSolution ) ) ) {
						$found = TRUE;
					}
				}
				if ( ! $found ) {
					foreach ( $solutionGroup as $element ) {
						$missingSolution[] = $element;
					}
				}
			}
			
			return $missingSolution;
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_finance_page() {
			?>
			
			<div class="wrap">
				<img alt="Scaleexpert logo" src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
				
				<?php AdminController::getAdministrationTopMenu( self::PAGE_NAME ); ?>
				
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'sg_scalexpert_finances_group' );
						do_settings_sections( 'sg-scalexpert-finances' );
						submit_button( __( "Save changes", "woo-scalexpert" ) );
					?>
				</form>
			</div>
			<?php
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_financepage_init() {
			
			add_settings_section(
				'sg_scalexpert_setting_section', // id
				__( "Activate/deactivate financing options", "woo-scalexpert" ), // title
				array( $this, 'sg_scalexpert_section_info' ), // callback
				'sg-scalexpert-finances' // page
			);
			
			register_setting(
				'sg_scalexpert_finances_group', // option_group
				'sg_scalexpert_solutions', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			
			foreach ( $this->eFinancingSolutions as $solutionCode => $solution ) {
				$section_id = 'sg_scalexpert_setting_section_' . $solutionCode;
				
				register_setting(
					'sg_scalexpert_finances_group', // option_group
					'sg_scalexpert_activated_' . $solutionCode, // option_name
					array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
				);
				
				add_settings_field(
					'sg_scalexpert_section_activations_' . $solutionCode, // id
					__( $this->getTitleBySolution( $solution ), "woo-scalexpert" ), // title
					array( $this, 'activate_solution_callback' ), // callback
					'sg-scalexpert-finances', // page
					'sg_scalexpert_setting_section', // section,
					array(
						'solution' => $solution
					)
				);
			}
			
			foreach ( $this->missingSolution as $solutionCode ) {
				add_settings_field(
					'sg_scalexpert_section_missing_' . $solutionCode, // id
					__( $this->getTitleBySolution( $solutionCode, 'solutionName' ), "woo-scalexpert" ), // title
					array( $this, 'missing_solutions_callback' ), // callback
					'sg-scalexpert-finances', // page
					'sg_scalexpert_setting_section', // section,
					$solutionCode
				);
			}
			
			register_setting(
				'sg_scalexpert_finances_group', // option_group
				'sg_scalexpert_group_financing_solution', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			add_settings_section(
				'solutions', // id
				'', // title
				array( $this, 'sg_scalexpert_section_info' ), // callback
				'sg-scalexpert-finances' // page
			);
			
			add_settings_field(
				'solutions', // id
				'', // title
				array( $this, 'solutions_callback' ), // callback
				'sg-scalexpert-finances', // page
				'sg_scalexpert_setting_section' // section,
			);
			
		}
		
		/**
		 * @param $input
		 *
		 * @return array
		 */
		public function sg_scalexpert_sanitize( $input ) : array {
			$sanitary_values = array();
			
			if ( isset( $input['activate'] ) ) {
				$sanitary_values['activate'] = $input['activate'];
			}
			
			if ( isset( $input['solutions'] ) ) {
				$sanitary_values['solutions'] = $input['solutions'];
			}
			
			if ( isset( $input['solutionnames'] ) ) {
				$sanitary_values['solutionnames'] = $input['solutionnames'];
			}
			
			$sanitary_values['group_financing_solution'] = 1;
			
			return $sanitary_values;
		}
		
		/**
		 * @param $solution
		 * @param $output
		 *
		 * @return string
		 */
		public function getTitleBySolution( $solution, $output = NULL ) : string {
			$financements = array();
			require( PLUGIN_DIR . '/Static/StaticData.php' );
			$data = $financements;
			
			if ( $output === "solutionName" ) {
				return "<img src='" . plugins_url( '/woo-scalexpert/assets/img/flags/' ) . $this->getSolutionFlag( $solution ) . ".jpg'> " . $data[ $solution ];
			}
			
			if ( isset( $data[ $solution['solutionCode'] ] ) ) {
				return "<img src='" . plugins_url( '/woo-scalexpert/assets' ) . $solution['countryFlag'] . "'> " . $data[ $solution['solutionCode'] ];
			}
			
			return '';
		}
		
		/**
		 * @param $solutionCode
		 *
		 * @return string|null
		 *
		 */
		public function getSolutionFlag( $solutionCode ) {
			$data = [
				'SCFRSP-3XTS' => 'fr',
				'SCFRSP-3XPS' => 'fr',
				'SCFRSP-4XTS' => 'fr',
				'SCFRSP-4XPS' => 'fr',
				//				'SCDELT-DXTS' => 'de',
				//				'SCDELT-DXCO' => 'de',
				'SCFRLT-TXPS' => 'fr',
				'SCFRLT-TXNO' => 'fr',
				'CIFRWE-DXCO' => 'fr',
			];
			
			if ( isset( $data[ $solutionCode ] ) ) {
				return $data[ $solutionCode ];
			}
			
			return NULL;
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_section_info() : void {}
		
		/**
		 * @param array $solution
		 *
		 * @return void
		 */
		public function activate_solution_callback( array $solution ) : void {
			$solution    = $solution['solution'];
			$sectionName = 'sg_scalexpert_activated_' . $solution['solutionCode'];
			?>
			<input id="<?= $sectionName ?>" type="checkbox" class="wppd-ui-toggle" name="<?php echo $sectionName; ?>[activate]" value="1"
			       onchange="changeLabel('<?= $sectionName ?>','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo get_option( $sectionName ) && get_option( $sectionName )['activate'] === "1" ? 'checked' : ''; ?>
			>
			<label for="field-id"
			       id="<?= $sectionName ?>_label"><?php echo get_option( $sectionName ) && get_option( $sectionName )['activate'] === "1"
					? __( "Activated", "woo-scalexpert" )
					: __( "Off", "woo-scalexpert" );
				?></label>
			<?php
		}
		
		
		/**
		 * @return void
		 *
		 */
		public function missing_solutions_callback( string $solutionCode ) {
			?>
			<input id="<?= $solutionCode ?>" type="checkbox" class="wppd-ui-toggle" value="0" disabled>
			<label for="field-id"><?php echo __( "Off", "woo-scalexpert" ) ?></label>
			<p class="help-block">
				<?= __( "This option is not available in your contract.", "woo-scalexpert" ); ?><br>
				<a href="https://scalexpert.societegenerale.com/app/fr/page/e-financement" target="_blank"><?= __( "Subscribe to this offer.", "woo-scalexpert" ); ?></a>
			</p>
			<?php
		}
		
		/**
		 * @return void
		 *
		 */
		public function solutions_callback() {
			$solutions       = [];
			$solutionsTitles = [];
			foreach ( $this->eFinancingSolutions as $solutionCode => $solution ) {
				$solutions[]     = $solutionCode;
				$solutionnames[] = $solution['visualTitle'];
			}
			if (
				count( $solutions )
			) {
				$solutions     = implode( ',', $solutions );
				$solutionnames = implode( ',', $solutionnames );
			}
			
			?>
			<input value="<?= $solutions ?>" type="hidden" class="wppd-ui-toggle" id="scalexpert_solutions" name="sg_scalexpert_solutions[solutions]">
			<input value="<?= $solutionnames ?>" type="hidden" class="wppd-ui-toggle" id="scalexpert_solutionnames" name="sg_scalexpert_solutions[solutionnames]">
			<?php
		}
		
		public function group_financing_solution_callback() {
			?>
			<input id="sg_scalexpert_group_financing_solution" type="checkbox" class="wppd-ui-toggle" name="sg_scalexpert_group_financing_solution[group_financing_solution]" value="1"
			       onchange="changeLabel('sg_scalexpert_group_financing_solution','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo get_option( 'sg_scalexpert_group_financing_solution' ) && get_option( 'sg_scalexpert_group_financing_solution' )['group_financing_solution'] === "1" ? 'checked' : ''; ?>
			>
			<label for="field-id"
			       id="sg_scalexpert_group_financing_solution_label"><?php echo get_option( 'sg_scalexpert_group_financing_solution' )['group_financing_solution'] && get_option( 'sg_scalexpert_group_financing_solution' )['group_financing_solution'] === "1"
					? __( "Activated", "woo-scalexpert" )
					: __( "Off", "woo-scalexpert" );
				?></label>
			<?php
		}
	}
