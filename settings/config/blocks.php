<?php
defined( 'ABSPATH' ) or die();


function rsssl_blocks() {
	if ( ! rsssl_user_can_manage() ) {
		return [];
	}
	$blocks = [
		[
			'id'       => 'progress',
			'header'  =>  'ProgressHeader',
			'content'  => 'ProgressBlock',
			'footer'   => 'ProgressFooter',
			'class'    => ' rsssl-column-2',
		],
		[
			'id'       => 'ssllabs',
			'header'   => 'SslLabsHeader',
			'content'  => 'SslLabs',
			'footer'   => 'SslLabsFooter',
			'class'    => 'border-to-border',
		],
		[
			'id'       => 'wpvul',
			'header'   => 'VulnerabilitiesHeader',
			'content'  => 'WPVul',
			'footer'   => 'WPVulFooter',
			'class'    => 'border-to-border',
		],
		[
			'id'       => 'tips_tricks',
			'title'    => __( "Tips & Tricks", 'really-simple-ssl' ),
			'content'  => 'TipsTricks',
			'footer'   => 'TipsTricksFooter',
			'class'    => ' rsssl-column-2',
		],
		[
			'id'       => 'other-plugins',
			'header'   => 'OtherPluginsHeader',
			'content'  => 'OtherPlugins',
			'class'    => ' rsssl-column-2 no-border no-background',
		],
	];

	return apply_filters( 'rsssl_blocks', $blocks );
}