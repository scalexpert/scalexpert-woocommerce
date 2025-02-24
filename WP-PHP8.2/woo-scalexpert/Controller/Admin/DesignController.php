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
	
	class DesignController {
		protected Client $apiClient;
		protected        $eFinancingSolutions = [];
		protected        $sg_scalexpert_options;
		protected        $eFinancingSolutionActivated;
        protected string $sectionName;

        public function __construct() {
			if ( is_admin() && isset( $_REQUEST['page'] )
                || (
                    array_key_exists('option_page', $_POST)
                    && $_POST['option_page'] == 'sg_scalexpert_custom_group'
                )
            ) {
				require_once( PLUGIN_DIR . '/Static/autoload.php' );
				$this->apiClient = new Client();
				
				if ( $this->getSolutionCode() ) {
					if ( get_option( 'sg_scalexpert_design_' . $this->getSolutionCode() ) ) {
						$this->sg_scalexpert_options = get_option( 'sg_scalexpert_design_' . $this->getSolutionCode() );
					} else {
						$this->sg_scalexpert_options = array();
						$options                     = array(
							"activate" => "",
						);
						add_option( 'sg_scalexpert_design_' . $this->getSolutionCode(), $options );
					}
				}


				try {
					$this->eFinancingSolutions = $this->apiClient->getFinancialSolutions();
				} catch ( Exception $e ) {
					echo 'Exception reçue : ', $e->getMessage(), "\n";
					$this->eFinancingSolutions = array();
				}

				$this->eFinancingSolutionActivated = ( array_key_exists('solution', $_GET) && get_option( "sg_scalexpert_activated_" . $_GET['solution'] ) ) ? get_option( "sg_scalexpert_activated_" . $_GET['solution'] ) : 0;

				add_action( 'admin_init', array( $this, 'sg_scalexpert_customisation_init' ) );
				add_action( 'admin_init', array( $this, 'sg_scalexpert_customisation_sectionProduct' ) );
				add_action( 'admin_init', array( $this, 'sg_scalexpert_customisation_sectionCart' ) );
				add_action( 'admin_init', array( $this, 'sg_scalexpert_customisation_sectionPaiement' ) );
				add_action( 'admin_init', array( $this, 'sg_scalexpert_customisation_sectionGeneral' ) );

                $this->sectionName = "sg_scalexpert_design_" . $this->getSolutionCode();
			}
		}

        public function getSolutionCode(): string
        {
            if ($_GET && array_key_exists('solution', $_GET)) {
                return $_GET['solution'];
            }
            return '';
        }

        public function getSgScalexpertOptions(string $key)
        {
            if ($this->sg_scalexpert_options && array_key_exists($key, $this->sg_scalexpert_options)) {
                return $this->sg_scalexpert_options[$key];
            }
            return '';
        }

		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_create_customisation_page() {
			?>
            <div class="wrap">
                <img alt="Scaleexpert logo" src="<?= plugins_url( '/woo-scalexpert/assets/img/Scaleexpert_logo.jpg' ); ?>" width="150">
                <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php
						$financeDesignController = new FinancingController();
						foreach ( $this->eFinancingSolutions as $solutionCode => $solution ) {
							$navTabActive = ($this->getSolutionCode() == $solutionCode ) ? " nav-tab-active" : "";
							?>
                            <a href="./admin.php?page=sg-scalexpert-customisation&solution=<?= $solutionCode ?>"
                               class="nav-tab<?= $navTabActive ?>">
								<?php
									echo ( get_option( 'sg_scalexpert_activated_' . $solutionCode ) && get_option( 'sg_scalexpert_activated_' . $solutionCode )['activate'] === "1" )
										?
										'<span>&#10003;</span>'
										:
										'<span>&#10060;</span>';
								?>
								<?= $financeDesignController->getTitleBySolution( $solution, '' ); ?>
                            </a>
							<?php
						}
					?>
                </nav>
				<?php settings_errors();

					if ( $this->getSolutionCode() ) {
						?>
                        <form enctype="multipart/form-data" method="post" action="options.php?solution=<?= $this->getSolutionCode() ?>">
							<?php
								settings_fields( 'sg_scalexpert_custom_group' );
								do_settings_sections( 'sg-scalexpert-design-' . $this->getSolutionCode() );
								do_settings_sections( 'sg-scalexpert-design' );
								if ( $this->eFinancingSolutionActivated ) {
									submit_button( __( "Save changes", "woo-scalexpert" ) );
								}
							?>
                        </form>

						<?php
					} else {
						?>
                        <div class="notice"><p><?= __( "Select a solution", "woo-scalexpert" ) ?></p></div>
						<?php
					}
				?>
            </div>
			<?php
		}

		/**
		 * @return void
		 *
		 */
		public function sg_scalexpert_customisation_init() {
            register_setting(
                'sg_scalexpert_custom_group', // option_group
                'sg_scalexpert_design_' . $this->getSolutionCode(), // option_name
                array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
            );

			register_setting(
				'sg_scalexpert_custom_group', // option_group
				'sg_scalexpert_design', // option_name
				array( $this, 'sg_scalexpert_sanitize' ) // sanitize_callback
			);

            add_settings_section(
                'sg_scalexpert_setting_sectionTop', // id
                __( "Customise", "woo-scalexpert" ), // title
                array( $this, 'sg_scalexpert_section_info' ), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode() // page
            );

            add_settings_field(
                'checkactivate', // id
                "", // title
                array( $this, 'checkactivate_callback' ), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionTop' // section
            );
		}

		public function sg_scalexpert_customisation_sectionProduct() {
            add_settings_section(
                'sg_scalexpert_setting_sectionProduct', // id
                __("Product page", "woo-scalexpert"), // title
                array($this, 'sg_scalexpert_section_info'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode() // page
            );

            add_settings_field(
                'activate', // id
                __("Display on product sheets", "woo-scalexpert"), // title
                array($this, 'activate_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionProduct' // section
            );

            add_settings_field(
                'bloc_title', // id
                __("Customise the block", "woo-scalexpert"), // title
                array($this, 'bloc_title_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionProduct' // section
            );

            add_settings_field(
                'showlogo', // id
                __("Display the logo", "woo-scalexpert"), // title
                array($this, 'showlogo_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionProduct' // section
            );

			add_settings_section(
				'sg_scalexpert_setting_sectionProduct', // id
				__( "Financing Simulator", "woo-scalexpert" ), // title
				array( $this, 'sg_scalexpert_section_info' ), // callback
				'sg-scalexpert-design' // page
			);

			add_settings_field(
				'blocposition', // id
				__( "Unique position on product pages", "woo-scalexpert" ), // title
				array( $this, 'blocposition_callback' ), // callback
				'sg-scalexpert-design', // page
				'sg_scalexpert_setting_sectionProduct' // section
			);

            add_settings_field(
                'blocposition_cart', // id
                __( "Unique position on cart page", "woo-scalexpert" ), // title
                array( $this, 'blocposition_cart_callback' ), // callback
                'sg-scalexpert-design', // page
                'sg_scalexpert_setting_sectionProduct' // section
            );
		}

        public function sg_scalexpert_customisation_sectionCart(): void
        {
            add_settings_section(
                'sg_scalexpert_setting_sectionCart', // id
                __("Cart page", "woo-scalexpert"), // title
                array($this, 'sg_scalexpert_section_info'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode() // page
            );

            add_settings_field(
                'activateCart', // id
                __("Display on cart", "woo-scalexpert"), // title
                array($this, 'activateCart_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionCart' // section
            );

            add_settings_field(
                'cart_title', // id
                __("Customise payment", "woo-scalexpert"), // title
                array($this, 'cart_title_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionCart' // section
            );

            add_settings_field(
                'showlogo_cart_cart', // id
                __("Show logo for cart", "woo-scalexpert"), // title
                array($this, 'showlogo_cart_cart_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionCart' // section
            );
        }

		public function sg_scalexpert_customisation_sectionPaiement(): void
        {
            add_settings_section(
                'sg_scalexpert_setting_sectionPaiement', // id
                __("Payment page", "woo-scalexpert"), // title
                array($this, 'sg_scalexpert_section_info'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode() // page
            );

            add_settings_field(
                'payment_title', // id
                __("Customise payment", "woo-scalexpert"), // title
                array($this, 'payment_title_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionPaiement' // section
            );

            add_settings_field(
                'showlogo_cart', // id
                __("Show logo for basket", "woo-scalexpert"), // title
                array($this, 'showlogo_cart_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionPaiement' // section
            );
		}

		public function sg_scalexpert_customisation_sectionGeneral(): void
        {
            add_settings_section(
                'sg_scalexpert_setting_sectionGeneral', // id
                __("General Configuration", "woo-scalexpert"), // title
                array($this, 'sg_scalexpert_section_info'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                array(
                    'after_section' => __("En sélectionnant des catégories, l'encart ne sera pas affiché pour les produits de ces catégories.") . '<br>' . __("Pour sélectionner plusieurs catégories, maintenez la touche CTRL et cliquer avec la souris sur les options souhaitées.")
                )
            );

            add_settings_field(
                'exclude_cats', // id
                __("Exclusion of categories", "woo-scalexpert"), // title
                array($this, 'exclude_cats_callback'), // callback
                'sg-scalexpert-design-' . $this->getSolutionCode(), // page
                'sg_scalexpert_setting_sectionGeneral' // section
            );
		}

		/**
		 * @param $input
		 *
		 * @return array
		 *
		 */
		public function sg_scalexpert_sanitize( $input ): array
        {
			$sanitary_values = array();
			foreach ( $input as $key => $val ) {
				if ( $key == "exclude_cats" ) {
					$val                     = implode( ",", $val );
					$sanitary_values[ $key ] = $val;
				} else {
					$sanitary_values[ $key ] = sanitize_text_field( $val );
				}
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
		public function blocposition_callback(): void
        {
			$blocposition = get_option( "sg_scalexpert_design" );

			?> <select name="sg_scalexpert_design[blocposition]" id="blocposition">
				<?php $selected = ( isset( $blocposition ) && $blocposition['blocposition'] === 'under' ) ? 'selected' : ''; ?>
                <option value="under" <?php echo $selected; ?>><?= __( "Under the add to basket blocks", "woo-scalexpert" ) ?></option>
				<?php $selected = ( isset( $blocposition ) && $blocposition['blocposition'] === 'over' ) ? 'selected' : ''; ?>
                <option value="over" <?php echo $selected; ?>><?= __( "At the top of the add to basket block", "woo-scalexpert" ) ?></option>
            </select> <?php
		}

        public function blocposition_cart_callback(): void
        {
            $blocposition = get_option( "sg_scalexpert_design" );

            ?> <select name="sg_scalexpert_design[blocposition_cart]" id="blocposition_cart">
                <?php $selected = ( isset( $blocposition ) && $blocposition['blocposition_cart'] === 'under' ) ? 'selected' : ''; ?>
                <option value="under" <?php echo $selected; ?>><?= __( "Under the basket block", "woo-scalexpert" ) ?></option>
                <?php $selected = ( isset( $blocposition ) && $blocposition['blocposition_cart'] === 'over' ) ? 'selected' : ''; ?>
                <option value="over" <?php echo $selected; ?>><?= __( "At the top basket block", "woo-scalexpert" ) ?></option>
            </select> <?php
        }


		/**
		 * @return void
		 */
		public function activate_callback(): void
        {
			$checked   = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('activate') : 0;
			$activated = ( $checked )
				? __( "Activated", "woo-scalexpert" )
				: __( "Off", "woo-scalexpert" );
			?>
            <input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_design_activate" name="<?= $this->sectionName ?>[activate]"
                   value="<?= $checked ?>"
                   onchange="toggleActivate('sg_scalexpert_design_activate','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo $checked === "1" ? 'checked' : ''; ?>
            >
            <label id="label_sg_scalexpert_design_activate" for="sg_scalexpert_design_activate"><?= $activated ?></label>
			<?php
		}

        /**
         * @return void
         */
        public function activateCart_callback(): void
        {
            $checked   = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('activateCart') : 0;
            $activated = ( $checked )
                ? __( "Activated", "woo-scalexpert" )
                : __( "Off", "woo-scalexpert" );
            ?>
            <input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_design_activateCart" name="<?= $this->sectionName ?>[activateCart]"
                   value="<?= $checked ?>"
                   onchange="toggleActivate('sg_scalexpert_design_activateCart','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
                <?php echo $checked === "1" ? 'checked' : ''; ?>
            >
            <label id="label_sg_scalexpert_design_activateCart" for="sg_scalexpert_design_activateCart"><?= $activated ?></label>
            <?php
        }


		/**
		 * @return void
		 */
		public function checkactivate_callback(): void
        {
			$checked = ( get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) ) ? get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) : 0;
			if ( $checked ) {
				$message = __( "This option is enabled on your site", "woo-scalexpert" );
				$class   = 'notice notice-success settings-error is-dismissible';
			} else {
				$message = __( "This option is disabled on your site", "woo-scalexpert" );
				$class   = 'notice notice-error settings-error is-dismissible';
			}
			printf( '<div class="%1$s"><p>%2$s <a href="/wp-admin/admin.php?page=sg-scalexpert-finances">' . __( "Enable / Disable", "woo-scalexpert" ) . '</a></p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		/**
		 * @return void
		 *
		 */
		public function bloc_title_callback(): void
        {
			$text = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('bloc_title') : "";
			?>
            <input type="text" id="sg_scalexpert_design_bloc_title" name="<?= $this->sectionName ?>[bloc_title]" placeholder="<?= __( "Title", "woo-scalexpert" ) ?>"
                   value="<?= $text ?>">
			<?php
		}

		/**
		 * @return void
		 */
		public function showlogo_callback(): void
        {
			$checked   = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('showlogo') : 0;
			$activated = ( $checked )
				? __( "Activated", "woo-scalexpert" )
				: __( "Off", "woo-scalexpert" );
			?>
            <input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_design_showlogo" name="<?= $this->sectionName ?>[showlogo]"
                   value="<?= $checked ?>"
                   onchange="toggleActivate('sg_scalexpert_design_showlogo','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo $checked === "1" ? 'checked' : ''; ?>
            >
            <label id="label_sg_scalexpert_design_showlogo" for="sg_scalexpert_design_showlogo"><?= $activated ?></label>
			<?php
		}

        public function cart_title_callback(): void
        {
            $text = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('cart_title') : "";
            ?>
            <input type="text" id="sg_scalexpert_design_cart_title" name="<?= $this->sectionName ?>[cart_title]" placeholder="<?= __( "Title", "woo-scalexpert" ) ?>"
                   value="<?= $text ?>">
            <?php
        }

        /**
         * @return void
         */
        public function showlogo_cart_cart_callback(): void
        {
            $checked   = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('showlogo_cart_cart') : 0;
            $activated = ( $checked )
                ? __( "Activated", "woo-scalexpert" )
                : __( "Off", "woo-scalexpert" );
            ?>
            <input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_design_showlogo_cart_cart" name="<?= $this->sectionName ?>[showlogo_cart_cart]"
                   value="<?= $checked ?>"
                   onchange="toggleActivate('sg_scalexpert_design_showlogo_cart_cart','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
                <?php echo $checked === "1" ? 'checked' : ''; ?>
            >
            <label id="label_sg_scalexpert_design_showlogo_cart_cart" for="sg_scalexpert_design_showlogo_cart_cart"><?= $activated ?></label>
            <?php
        }

		/**
		 * @return void
		 *
		 */
		public function payment_title_callback(): void
        {
			$text = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('payment_title') : "";
			?>
            <input type="text" id="sg_scalexpert_design_payment_title" name="<?= $this->sectionName ?>[payment_title]" placeholder="<?= __( "Title", "woo-scalexpert" ) ?>"
                   value="<?= $text ?>">
			<?php
		}

		/**
		 * @return void
		 */
		public function showlogo_cart_callback(): void
        {
			$checked   = ( get_option( $this->sectionName ) ) ? $this->getSgScalexpertOptions('showlogo_cart') : 0;
			$activated = ( $checked )
				? __( "Activated", "woo-scalexpert" )
				: __( "Off", "woo-scalexpert" );
			?>
            <input type="checkbox" class="wppd-ui-toggle" id="sg_scalexpert_design_showlogo_cart" name="<?= $this->sectionName ?>[showlogo_cart]"
                   value="<?= $checked ?>"
                   onchange="toggleActivate('sg_scalexpert_design_showlogo_cart','<?= __( "Activated", "woo-scalexpert" ) ?>','<?= __( "Off", "woo-scalexpert" ) ?>');"
				<?php echo $checked === "1" ? 'checked' : ''; ?>
            >
            <label id="label_sg_scalexpert_design_showlogo_cart" for="sg_scalexpert_design_showlogo_cart"><?= $activated ?></label>
			<?php
		}

		/**
		 * @return void
		 *
		 */
		public function design_payment_bloc() {
			$enabled = ( get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) ) ? get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) : 0;
			if ( $enabled ) {
				?>
                <h2 class="scalexpertAdmin"><?= __( "Payment page", "woo-scalexpert" ) ?></h2>
                <h3 class="scalexpertAdmin"><?= __( "Customise the block", "woo-scalexpert" ) ?></h3>
			<?php }
		}

		/**
		 * @return void
		 *
		 */
		public function design_product_bloc_title() {
			?>
            <h2 class="scalexpertAdmin"><?= __( "Customise the block", "woo-scalexpert" ) ?></h2>
			<?php
		}

		/**
		 * @return void
		 */
		public function product_bloc_title() {
			$enabled = ( get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) ) ? get_option( "sg_scalexpert_activated_" . $this->getSolutionCode() ) : 0;
			if ( $enabled ) {
				?>
                <h2 class="scalexpertAdmin"><?= __( "Product page", "woo-scalexpert" ) ?></h2>
                <h3 class="scalexpertAdmin"><?= __( "Customise the block", "woo-scalexpert" ) ?></h3>
			<?php }
		}
		
		/**
		 * @return void
		 */
		public function exclude_cats_title_callback() {
			?>
            <h2 class="scalexpertAdmin"><?= __( "Exclusion of categories", "woo-scalexpert" ) ?></h2>
			<?php
		}
		
		/**
		 * @return void
		 *
		 */
		public function exclude_cats_callback() {
			$excludes = ( $this->sectionName ) ? $this->getSgScalexpertOptions('exclude_cats') : array();
			$excludes = explode( ",", $excludes );
			$terms    = get_terms( array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => TRUE,
			) );
			?>
            <select size="10" multiple name="<?= $this->sectionName ?>[exclude_cats][]" id="exclude_cats">
				<?php
					foreach ( $terms as $term ) {
						$selected = ( in_array( $term->term_id, $excludes ) ) ? 'selected' : '';
						$parent   = get_term( $term->parent );
						$parent   = ( $parent->name != "" ) ? $parent->name . " > " : "";
						?>
                        <option value="<?= $term->term_id ?>" <?php echo $selected; ?>>Boutique > <?= $parent . $term->name ?> </option>
					<?php } ?>
            </select>
			<?php
		}
		
		
	}
