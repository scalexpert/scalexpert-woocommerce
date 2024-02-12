<?php
	
	
	$financements = [
		'SCFRSP-4XTS' => __( "Paiement en 4X (option sans frais)", "woo-scalexpert" ),
		'SCFRSP-4XPS' => __( "Paiement en 4X (option avec frais)", "woo-scalexpert" ),
		'SCFRLT-TXPS' => __( 'Crédit long (sans frais)', "woo-scalexpert" ),
		'SCFRLT-TXNO' => __( 'Crédit long (avec commission)', "woo-scalexpert" ),
		'SCFRSP-3XPS' => __( 'Paiement en 3X (option avec frais)', "woo-scalexpert" ),
		'SCFRSP-3XTS' => __( 'Paiement en 3X (option sans frais)', "woo-scalexpert" ),
		'SCDELT-DXTS' => __( 'Crédit long (sans frais)', "woo-scalexpert" ),
		'SCDELT-DXCO' => __( 'Crédit long (avec commission)', "woo-scalexpert" ),
	];
	
	$solutionGrouped = [
		'financials' => [
			[ 'SCFRSP-3XTS', 'SCFRSP-3XPS' ],
			[ 'SCFRSP-4XTS', 'SCFRSP-4XPS' ],
			//['SCDELT-DXTS',],
			//['SCDELT-DXCO',],
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
	
	
	defined( 'SCALEXPERTCRONSCHEDULES' ) or define( 'SCALEXPERTCRONSCHEDULES', $cronSchedules );
	defined( 'SCALEXPERTGROUPEDSOLUTIONS' ) or define( 'SCALEXPERTGROUPEDSOLUTIONS', $solutionGrouped );
	defined( 'SCALEXPERTSOLUTIONS' ) or define( 'SCALEXPERTSOLUTIONS', $financements );
	defined( 'URL_SCALEXPERT_SG' ) or define( "URL_SCALEXPERT_SG", "https://dev.scalexpert.societegenerale.com/fr/prod/" );
	defined( 'URLAPIPROD' ) or define( 'URLAPIPROD', 'https://api.scalexpert.societegenerale.com/baas/prod/' );
	defined( 'URLAPIUAT' ) or define( 'URLAPIUAT', 'https://api.scalexpert.hml.societegenerale.com/baas/uat/' );
	defined( 'SCALEXPERT_ENDPOINT_AUTH' ) or define( "SCALEXPERT_ENDPOINT_AUTH", "auth-server/api/v1/oauth2/token" );
	defined( 'SCALEXPERT_ENDPOINT_ELIGIBLE_SOLUTIONS' ) or define( "SCALEXPERT_ENDPOINT_ELIGIBLE_SOLUTIONS", "e-financing/api/v1/eligible-solutions" );
	defined( 'SCALEXPERT_ENDPOINT_SUBSCRIPTION' ) or define( "SCALEXPERT_ENDPOINT_SUBSCRIPTION", "e-financing/api/v1/subscriptions/" );
	defined( 'SCALEXPERT_TRANSIENTS' ) or define( "SCALEXPERT_TRANSIENTS", 86400 );
	