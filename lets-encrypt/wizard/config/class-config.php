<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "rsssl_config" ) ) {

    class rsssl_config {
        private static $_this;
        public $fields = array();
        public $sections;
        public $pages;
        public $warning_types;
        public $yes_no;
        public $supported_hosts;
        public $not_local_certificate_hosts;
        public $no_renewal_needed;

        function __construct() {
            if ( isset( self::$_this ) ) {
                wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
                    get_class( $this ) ) );
            }

            self::$_this = $this;


            //common options type
            $this->yes_no = array(
                'yes' => __( 'Yes', 'really-simple-ssl' ),
                'no'  => __( 'No', 'really-simple-ssl' ),
            );

            $this->no_renewal_needed = array(
                'cloudways',
	            'cpanel:autossl',
            );

            $this->supported_hosts = array(
            	'none' => __('Not listed, proceed with installation', 'really-simple-ssl'),
            	'cloudways' => 'CloudWays',
            );

            $this->not_local_certificate_hosts = array(
            	'cloudways'
            );


            /* config files */
            require_once( rsssl_le_path . 'wizard/config/steps.php' );
            require_once( rsssl_le_path . 'wizard/config/questions.php' );

            /**
             * Preload fields with a filter, to allow for overriding types
             */
            add_action( 'plugins_loaded', array( $this, 'preload_init' ), 10 );

            /**
             * The integrations are loaded with priority 10
             * Because we want to initialize after that, we use 15 here
             */
            add_action( 'plugins_loaded', array( $this, 'init' ), 15 );
        }

        static function this() {
            return self::$_this;
        }


        public function get_section_by_id( $id ) {

            $steps = $this->steps['lets-encrypt'];
            foreach ( $steps as $step ) {
                if ( ! isset( $step['sections'] ) ) {
                    continue;
                }
                $sections = $step['sections'];

                //because the step arrays start with one instead of 0, we increase with one
                return array_search( $id, array_column( $sections, 'id' ) ) + 1;
            }

        }

        public function get_step_by_id( $id ) {
            $steps = $this->steps['lets-encrypt'];

            //because the step arrays start with one instead of 0, we increase with one
            return array_search( $id, array_column( $steps, 'id' ) ) + 1;
        }


        public function fields(
            $page = false, $step = false, $section = false,
            $get_by_fieldname = false
        ) {

            $output = array();
            $fields = $this->fields;
            if ( $page ) {
                $fields = rsssl_array_filter_multidimensional( $this->fields,
                    'source', $page );
            }

            foreach ( $fields as $fieldname => $field ) {
                if ( $get_by_fieldname && $fieldname !== $get_by_fieldname ) {
                    continue;
                }

                if ( $step ) {
                    if ( $section && isset( $field['section'] ) ) {
                        if ( ( $field['step'] == $step
                                || ( is_array( $field['step'] )
                                    && in_array( $step, $field['step'] ) ) )
                            && ( $field['section'] == $section )
                        ) {
                            $output[ $fieldname ] = $field;
                        }
                    } else {
                        if ( ( $field['step'] == $step )
                            || ( is_array( $field['step'] )
                                && in_array( $step, $field['step'] ) )
                        ) {
                            $output[ $fieldname ] = $field;
                        }
                    }
                }
                if ( ! $step ) {
                    $output[ $fieldname ] = $field;
                }

            }

            return $output;
        }

        public function has_sections( $page, $step ) {
            if ( isset( $this->steps[ $page ][ $step ]["sections"] ) ) {
                return true;
            }

            return false;
        }

        public function preload_init(){
            $this->fields = apply_filters( 'rsssl_fields_load_types', $this->fields );
        }

        public function init() {
	        $this->fields = apply_filters( 'rsssl_fields', $this->fields );
        }
    }



} //class closure