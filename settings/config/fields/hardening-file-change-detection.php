<?php
defined( 'ABSPATH' ) or die();

add_filter( 'rsssl_fields', function( $fields ) {
	return array_merge( $fields,
		[
			[
				'id'       => 'file_change_detection',
				'menu_id'  => 'hardening-file-change',
				'group_id' => 'hardening-file-change-main',
				'type'     => 'checkbox',
				'label'    => __( "Enable File Change Detection", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => false,
			],
			[
				'id'       => 'file_change_exclusions',
				'menu_id'  => 'hardening-file-change',
				'group_id' => 'hardening-file-change-main',
				'type'     => 'textarea',
				'label'    => __( "Exclude files/directories", 'really-simple-ssl' ),
				'disabled' => false,
				'default'  => '',
				'condition_action'   => 'hide',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'file_change_detection' => 1,
					]
				],
			],
			[
				'id'               => 'changed-files-overview',
				'menu_id'          => 'hardening-file-change',
				'group_id'         => 'hardening-file-change-datatable',
				'type'             => 'file-change-detection',
				'action'           => 'get_changed_files',
				'label'            => "XML-RPC",
				'disabled'         => false,
				'default'          => false,
				'condition_action'   => 'hide',
				'react_conditions' => [
					'relation' => 'AND',
					[
						'file_change_detection' => 1,
					]
				],
				'multiselect_buttons' => [
					[
						'action'   => 'delete_changed_files',
						'type'     => 'delete',
						'label'   => __("Ignore", 'really-simple-ssl'),
						'className'=> 'rsssl-red',
					],
					[
						'action'   => 'exclude_from_changed_files',
						'type'     => 'exclude',
						'label'   => __("Exclude", 'really-simple-ssl'),
						'reloadFields' => true,
					],
				],
				'columns'          => [
					[
						'name'     => __( 'Changed file', 'really-simple-ssl' ),
						'sortable' => true,
						'searchable' => true,
						'column'   => 'file',
						'width'     => '40%',
					],
					[
						'name'     => __( 'Detected', 'really-simple-ssl' ),
						'sortable' => true,
						'searchable' => true,
						'column'   => 'changed',
						'width'     => '20%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'excludeButton',
						'isButton'   => true,
						'action'   => 'exclude_from_changed_files',
						'type'     => 'exclude',
						'label'   => __("Exclude", 'really-simple-ssl'),
						'reloadFields' => true,
						'className'=> 'button-primary rsssl-exclude-button',
						'width'    => '15%',
					],
					[
						'name'     => '',
						'sortable' => false,
						'column'   => 'deleteButton',
						'isButton'   => true,
						'action'   => 'delete_changed_files',
						'type'     => 'delete',
						'label'   => __("Ignore", 'really-simple-ssl'),
						'className'=> 'rsssl-red',
						'width'    => '15%',
					],

				],
			],
		]
	);
}, 200 );
