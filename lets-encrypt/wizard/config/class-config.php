<?php
defined( 'ABSPATH' ) or die( );

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
        public $dashboard_activation_required;
        public $activated_by_default;
        public $paid_only;

        function __construct() {
	        define('RSSSL_LE_CONFIG_LOADED', true);

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
		            'free_ssl_available' => false,
		            'hosting_dashboard' => 'cloudways',
		            'api' => true,
		            'ssl_installation_link' => false,
	            ),
	            'tierpoint' => array(
            		'name' => 'TierPoint',
		            'installation_renewal_required' => true,
		            'local_ssl_generation_needed' => true,
		            'free_ssl_available' => false,
		            'hosting_dashboard' => 'cpanel',
		            'api' => true,
		            'ssl_installation_link' => false,
	            ),
	            'godaddy' => array(
		            'name' => 'GoDaddy',
		            'installation_renewal_required' => true,
		            'local_ssl_generation_needed' => true,
		            'free_ssl_available' => false,
		            'hosting_dashboard' => 'cpanel',
		            'api' => false,
		            'ssl_installation_link' => false,
	            ),
	            'godaddy_managed' => array(
		            'name' => 'GoDaddy Managed WordPress',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => 'godaddymanaged',
		            'api' => false,
		            'ssl_installation_link' => false,
	            ),
	            'kasserver' => array(
		            'name' => 'Kasserver',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => 'kasserver',
		            'api' => false,
		            'ssl_installation_link' => 'https://kas.all-inkl.com/',
	            ),
	            'argeweb' => array(
		            'name' => 'Argeweb',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => 'plesk',
		            'api' => false,
		            'ssl_installation_link' => 'https://www.argeweb.nl/argecs/',
	            ),

	            'hostgator' => array(
		            'name' => 'HostGator',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => true,
		            'hosting_dashboard' => 'cpanel:autossl',
		            'api' => true,
		            'ssl_installation_link' => 'https://{host}:2083/frontend/paper_lantern/security/tls_status/',
	            ),

	            'ionos' => array(
		            'name' => 'IONOS',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'paid_only',
		            'hosting_dashboard' => 'ionos',
		            'api' => false,
		            'ssl_installation_link' => '',
	            ),

	            'simply' => array(
		            'name' => 'Simply',
		            'installation_renewal_required' => true,
		            'local_ssl_generation_needed' => true,
		            'free_ssl_available' => false,
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://www.simply.com/en/controlpanel/sslcerts/',
	            ),
	            'siteground' => array(
		            'name' => 'SiteGround',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://tools.siteground.com/ssl',
	            ),
	            'dreamhost' => array(
		            'name' => 'Dreamhost',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://help.dreamhost.com/hc/en-us/articles/216539548-Adding-a-free-Let-s-Encrypt-certificate',
	            ),
	            'wpengine' => array(
		            'name' => 'WPEngine',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://wpengine.com/support/add-ssl-site/#letsencrypt',
	            ),
	            'ipage' => array(
		            'name' => 'iPage',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://www.ipage.com/help/article/enable-your-free-ssl-certificate',
	            ),
	            'onecom' => array(
		            'name' => 'one.com',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://help.one.com/hc/en-us/articles/360000297458-Why-is-SSL-HTTPS-not-working-on-my-site-',
	            ),
	            'wpmudev' => array(
		            'name' => 'WPMUDEV',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => false,
		            'api' => false,
		            'ssl_installation_link' => 'https://wpmudev.com',
	            ),
	            'ovh' => array(
		            'name' => 'OVH',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => 'cpanel',
		            'api' => false,
		            'ssl_installation_link' => 'https://ovh.com',
	            ),
	            'freeola' => array(
		            'name' => 'Freeola',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'paid_only',
		            'hosting_dashboard' => 'freeola',
		            'api' => false,
		            'ssl_installation_link' => '',
	            ),
	            'hostinger' => array(
		            'name' => 'Hostinger',
		            'installation_renewal_required' => true,
		            'local_ssl_generation_needed' => true,
		            'free_ssl_available' => false,
		            'hosting_dashboard' => 'hpanel',
		            'api' => false,
		            'ssl_installation_link' => 'https://hpanel.hostinger.com/hosting/{domain}advanced/ssl',
	            ),
	            'pcextreme' => array(
		            'name' => 'PCExtreme',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => 'directadmin',
		            'api' => false,
		            'ssl_installation_link' => 'https://help.pcextreme.nl/domains-ssl/hoe-vraag-ik-een-ssl-certificaat-aan-voor-mijn-domein/',
	            ),
	            'internic' => array(
		            'name' => 'Internic',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activated_by_default',
		            'hosting_dashboard' => 'internic',
		            'api' => false,
		            'ssl_installation_link' => 'https://internic.com',
	            ),
	            'aruba' => array(
		            'name' => 'Aruba',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'paid_only',
		            'hosting_dashboard' => 'aruba',
		            'api' => false,
		            'ssl_installation_link' => 'https://admin.aruba.it/PannelloAdmin/UI/Pages/ContentSection.aspx?Action=153',
	            ),
	            'namecheap' => array(
		            'name' => 'Namecheap',
		            'installation_renewal_required' => false,
		            'local_ssl_generation_needed' => false,
		            'free_ssl_available' => 'activation_required',
		            'hosting_dashboard' => 'cpanel',
		            'api' => false,
		            'ssl_installation_link' => 'https://www.namecheap.com/blog/install-free-ssls/',
	            ),

            );

	        $this->not_local_certificate_hosts = $this->filter_hosts( 'local_ssl_generation_needed', false);
	        $this->dashboard_activation_required = $this->filter_hosts( 'free_ssl_available', 'activation_required');
	        $this->activated_by_default = $this->filter_hosts( 'free_ssl_available', 'activated_by_default');
	        $this->paid_only = $this->filter_hosts( 'free_ssl_available', 'paid_only');
            $this->no_installation_renewal_needed = $this->filter_hosts( 'installation_renewal_required', false);
            $this->no_installation_renewal_needed[] = 'cpanel:autossl';

	        $this->yes_no = array(
		        'yes' => __( 'Yes', 'really-simple-ssl' ),
		        'no'  => __( 'No', 'really-simple-ssl' ),
	        );


	        ksort($this->hosts);
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
	     * @param string | bool $type
	     *
	     * @return bool
	     */

	    public function host_api_supported( $type ) {
		    $hosting_company = rsssl_get_other_host();
		    //if not listed, we assume it can.
		    if ( !$hosting_company || $hosting_company === 'none' ) {
			    return true;
		    }

		    $hosts_has_dashboard = RSSSL_LE()->config->filter_hosts( 'api', $type);
		    if ( in_array($hosting_company, $hosts_has_dashboard) ) {
			    return true;
		    } else {
			    return false;
		    }
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