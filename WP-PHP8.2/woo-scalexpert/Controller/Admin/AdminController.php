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
	
	class AdminController {
		
		public const                            PAGE_NAME = "keys";
		protected Client                      $apiClient;
		protected ConfigController            $configController;
		protected DebugController             $debugController;
		protected FinancingController         $financingController;
		protected DesignController            $designController;
		protected SettingController           $settingController;
		protected CronConfigurationController $cronConfigurationController;
		
		
		public function __construct() {
			
			require_once( PLUGIN_DIR . '/Static/autoload.php' );
			add_action( 'admin_menu', array( $this, 'sg_scalexpert_add_plugin_page' ) );
			
			$this->configController            = new ConfigController();
			$this->debugController             = new DebugController();
			$this->financingController         = new FinancingController();
			$this->designController            = new DesignController();
			$this->settingController           = new SettingController();
			$this->cronConfigurationController = new CronConfigurationController();
			
		}
		
		
		/**
		 * @return void
		 */
		public function sg_scalexpert_add_plugin_page() {
			add_menu_page(
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "SG Scalexpert", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert', // menu_slug
				'', // function
				'/wp-content/plugins/woo-scalexpert/assets/img/logoSG.png', // icon_url
				2 // position
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "Administer", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-keys', // menu_slug
				array( $this->configController, 'sg_scalexpert_create_admin_page' ) // function
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "Debug", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-debug', // menu_slug
				array( $this->debugController, 'sg_scalexpert_create_debug_page' ) // function
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "Financing", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-finances', // menu_slug
				array( $this->financingController, 'sg_scalexpert_create_finance_page' ) // function
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "Customise", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-customisation', // menu_slug
				array( $this->designController, 'sg_scalexpert_create_customisation_page' ) // function
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "WooCommerce", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-settings', // menu_slug
				array( $this->settingController, 'sg_scalexpert_create_settings_page' ) // function
			);
			
			add_submenu_page(
				'sg-scalexpert', // parent page
				__( "SG Scalexpert", "woo-scalexpert" ), // page_title
				__( "Cron settings", "woo-scalexpert" ), // menu_title
				'manage_options', // capability
				'sg-scalexpert-cron-settings', // menu_slug
				array( $this->cronConfigurationController, 'sg_scalexpert_create_cron_settings_page' ) // function
			);
			
			remove_submenu_page( 'sg-scalexpert', 'sg-scalexpert' );
		}
		
		
		/**
		 * @param $hook
		 *
		 * @return void
		 */
		public function load_scalexpert_admin_scripts( $hook ) {
			// $hook is string value given add_menu_page function.
			if ( ( $hook == 'sg-scalexpert_page_sg-scalexpert-keys' )
			     || ( $hook == 'sg-scalexpert_page_sg-scalexpert-debug' )
			     || ( $hook == 'sg-scalexpert_page_sg-scalexpert-finances' )
			     || ( $hook == 'sg-scalexpert_page_sg-scalexpert-customisation' )
			     || ( $hook == 'sg-scalexpert_page_sg-scalexpert-settings' )
			) {
				
				wp_enqueue_style( 'scalexpert_admin_css', plugins_url( '/assets/admin-style.css', __FILE__ ) );
				wp_enqueue_script( 'jquery-ui-core', FALSE, array( 'jquery' ) );
				wp_enqueue_script( 'jquery-ui-dialog', FALSE, array( 'jquery' ) );
				wp_localize_script( 'sgCheckKey', 'sgCheckKey', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
				wp_enqueue_script( 'scalexpert_admin', plugins_url( '/assets/admin-script.js', __FILE__ ) );
			}
			
		}
		
		
		/**
		 * @return void
		 *
		 */
		public static function getAdministrationTopMenu( string $activeTab = self::PAGE_NAME ) {
			?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="./admin.php?page=sg-scalexpert-keys"
				   class="nav-tab <?php echo ( $activeTab === self::PAGE_NAME ) ? "nav-tab-active" : ""; ?>"><?= __( "Setting the keys", "woo-scalexpert" ) ?></a><a
					href="./admin.php?page=sg-scalexpert-debug"
					class="nav-tab <?php echo ( $activeTab === DebugController::PAGE_NAME ) ? "nav-tab-active" : ""; ?>"><?= __( "Mode debug", "woo-scalexpert" ) ?></a><a
					href="./admin.php?page=sg-scalexpert-finances"
					class="nav-tab <?php echo ( $activeTab === FinancingController::PAGE_NAME ) ? "nav-tab-active" : ""; ?>"><?= __( "Activate / deactivate", "woo-scalexpert" ) ?></a>
			</nav>
			<?php
		}
		
	}