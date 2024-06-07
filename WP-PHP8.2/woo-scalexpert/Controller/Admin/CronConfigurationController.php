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
	class CronConfigurationController {
		
		protected $sg_cron_configuration_settings;
		
		public function __construct() {
			if ( is_admin() && isset( $_REQUEST['page'] ) || ( $_POST["option_page"] == "cron_configuration_settings_group" ) ) {
				$this->sg_cron_configuration_settings = $this->getSgCronConfigurationSettings();
				
				if ( ! isset( $this->sg_cron_configuration_settings['activate_cron'] )
				     && $this->sg_cron_configuration_settings['activate_cron'] != 1 ) {
					$options = array(
						"activate_cron" => "",
						"interval_time" => "",
					);
					update_option( 'sg_cron_configuration_settings', $options );
				}
				apply_filters( 'cron_schedules', array(
						'interval' => 604800,
						'display'  => __( 'Once Weekly' )
					)
				);
				add_action( 'admin_init', array( $this, 'sg_scalexpert_configurable_settings_page_init' ) );
			}
			
		}
		
		/**
		 * @return false|mixed|null
		 *
		 */
		public function getSgCronConfigurationSettings() {
			$options = array();
			if ( get_option( 'sg_cron_configuration_settings' ) ) {
				return get_option( 'sg_cron_configuration_settings' );
			} else {
				$options = array(
					"activate_cron" => "",
					"interval_time" => "",
				);
				add_option( 'sg_cron_configuration_settings', $options );
				
				return $options;
			}
		}
		
		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_cron_settings_page() {
			?>
			
			<div class="wrap">
				<img alt="" src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
				<p>
					<strong><?= __( "Cron settings", "woo-scalexpert" ) ?></strong>
					</br>
					<?= __( "Settings for cron update status", "woo-scalexpert" ) ?>
				</p>
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'cron_configuration_settings_group' );
						do_settings_sections( 'sg-scalexpert-cron-configurable-settings' );
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
				'cron_configuration_settings_group', // option_group
				'sg_cron_configuration_settings', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);
			
			add_settings_section(
				'cron_configuration_settings_section', // id
				'',//__( "Paramètres", "woo-scalexpert" ), // title
				array( $this, "sg_scalexpert_configurable_settings_info" ), // callback
				'sg-scalexpert-cron-configurable-settings' // page
			);
			
			add_settings_field(
				'activate_cron', // id
				__( 'Activate cron', 'woo-scalexpert' ), // title
				array( $this, 'cron_activated_callback' ), // callback
				'sg-scalexpert-cron-configurable-settings', // page
				'cron_configuration_settings_section' // section
			);
			
			add_settings_field(
				'interval_time', // id
				__( 'Interval time', 'woo-scalexpert' ), // title
				array( $this, 'interval_time_callback' ), // callback
				'sg-scalexpert-cron-configurable-settings', // page
				'cron_configuration_settings_section' // section
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
			
			if ( isset( $input['activate_cron'] ) ) {
				$sanitary_values['activate_cron'] = $input['activate_cron'];
			}
			
			if ( isset( $input['interval_time'] ) ) {
				$sanitary_values['interval_time'] = $input['interval_time'];
				
				wp_clear_scheduled_hook( 'cron_update_status_job' );
				wp_schedule_event( time(), $sanitary_values['interval_time'], 'cron_update_status_job' );
				
			}
			
			return $sanitary_values;
		}
		
		/**
		 * @return void
		 */
		public function cron_activated_callback() : void {
			?>
			<input type="checkbox" class="wppd-ui-toggle" id="sg_cron_configuration_settings" name="sg_cron_configuration_settings[activate_cron]" value="1"
			       onchange="changeLabel('sg_scalexpert_configurable_settings','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo $this->sg_cron_configuration_settings['activate_cron'] && $this->sg_cron_configuration_settings['activate_cron'] === "1" ? 'checked' : ''; ?>
			>
			<label for="field-id"
			       id="sg_cron_configuration_settings_label"><?php echo $this->sg_cron_configuration_settings['activate_cron'] && $this->sg_cron_configuration_settings['activate_cron'] === "1"
					? __( "Activated", "woo-scalexpert" )
					: __( "Off", "woo-scalexpert" );
				?></label>
			<?php
		}
		
		/**
		 * @return void
		 */
		public function interval_time_callback() {
			$times = array_filter( wp_get_schedules(), function ( $k, $v ) {
				return str_contains( $v, "scalexpert_updateorder_" );
			}, ARRAY_FILTER_USE_BOTH );
			
			if ( ! isset( $this->sg_cron_configuration_settings['activate_cron'] )
			     && $this->sg_cron_configuration_settings['activate_cron'] != 1 ) {
				$this->sg_cron_configuration_settings['interval_time'] = "";
			}
			
			?> <select name="sg_cron_configuration_settings[interval_time]" id="interval_time">
				<?php
					foreach ( $times as $key_interval => $value ) {
						if ( str_contains( $key_interval, "scalexpert_updateorder_" ) ) {
							$selected = ( isset( $this->sg_cron_configuration_settings['interval_time'] ) && $this->sg_cron_configuration_settings['interval_time'] == $key_interval ) ? 'selected' : '';
							
							if ( isset( $this->sg_cron_configuration_settings['interval_time'] )
							     && $this->sg_cron_configuration_settings['interval_time'] == ""
							     && $key_interval == "scalexpert_updateorder_hourly" ) {
								$selected = 'selected';
							};
							?>
							<option value="<?php echo $key_interval; ?>" <?php echo $selected; ?>><?= __( $value['display'], "woo-scalexpert" ) ?></option>
							<?php
						}
					}
				?>
			</select></br>
			<em><p><strong><?= __( "Save the configuration to make sure it is applied.", "woo-scalexpert" ) ?></strong></br></br>
					<?= __( "Execute update status order with following link :", "woo-scalexpert" ) ?></br>
					<?= __( "It is preferable for the performance of your Wordpress installation to run the CRON task via a server crontab. To do this, give your server administrator the following url:<br> HOMEURL / SHOPPAGE (shop, boutique, ...) / ?updateOrder (see example below)", "woo-scalexpert" ) ?>
					</br>
					<a href="<?php echo get_option( 'home' ) . '?updateOrder'; ?>" target=”_blank”><?php echo get_option( 'home' ) . '/shop/?updateOrder'; ?></a>
				</p>
			</em>
			<?php
		}
	}
