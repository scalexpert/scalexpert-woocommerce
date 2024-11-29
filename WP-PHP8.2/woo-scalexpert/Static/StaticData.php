<?php
	
	
	$financements = [
		'SCFRSP-4XTS' => __( "Paiement 4X sans frais (SCFRSP-4XTS)", "woo-scalexpert" ),
		'SCFRSP-4XPS' => __( "Paiement 4X frais partagés (SCFRSP-4XPS)", "woo-scalexpert" ),
		'SCFRSP-3XPS' => __( 'Paiement 3X frais partagés (SCFRSP-3XPS)', "woo-scalexpert" ),
		'SCFRSP-3XTS' => __( 'Paiement 3X sans frais (SCFRSP-3XTS)', "woo-scalexpert" ),
		'SCFRLT-TXPS' => __( 'Crédit long frais partagés (SCFRLT-TXPS)', "woo-scalexpert" ),
		'SCFRLT-TXNO' => __( 'Crédit long (SCFRLT-TXNO)', "woo-scalexpert" ),
		'SCFRLT-TXTS' => __( 'Crédit long sans frais (SCFRLT-TXTS)', "woo-scalexpert" ),
		'SCDELT-DXTS' => __( 'Crédit long (sans frais)', "woo-scalexpert" ),
		'SCDELT-DXCO' => __( 'Crédit long (avec commission)', "woo-scalexpert" ),
	];
	
	$solutionGrouped = [
		'financials' => [
			[ 'SCFRSP-3XTS', 'SCFRSP-3XPS' ],
			[ 'SCFRSP-4XTS', 'SCFRSP-4XPS' ],
			[ 'SCFRLT-TXPS', ],
			[ 'SCFRLT-TXNO', ],
		],
		'insurance'  => [ [ 'CIFRWE-DXCO', ], ],
	];
	
	
	$cronSchedules = array(
		"scalexpert_updateorder_none"           => array(
			'interval' => '',
			'display'  => __( 'Make a selection', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_every_minute"   => array(
			'interval' => 60,
			'display'  => __( 'Every Minute', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_every_5_minute" => array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_halfhourly"     => array(
			'interval' => 1800,
			'display'  => __( 'Every half an Hour', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_hourly"         => array(
			'interval' => 3600,
			'display'  => __( 'Every Hour', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_twicedaily"     => array(
			'interval' => 43200,
			'display'  => __( 'Twice a Day', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_daily"          => array(
			'interval' => 86400,
			'display'  => __( 'Once a Day', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_weekly"         => array(
			'interval' => 604800,
			'display'  => __( 'Once a Week', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_fifteendays"    => array(
			'interval' => 1296000,
			'display'  => __( 'Every two weeks', 'woo-scalexpert' ),
		),
		"scalexpert_updateorder_monthly"        => array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'woo-scalexpert' ),
		),
	);
	
	
	/**
	 * For UAT purpose only
	 */
	$apiDebug = get_option( 'sg_scalexpert_debug' );
	if ( ! empty( $apiDebug['mode_cache'] ) && $apiDebug['mode_cache'] === "1" ) {
		defined( 'SCALEXPERT_APICACHE' ) or define( "SCALEXPERT_APICACHE", TRUE );
		defined( 'SCALEXPERT_TRANSIENTS' ) or define( "SCALEXPERT_TRANSIENTS", $apiDebug['cache_life'] );
	} else {
		defined( 'SCALEXPERT_APICACHE' ) or define( "SCALEXPERT_APICACHE", FALSE );
		defined( 'SCALEXPERT_TRANSIENTS' ) or define( "SCALEXPERT_TRANSIENTS", NULL );
	}
	/**
	 * Cache duration
	 */
	if ( ! empty( $apiDebug['cache_life'] ) ) {
        defined( 'SCALEXPERT_APICACHE' ) or define( "SCALEXPERT_APICACHE", TRUE );
        defined( 'SCALEXPERT_TRANSIENTS' ) or define( "SCALEXPERT_TRANSIENTS", $apiDebug['cache_life'] );
	} else {
        defined( 'SCALEXPERT_APICACHE' ) or define( "SCALEXPERT_APICACHE", FALSE );
		defined( 'SCALEXPERT_TRANSIENTS' ) or define( "SCALEXPERT_TRANSIENTS", NULL );
	}
	
	defined( 'SCALEXPERTWOOTITLE' ) or define( 'SCALEXPERTWOOTITLE', "Paiement en plusieurs fois" );
	defined( 'SCALEXPERTWOODESCRIBE' ) or define( 'SCALEXPERTWOODESCRIBE', "Paiement en plusieurs fois" );
	defined( 'SCALEXPERTCRONSCHEDULES' ) or define( 'SCALEXPERTCRONSCHEDULES', $cronSchedules );
	defined( 'SCALEXPERTGROUPEDSOLUTIONS' ) or define( 'SCALEXPERTGROUPEDSOLUTIONS', $solutionGrouped );
	defined( 'SCALEXPERTSOLUTIONS' ) or define( 'SCALEXPERTSOLUTIONS', $financements );
	defined( 'URL_SCALEXPERT_SG' ) or define( "URL_SCALEXPERT_SG", "https://dev.scalexpert.societegenerale.com/fr/prod/" );
	defined( 'URLAPIPROD' ) or define( 'URLAPIPROD', 'https://api.scalexpert.societegenerale.com/baas/prod/' );
	defined( 'URLAPIUAT' ) or define( 'URLAPIUAT', 'https://api.scalexpert.uatc.societegenerale.com/baas/uatc/' );
	defined( 'SCALEXPERT_ENDPOINT_AUTH' ) or define( "SCALEXPERT_ENDPOINT_AUTH", "auth-server/api/v1/oauth2/token" );
	defined( 'SCALEXPERT_ENDPOINT_ELIGIBLE_SOLUTIONS' ) or define( "SCALEXPERT_ENDPOINT_ELIGIBLE_SOLUTIONS", "e-financing/api/v1/eligible-solutions" );
	defined( 'SCALEXPERT_ENDPOINT_SUBSCRIPTION' ) or define( "SCALEXPERT_ENDPOINT_SUBSCRIPTION", "e-financing/api/v1/subscriptions/" );
	defined( 'SCALEXPERT_ENDPOINT_SIMULATION' ) or define( "SCALEXPERT_ENDPOINT_SIMULATION", "e-financing/api/v1/_simulate-solutions" );
    defined( 'SCALEXPERT_ENDPOINT_CONFIRM_DELIVERY' ) or define( "SCALEXPERT_ENDPOINT_CONFIRM_DELIVERY", "e-financing/api/v1/subscriptions/{creditSubscriptionId}/_confirmDelivery" );
    defined( 'SCALEXPERT_LOWERLIMIT' ) or define( "SCALEXPERT_LOWERLIMIT", 100 );
	defined( 'SCALEXPERT_UPPERLIMIT' ) or define( "SCALEXPERT_UPPERLIMIT", 30000 );
	
	defined( 'SCALEXPERT_SHOWSIMULATION' ) or define( "SCALEXPERT_SHOWSIMULATION", TRUE );
	defined( 'SCALEXPERT_SHOWSOLUTIONS' ) or define( "SCALEXPERT_SHOWSOLUTIONS", FALSE );