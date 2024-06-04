<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[

			[
				'id'       => 'firewall_enabled',
//			'control_field' => 'firewall_enabled',
				'menu_id'  => 'geo_block_list',
				'group_id' => 'geo_block_list_general',
				'type'     => 'checkbox',
				'label'    => __("Enable Region restrictions", "really-simple-ssl"),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'      => 'geo_blocklist_white_listing_overview',
				'menu_id' => 'geo_block_list',
				'group_id' => 'geo_block_list_white_listing',
				'type'    => 'geo-ip-datatable',
				'action'  => 'rsssl_geo_white_list',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'firewall_enabled' => true,
					]
				],
				'columns' => [
					[
						'name'       => __('IP Address', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'ip_address',
						'width'      => '65%',
					],
					[
						'name'     => __('Note', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'note',
						'width'    => '20%',
					],
					[
						'name'     => __('Date', 'really-simple-ssl'),
						'sortable' => true,
						'column'   => 'create_date',
						'width'    => '18%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
						'width'    => '13%',
					],
				],
			],
			[
				'id'               => 'geo_blocklist_listing_overview',
				'menu_id'          => 'geo_block_list',
				'group_id'         => 'geo_block_list_listing',
				'type'             => 'geo-datatable',
				'action'           => 'rsssl_geo_list',
				'options'          => [
					'blocked' => __('Blocked', 'really-simple-ssl'),
					'locked'  => __('Locked-out', 'really-simple-ssl'),
					'trusted' => __('Trusted', 'really-simple-ssl'),
				],
				'disabled'         => false,
				'default'          => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'firewall_enabled' => true,
					]
				],
				'columns'          => [
					[
						'name'       => __('', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => false,
						'column'     => 'flag',
						'width'      => '5%',
					],
					[
						'name'       => __('Country', 'really-simple-ssl'),
						'sortable'   => true,
						'searchable' => true,
						'column'     => 'country_name',
						'width'      => '150px',
					],
					[
						'name'    => __('Continent', 'really-simple-ssl'),
						'sortable' => false,
						'searchable' => false,
						'column'  => 'region_name',
						'width'   => '30%',
					],
					[
						'name'     => __('Status', 'really-simple-ssl'),
						'sortable' => false,
						'column'   => 'status',
						'width'    => '20%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'action',
						'width'    => '180px',
					],
				],
			],
		]
	);
}, 200 );
