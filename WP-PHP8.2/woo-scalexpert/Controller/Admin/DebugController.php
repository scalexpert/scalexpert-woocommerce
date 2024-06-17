<?php
	
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	namespace wooScalexpert\Controller\Admin;
	
	class DebugController {
		protected array $sg_scalexpert_options;
		public const    PAGE_NAME = "debug";
		
		
		public function __construct() {
			if ( is_admin() && isset( $_REQUEST['page'] ) || ( $_POST["option_page"] == "sg_scalexpert_debug_group" ) ) {
				
				if ( get_option( 'sg_scalexpert_debug' ) ) {
					$this->sg_scalexpert_options = get_option( 'sg_scalexpert_debug' );
				} else {
					$this->sg_scalexpert_options = array();
					$options                     = array(
						"mode_debug" => "",
					);
					add_option( 'sg_scalexpert_debug', $options );
				}
				add_action( 'admin_init', array( $this, 'sg_scalexpert_debugpage_init' ) );
			}
			
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_debug_page() {
			?>
			
			<div class="wrap">
				<img alt="" src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
				
				<?php AdminController::getAdministrationTopMenu( self::PAGE_NAME ); ?>
				
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'sg_scalexpert_debug_group' );
						do_settings_sections( 'sg-scalexpert-debug' );
						submit_button();
					?>
				</form>
			</div>
			<?php
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_debugpage_init() {
			register_setting(
				'sg_scalexpert_debug_group', // option_group
				'sg_scalexpert_debug', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			add_settings_section(
				'sg_scalexpert_setting_section', // id
				__( "Mode Debug", "woo-scalexpert" ), // title
				array( $this, 'sg_scalexpert_section_info' ), // callback
				'sg-scalexpert-debug' // page
			);
			
			add_settings_field(
				'mode_debug', // id
				__( "Enable/Disable Debug", "woo-scalexpert" ), // title
				array( $this, 'debug_environment_callback' ), // callback
				'sg-scalexpert-debug', // page
				'sg_scalexpert_setting_section' // section
			);
		}
		
		/**
		 * @param $input
		 *
		 * @return array
		 *
		 */
		public function sg_scalexpert_sanitize( $input ) : array {
			$sanitary_values = array();
			
			if ( isset( $input['mode_debug'] ) ) {
				$sanitary_values['mode_debug'] = $input['mode_debug'];
			}
			
			return $sanitary_values;
		}
		
		/**
		 * @return void
		 */
		public function sg_scalexpert_section_info() : void {}
		
		/**
		 * @return void
		 */
		public function debug_environment_callback() : void {
			?>
			<input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_debug" name="sg_scalexpert_debug[mode_debug]" value="1"
			       onchange="changeLabel('sg_scalexpert_debug','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo $this->sg_scalexpert_options['mode_debug'] && $this->sg_scalexpert_options['mode_debug'] === "1" ? 'checked' : ''; ?>
			>
			<label for="field-id"
			       id="sg_scalexpert_debug_label"><?php echo $this->sg_scalexpert_options['mode_debug'] && $this->sg_scalexpert_options['mode_debug'] === "1"
					? __( "Activated", "woo-scalexpert" )
					: __( "Off", "woo-scalexpert" );
				?></label>
			
			</br>
			<em><?= __( "Log files are available in the following folder :", "woo-scalexpert" ) ?></br>
				/wp-content/plugins/woo-scalexpert/logs
			</em>
			<?php
		}
		
	}