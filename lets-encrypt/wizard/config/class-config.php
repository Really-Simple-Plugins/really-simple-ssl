<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "rsssl_config" ) ) {

    class rsssl_config {
        private static $_this;
        public $fields = array();
        public $sections;
        public $pages;
        public $steps;
        public $hosts;
        public $warning_types;
        public $yes_no;
        public $supported_hosts;
        public $not_local_certificate_hosts;
        public $no_installation_renewal_needed;

        function __construct() {
            if ( isset( self::$_this ) ) {
                wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
                    get_class( $this ) ) );
            }

            self::$_this = $this;

	        /**
	         * Plesk requires local SSL generation, and installation renewal.
	         * Cpanel default requires local SSL generation, and installation renewal.
	         * Cpanel autossl: no local ssl generation, no renewal
	         */

            $this->hosts = array(
            	'cloudways' => array(
            		'name' => 'CloudWays',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'cpanel:autossl' => false,
		            'cpanel:default' => false,
		            'ssl_installation_link' => '',
	            ),
	            'hostgator' => array(
		            'name' => 'HostGator',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'cpanel:autossl' => true,
		            'cpanel:default' => false,
		            'ssl_installation_link' => 'https://{host}:2083/frontend/paper_lantern/security/tls_status/',
	            ),
	            'simply' => array(
		            'name' => 'Simply',
		            'installation_renewal_required' => true,
		            'local_ssl_generation_needed' => true,
		            'cpanel:autossl' => false,
		            'cpanel:default' => false,
		            'ssl_installation_link' => 'https://www.simply.com/en/controlpanel/sslcerts/',
	            ),
            );

	        $this->not_local_certificate_hosts = $this->filter_hosts( 'local_ssl_generation_needed', false);
            $this->no_installation_renewal_needed = $this->filter_hosts( 'installation_renewal_required', false);
            $this->no_installation_renewal_needed[] = 'cpanel:autossl';

	        $this->yes_no = array(
		        'yes' => __( 'Yes', 'really-simple-ssl' ),
		        'no'  => __( 'No', 'really-simple-ssl' ),
	        );

	        $this->supported_hosts = array(
            	'none' => __('I don\'t know, or not listed, proceed with installation', 'really-simple-ssl'),
            );
	        $this->supported_hosts = $this->supported_hosts + wp_list_pluck($this->hosts, 'name');

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


	    /**
	     * @param array $array
	     * @param mixed $filter_value
	     * @param mixed $filter_key
	     *
	     * @return array
	     */
        public function filter_hosts( $filter_key, $filter_value){
	        return array_keys(array_filter($this->hosts, function ($var) use ($filter_value, $filter_key) {
		        return ($var[$filter_key] == $filter_value);
	        }) );
        }



	    /**
	     * Check if a host has a specific capability
	     * @param string $function
	     *
	     * @return bool
	     */
        public function current_host_can( $function ) {
	        $hosting_company = rsssl_get_other_host();
	        //if not listed, we assume it can.
			if ( !$hosting_company || $hosting_company === 'none' ) {
				return true;
			}
	        //we check only for hosts WITHOUT this capability
	        //by default we assume they can.
	        $hosts_without_function = RSSSL_LE()->config->filter_hosts( $function, false);
	        if ( in_array($hosting_company, $hosts_without_function) ) {
	        	return false;
	        } else {
	        	return true;
	        }
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
            $this->steps = apply_filters( 'rsssl_steps', $this->steps );
        }

        public function init() {
	        $this->fields = apply_filters( 'rsssl_fields', $this->fields );
        }
    }



} //class closure