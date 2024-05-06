<?php
	
	/**
	 * Copyright © Scalexpert.
	 * This file is part of Scalexpert plugin for WordPress.
	 *
	 * @author    Société Générale
	 * @copyright Scalexpert
	 */
	
	
	namespace wooScalexpert\Controller\Admin;
	
	use wooScalexpert\Helper\API\Client;
	
	/**
	 *
	 *
	 */
	class ConfigController {
		
		protected Client $apiClient;
		protected array  $sg_scalexpert_options;
		
		/**
		 *
		 */
		public function __construct() {
			require_once( PLUGIN_DIR . '/Static/autoload.php' );
			$this->apiClient             = new Client();
			$this->sg_scalexpert_options = get_option( 'sg_scalexpert_keys' );
			add_action( 'admin_init', array( $this, 'sg_scalexpert_page_init' ) );
			add_action( 'admin_notices', array( $this, 'activate_notice_error' ) );
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_admin_page() {
			?>
			
			<div class="wrap">
				<img src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
				<?php AdminController::getAdministrationTopMenu(); ?>
				<?php settings_errors(); ?>
				<p>
					<strong><?= __( "Configure my keys", "woo-scalexpert" ) ?></strong></br><?= __( "Please enter your API keys for the different environments here.", "woo-scalexpert" ) ?>
				</p>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'sg_scalexpert_option_group' );
						do_settings_sections( 'sg-scalexpert-admin' );
					?>
					<button class="button-primary" onclick="checkKey(); return false;"><?= __( "Check my key", "woo-scalexpert" ) ?></button>
					<?php submit_button( __( "Save changes", "woo-scalexpert" ) ); ?>
				</form>
			</div>
			<?php
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_page_init() {
			
			$activationPossible = $this->apiClient->activationPossible();
			
			register_setting(
				'sg_scalexpert_option_group', // option_group
				'sg_scalexpert_keys', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			if ( $activationPossible ) {
				add_settings_section(
					'sg_scalexpert_activation_section', // id
					__( "Activation", "woo-scalexpert" ), // title
					array( $this, 'sg_scalexpert_section_info' ), // callback
					'sg-scalexpert-admin' // page
				);
				
				add_settings_field(
					'activate', // id
					__( "Activate plugin in Woocommerce", "woo-scalexpert" ), // title
					array( $this, 'activate_callback' ), // callback
					'sg-scalexpert-admin', // page
					'sg_scalexpert_activation_section' // section
				);
			}
			
			add_settings_section(
				'sg_scalexpert_setting_section', // id
				__( "API keys", "woo-scalexpert" ), // title
				array( $this, 'sg_scalexpert_section_info' ), // callback
				'sg-scalexpert-admin' // page
			);
			
			add_settings_field(
				'environment', // id
				__( "Choose your environment", "woo-scalexpert" ), // title
				array( $this, 'environment_callback' ), // callback
				'sg-scalexpert-admin', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'api_key_test', // id
				__( "Enter your test ID", "woo-scalexpert" ), // title
				array( $this, 'api_key_test_callback' ), // callback
				'sg-scalexpert-admin', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'secret_test', // id
				__( "Enter your test key", "woo-scalexpert" ), // title
				array( $this, 'secret_test_callback' ), // callback
				'sg-scalexpert-admin', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'api_key', // id
				__( "Enter your production ID", "woo-scalexpert" ), // title
				array( $this, 'api_key_callback' ), // callback
				'sg-scalexpert-admin', // page
				'sg_scalexpert_setting_section' // section
			);
			
			add_settings_field(
				'secret', // id
				__( "Enter your production key", "woo-scalexpert" ), // title
				array( $this, 'secret_callback' ), // callback
				'sg-scalexpert-admin', // page
				'sg_scalexpert_setting_section' // section
			);
			
			
		}
		
		
		/**
		 * @param $input
		 *
		 * @return array
		 *
		 */
		public function sg_scalexpert_sanitize( $input ) {
			
			$sanitary_values = array();
			
			if ( isset( $input['environment'] ) ) {
				$sanitary_values['environment'] = $input['environment'];
			}
			
			if ( isset( $input['api_key'] ) ) {
				$sanitary_values['api_key'] = $this->apiClient->openSslCrypt( sanitize_text_field( $input['api_key'] ) );
			}
			
			if ( isset( $input['api_key_test'] ) ) {
				$sanitary_values['api_key_test'] = $this->apiClient->openSslCrypt( sanitize_text_field( $input['api_key_test'] ) );
			}
			
			if ( isset( $input['secret'] ) ) {
				$sanitary_values['secret'] = $this->apiClient->openSslCrypt( sanitize_text_field( $input['secret'] ) );
			}
			
			if ( isset( $input['secret_test'] ) ) {
				$sanitary_values['secret_test'] = $this->apiClient->openSslCrypt( sanitize_text_field( $input['secret_test'] ) );
			}
			
			if ( isset( $input['activate'] ) ) {
				$sanitary_values['activate'] = $input['activate'];
			}
			
			return $sanitary_values;
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_section_info() {}
		
		
		/**
		 * @return void
		 */
		public function environment_callback() {
			?> <select name="sg_scalexpert_keys[environment]" id="environment">
				<?php $selected = ( isset( $this->sg_scalexpert_options['environment'] ) && $this->sg_scalexpert_options['environment'] === 'UAT' ) ? 'selected' : ''; ?>
				<option <?php echo $selected; ?>>Test</option>
				<?php $selected = ( isset( $this->sg_scalexpert_options['environment'] ) && $this->sg_scalexpert_options['environment'] === 'Production' ) ? 'selected' : ''; ?>
				<option <?php echo $selected; ?>>Production</option>
			</select> <?php
		}
		
		/**
		 * @return void
		 */
		public function api_key_callback() {
			printf(
				'<input class="regular-text" type="text" name="sg_scalexpert_keys[api_key]" id="api_key" value="%s"></br><a href="' . URL_SCALEXPERT_SG . '" target="_blank">' . __( "Find my key", "woo-scalexpert" ) . '</a>',
				isset( $this->sg_scalexpert_options['api_key'] ) ? $this->apiClient->openSslDeCrypt( esc_attr( $this->sg_scalexpert_options['api_key'] ) ) : ''
			);
		}
		
		/**
		 * @return void
		 */
		public function api_key_test_callback() {
			printf(
				'<input class="regular-text" type="text" name="sg_scalexpert_keys[api_key_test]" id="api_key_test" value="%s"></br><a href="' . URL_SCALEXPERT_SG . '" target="_blank">' . __( "Find my key", "woo-scalexpert" ) . '</a>',
				isset( $this->sg_scalexpert_options['api_key_test'] ) ? $this->apiClient->openSslDeCrypt( esc_attr( $this->sg_scalexpert_options['api_key_test'] ) ) : ''
			);
		}
		
		/**
		 * @return void
		 */
		public function activate_callback() {
			$activated = ( $this->sg_scalexpert_options['activate'] && $this->sg_scalexpert_options['activate'] === "1" )
				? __( "Activé", "woo-scalexpert" )
				: __( "Désactivé", "woo-scalexpert" );
			?>
			<input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_activate" name="sg_scalexpert_keys[activate]" value="<?= $this->sg_scalexpert_options['activate'] ?>"
			       onchange="toggleActivate('sg_scalexpert_activate','<?= __( "Activé", "woo-scalexpert" ) ?>','<?= __( "Désactivé", "woo-scalexpert" ) ?>' );"
				<?php echo $this->sg_scalexpert_options['activate'] && $this->sg_scalexpert_options['activate'] === "1" ? 'checked' : ''; ?>
			>
			<label id="label_sg_scalexpert_activate" for="sg_scalexpert_activate"><?= $activated ?></label>
			<?php
		}
		
		/**
		 * @return void
		 */
		public function secret_callback() {
			printf(
				'<div class="wp-pwd">
					<input type="password" name="sg_scalexpert_keys[secret]" id="secret" aria-describedby="login-message" class="regular-text input password-input" value="%s" size="20" autocomplete="current-password" spellcheck="false">
					<button onclick="tooglePass(\'secret\');" type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</button>
				</div>',
				isset( $this->sg_scalexpert_options['secret'] ) ? $this->apiClient->openSslDeCrypt( esc_attr( $this->sg_scalexpert_options['secret'] ) ) : ''
			);
		}
		
		/**
		 * @return void
		 */
		public function secret_test_callback() {
			printf(
				'<div class="wp-pwd">
					<input type="password" name="sg_scalexpert_keys[secret_test]" id="secret_test" aria-describedby="login-message" class="regular-text input password-input" value="%s" size="20" autocomplete="current-password" spellcheck="false">
					<button onclick="tooglePass(\'secret_test\');" type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</button>
				</div>',
				isset( $this->sg_scalexpert_options['secret_test'] ) ? $this->apiClient->openSslDeCrypt( esc_attr( $this->sg_scalexpert_options['secret_test'] ) ) : ''
			);
		}
		
		
		/**
		 * @return void
		 */
		public function activate_notice_error() {
			
			$activated['activate'] = NULL;
			$class                 = 'notice notice-error';
			$message               = __( 'Ooops! Scalexpert is not yet activated!', 'woo-scalexpert' );
			$activated             = $this->apiClient->getScalexpertOptions();
			$activated             = ( isset( $activated['activate'] ) ) ? $activated['activate'] : 0;
			$activationPossible    = $this->apiClient->activationPossible();
			
			if ( ( ! $activated ) || ( ! $activationPossible ) ) {
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			}
		}
		
	}
