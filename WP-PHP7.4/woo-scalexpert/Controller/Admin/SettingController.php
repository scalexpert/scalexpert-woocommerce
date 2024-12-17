<?php
	
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	namespace wooScalexpert\Controller\Admin;
	
	/**
	 *
	 *
	 */
	class SettingController {
		
		protected $sg_scalexpert_configurable_settings;
		
		public function __construct() {
            if ( is_admin() && isset( $_REQUEST['page'] )
                || (
                    array_key_exists('option_page', $_POST)
                    && $_POST['option_page'] == 'sg_scalexpert_configurable_settings_group'
                )
            ) {
				$this->sg_scalexpert_configurable_settings = $this->getScalexpertConfigurableSettings();
				add_action( 'admin_init', array( $this, 'sg_scalexpert_configurable_settings_page_init' ) );
			}
		}
		
		/**
		 * @return false|mixed|null
		 *
		 */
		public function getScalexpertConfigurableSettings() {
			$options = array();
			if ( get_option( 'sg_scalexpert_configurable_settings' ) ) {
				return get_option( 'sg_scalexpert_configurable_settings' );
			} else {
				$options = array(
					"title"       => SCALEXPERTWOOTITLE,
					"description" => SCALEXPERTWOODESCRIBE,
					"attrmarque"  => "",
					"attrmodel"   => "",
				);
				add_option( 'sg_scalexpert_configurable_settings', $options );
				
				return $options;
			}
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_settings_page() {
			?>
			
			<div class="wrap">
				<img alt="" src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
				<p>
					<strong><?= __( "Display settings", "woo-scalexpert" ) ?></strong>
					</br>
					<?= __( "Configurations included in WooCommerce", "woo-scalexpert" ) ?>
				</p>
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'sg_scalexpert_configurable_settings_group' );
						do_settings_sections( 'sg-scalexpert-configurable-settings' );
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
		public function sg_scalexpert_configurable_settings_page_init() {
			register_setting(
				'sg_scalexpert_configurable_settings_group', // option_group
				'sg_scalexpert_configurable_settings', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			add_settings_section(
				'sg_scalexpert_setting_section', // id
				'',//__( "Paramètres", "woo-scalexpert" ), // title
				array( $this, "sg_scalexpert_configurable_settings_info" ), // callback
				'sg-scalexpert-configurable-settings' // page
			);
			
			add_settings_field(
				'titre', // id
				__( "Scalexpert title Basket", "woo-scalexpert" ), // title
				array( $this, 'title_callback' ), // callback
				'sg-scalexpert-configurable-settings', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'description', // id
				__( "Description Scalexpert", "woo-scalexpert" ), // title
				array( $this, 'description_callback' ), // callback
				'sg-scalexpert-configurable-settings', // page
				'sg_scalexpert_setting_section' // section
			);
			
			
			add_settings_field(
				'attrmarque', // id
				__( "Name Attribute Brand", "woo-scalexpert" ), // title
				array( $this, 'attrmarque_callback' ), // callback
				'sg-scalexpert-configurable-settings', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'attrmodel', // id
				__( "Name Attribute Model", "woo-scalexpert" ), // title
				array( $this, 'attrmodel_callback' ), // callback
				'sg-scalexpert-configurable-settings', // page
				'sg_scalexpert_setting_section' // section
			);
			
			
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_configurable_settings_info() {}
		
		
		/**
		 * @param $input
		 *
		 * @return array
		 *
		 */
		public function sg_scalexpert_sanitize( $input ) : array {
			$sanitary_values = array();
			
			if ( isset( $input['title'] ) ) {
				$sanitary_values['title'] = $input['title'];
			}
			
			if ( isset( $input['description'] ) ) {
				$sanitary_values['description'] = $input['description'];
			}
			
			if ( isset( $input['attrmarque'] ) ) {
				$sanitary_values['attrmarque'] = $input['attrmarque'];
			}
			
			if ( isset( $input['attrmodel'] ) ) {
				$sanitary_values['attrmodel'] = $input['attrmodel'];
			}
			
			
			return $sanitary_values;
		}
		
		/**
		 * @return void
		 */
		public function title_callback() : void {
			
			if ( isset( $this->sg_scalexpert_configurable_settings['title'] ) && $this->sg_scalexpert_configurable_settings['title'] != "" ) {
				$title = $this->sg_scalexpert_configurable_settings['title'];
			} else {
				$title = SCALEXPERTWOOTITLE;
			}
			
			printf(
				'<input class="regular-text" type="text" name="sg_scalexpert_configurable_settings[title]" id="title" value="%s">',
				$title
			);
			echo "<br>" . __( "This title is used to display the payment method in the checkout.", "woo-scalexpert" );
		}
		
		/**
		 * @return void
		 */
		public function description_callback() : void {
			
			if ( isset( $this->sg_scalexpert_configurable_settings['description'] ) && $this->sg_scalexpert_configurable_settings['description'] != "" ) {
				$description = $this->sg_scalexpert_configurable_settings['description'];
			} else {
				$description = SCALEXPERTWOODESCRIBE;
			}
			
			printf(
				'<textarea class="regular-text" type="text" name="sg_scalexpert_configurable_settings[description]" id="description">%s</textarea>',
				$description
			);
			echo "<br>" . __( "This description is displayed in the Woocommerce configuration", "woo-scalexpert" );
		}
		
		/**
		 * @return void
		 */
		public function attrmarque_callback() : void {
			printf(
				'<input class="regular-text" type="text" name="sg_scalexpert_configurable_settings[attrmarque]" id="attrmarque" value="%s">',
				isset( $this->sg_scalexpert_configurable_settings['attrmarque'] ) ? esc_attr( $this->sg_scalexpert_configurable_settings['attrmarque'] ) : ''
			);
			echo "<br>" . __( "Brand attribute to be configured and filled in for products to be financed by Scalexpert", "woo-scalexpert" );
		}
		
		/**
		 * @return void
		 */
		public function attrmodel_callback() : void {
			printf(
				'<input class="regular-text" type="text" name="sg_scalexpert_configurable_settings[attrmodel]" id="attrmodel" value="%s">',
				isset( $this->sg_scalexpert_configurable_settings['attrmodel'] ) ? esc_attr( $this->sg_scalexpert_configurable_settings['attrmodel'] ) : ''
			);
			echo "<br>" . __( "Model attribute to be configured and filled in for products to be financed by Scalexpert", "woo-scalexpert" );
		}
		
		
	}
