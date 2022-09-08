<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( "rsssl_wizard" ) ) {
	class rsssl_wizard{

		private static $_this;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_filter("rsssl_run_test", array($this, 'handle_lets_encrypt_request'), 10, 3);
			add_filter("rsssl_localize_script", array($this, 'localize_script'), 10, 3);
			add_action( 'rsssl_after_save_field', array( $this, 'after_save_field' ), 10, 4 );
			add_filter( 'rsssl_steps', array($this, 'maybe_add_multisite_test') );
		}

		static function this() {
			return self::$_this;
		}

		/**
         * Add some information to the javascript
		 * @param array $args
		 *
		 * @return array
		 */
        public function localize_script($args){
            $hosting_dashboard = 'other';
            if ( rsssl_is_cpanel() ) $hosting_dashboard = 'cpanel';
            if ( rsssl_is_directadmin() ) $hosting_dashboard = 'directadmin';
            if ( rsssl_is_plesk() ) $hosting_dashboard = 'plesk';
            $args['hosting_dashboard'] = $hosting_dashboard;
            return $args;
        }

		/**
         * In case of multisite, we add a step to test for subdomains
		 * @param $steps
		 *
		 * @return mixed
		 */
		public function maybe_add_multisite_test($steps){
			if ( is_multisite() ) {
				$index = array_search( 'system-status', array_column( $steps['lets-encrypt'], 'id' ) );
				$index ++;
				$steps['lets-encrypt'][ $index ]['actions'] = array_merge(
					array(
                        array(
                            'description' => __("Checking for subdomain setup...", "really-simple-ssl"),
                            'action'=> 'is_subdomain_setup',
                            'attempts' => 1,
                            'speed' => 'normal',
                        )
                    ) , $steps['lets-encrypt'][ $index ]['actions']);
			}
			return $steps;
		}

		/**
         * Switch to DNS verification
         * @param WP_REST_Request $request
		 * @return []
		 */
        public function update_verification_type($request){
	        $type = $request->get_param('id');
            $type = $type === 'dns' ? 'dns' : 'dir';
	        rsssl_update_option('verification_type', $type );
            if ($type==='dns') {
	            rsssl_progress_add('directories');
            } else {
	            rsssl_progress_add('dns-verification');
            }
	        return new RSSSL_RESPONSE(
		        'success',
		        'stop',
                ''
            );
        }

		/**
         * Skip DNS check
		 * @return RSSSL_RESPONSE
		 */
		public function skip_dns_check(){
		    if ( !rsssl_user_can_manage() ) {
			    return new RSSSL_RESPONSE(
				    'error',
				    'stop',
				    ''
			    );
            }
            update_option('rsssl_skip_dns_check', true, false);
			return new RSSSL_RESPONSE(
				'success',
				'stop',
				''
			);
        }
		/**
         * Get installation data
		 * @return RSSSL_RESPONSE
		 */
		public function installation_data(){
		    if ( !rsssl_user_can_manage() ) {
			    return new RSSSL_RESPONSE(
				    'error',
				    'stop',
				    ''
			    );
            }
			$key_file = get_option('rsssl_private_key_path');
			$cert_file = get_option('rsssl_certificate_path');
			$cabundle_file = get_option('rsssl_intermediate_path');
            $data = [
                    'generated_by_rsssl' => rsssl_generated_by_rsssl(),
	                'download_url' => rsssl_le_url.'download.php?token='.wp_create_nonce('rsssl_download_cert'),
                    'key_content' => file_exists($key_file) ? file_get_contents($key_file) : 'no data found',
                    'certificate_content' => file_exists($cert_file) ? file_get_contents($cert_file) : 'no data found',
                    'ca_bundle_content' => file_exists($cabundle_file) ? file_get_contents($cabundle_file) : 'no data found',
            ];
			return new RSSSL_RESPONSE(
				'success',
				'continue',
				'',
				$data
			);
        }

		/**
		 * Get activation data
		 * @return RSSSL_RESPONSE
		 */
		public function activation_data(){
			if ( !rsssl_user_can_manage() ) {
				return new RSSSL_RESPONSE(
					'error',
					'stop',
					''
				);
			}
			$response = RSSSL_LE()->letsencrypt_handler->certificate_status();
			$certificate_is_valid = $response->status === 'error'; //seems weird, but is correct.
			$ssl_enabled = RSSSL()->really_simple_ssl->ssl_enabled;
			$data = [
				'certificate_is_valid' => $certificate_is_valid,
				'ssl_enabled' => $ssl_enabled,
			];
			return new RSSSL_RESPONSE(
				'success',
				'continue',
				'',
				$data
			);
		}

		/**
         * Challenge directory request
         *
		 * @return RSSSL_RESPONSE
		 */
		public function skip_challenge_directory_request(){
			if ( !rsssl_user_can_manage() ) {
				return new RSSSL_RESPONSE(
					'error',
					'stop',
					''
				);
			}

            update_option('rsssl_skip_challenge_directory_request', true, false);
			return new RSSSL_RESPONSE(
				'success',
				'stop',
				''
			);
		}

		/**
		 * Reset the LE wizard
		 * @return bool[]|RSSSL_RESPONSE
		 */
		public function reset(){
			if ( !rsssl_user_can_manage() ) {
				return new RSSSL_RESPONSE(
					'success',
					'stop',
                    ''
				);
			}

            RSSSL_LE()->letsencrypt_handler->clear_order();
            rsssl_update_option('verification_type', 'dir' );
            delete_option('rsssl_skip_dns_check' );
            delete_option('rsssl_skip_challenge_directory_request' );
            delete_option('rsssl_force_plesk' );
            delete_option('rsssl_force_cpanel' );
            delete_option('rsssl_create_folders_in_root');
            delete_option('rsssl_hosting_dashboard');
            RSSSL_LE()->letsencrypt_handler->clear_keys_directory();

			return new RSSSL_RESPONSE(
				'success',
				'stop',
				''
			);
		}



		/**
         * Process a Let's Encrypt test request
         *
		 * @param array $data
		 * @param string $test
		 * @param WP_REST_Request $request
		 *
		 * @return RSSSL_RESPONSE
		 */
        public function handle_lets_encrypt_request($data, $test, $request){
	        if ( ! current_user_can('manage_security') ) {
		        return new RSSSL_RESPONSE(
			        'error',
			        'stop',
			        __( "Permission denied.", 'really-simple-ssl' )
		        );
	        }

	        switch( $test ){
                case 'reset':
	                return $this->reset();
		        case 'update_verification_type':
                    return $this->update_verification_type($request);
		        case 'skip_dns_check':
			        return $this->skip_dns_check();
		        case 'skip_challenge_directory_request':
			        return $this->skip_challenge_directory_request();
		        case 'installation_data':
			        return $this->installation_data();
                case 'activation_data':
			        return $this->activation_data();
		        case 'verify_dns':
		        case 'rsssl_php_requirement_met':
		        case 'certificate_status':
		        case 'curl_exists':
		        case 'server_software':
		        case 'alias_domain_available':
		        case 'check_domain':
		        case 'check_host':
		        case 'check_challenge_directory':
		        case 'check_key_directory':
		        case 'check_certs_directory':
		        case 'check_writing_permissions':
		        case 'challenge_directory_reachable':
		        case 'get_account':
		        case 'get_dns_token':
		        case 'terms_accepted':
		        case 'create_bundle_or_renew':
		        case 'search_ssl_installation_url':
                    return $this->get_installation_progress($data, $test, $request);
                default:
                    return new RSSSL_RESPONSE(
	                    'error',
	                    'stop',
	                    __( "Test not found.", 'really-simple-ssl' )
                    );
            }
        }

		/**
         * Run a LE test
		 * @param $data
		 * @param $function
		 * @param $request
		 *
		 * @return RSSSL_RESPONSE
		 */
		public function get_installation_progress( $data, $function, $request ){
			$id = $request->get_param('id');
			if ( ! current_user_can('manage_security') ) {
				return new RSSSL_RESPONSE(
					'error',
					'stop',
					__( "Permission denied.", 'really-simple-ssl' )
				);
			}

            if (!function_exists($function) && !method_exists(RSSSL_LE()->letsencrypt_handler, $function)) {
                return new RSSSL_RESPONSE(
                    'error',
                    'stop',
                    __( "Test not found.", 'really-simple-ssl' )
                );
            }

			rsssl_progress_add($id);
            if ( function_exists($function) ){
                $response = $function();
            } else {
                $response = RSSSL_LE()->letsencrypt_handler->$function();
            }

			return $response;
		}

		/**
		 * Handle some custom options after saving the wizard options
		 * @param string $field_id
		 * @param mixed $field_value
		 * @param mixed $prev_value
		 * @param string $type
		 */

		public function after_save_field( $field_id, $field_value, $prev_value, $type ) {
			//only run when changes have been made
			if ( $field_value === $prev_value ) {
				return;
			}

			if ( $field_id==='other_host_type'){
			    if ( isset(RSSSL_LE()->hosts->hosts[$field_value]) ){
			        $dashboard = RSSSL_LE()->hosts->hosts[$field_value]['hosting_dashboard'];
			        update_option('rsssl_hosting_dashboard', $dashboard, false);
                }
            }

			if ( $field_id === 'email_address'&& is_email($field_value) ) {
				RSSSL_LE()->letsencrypt_handler->update_account($field_value);
			}

		}

		/**
         * @deprecated
		 * @return string
		 */
		public function get_support_url()
		{
            $user_info = get_userdata(get_current_user_id());
            $email = urlencode($user_info->user_email);
            $name = urlencode($user_info->display_name);
			$verification_type = rsssl_get_option('verification_type') === 'dns' ? 'dns' : 'dir';
			$skip_dns_check = get_option('rsssl_skip_dns_check' ) ? 'Skip DNS check' : 'Do DNS check';
			$skip_directory_check = get_option('rsssl_skip_challenge_directory_request' ) ? 'Skip directory check' : 'Do directory check';
			$hosting_company = rsssl_get_other_host();
			$dashboard = 'unknown';
			if (rsssl_is_cpanel()){
			    $dashboard = 'cpanel';
			} else if(rsssl_is_plesk()){
			    $dashboard = 'plesk';
			} else if (rsssl_is_directadmin()){
			    $dashboard = 'directadmin';
			}

            $debug_log_contents = 'dashboard '.$dashboard.'--br--';
            $debug_log_contents .= 'skip dns check '.$skip_dns_check.'--br--';
            $debug_log_contents .= 'skip directory check '.$skip_directory_check.'--br--';
            $debug_log_contents .= 'verification type '.$verification_type.'--br--';
            $debug_log_contents = urlencode(strip_tags( $debug_log_contents ) );

            //Retrieve the domain
            $domain = site_url();

            $url = "https://really-simple-ssl.com/letsencrypt-support/?email=$email&customername=$name&domain=$domain&hosting_company=$hosting_company&debuglog=$debug_log_contents";

            return $url;
		}


		public function activate_ssl_buttons(){
		    ob_start();
		    wp_nonce_field('rsssl_le_nonce', 'rsssl_le_nonce'); ?>
            <?php
                $response = RSSSL_LE()->letsencrypt_handler->certificate_status();
                $certificate_is_valid = $response->status === 'error';
                $already_enabled = RSSSL()->really_simple_ssl->ssl_enabled;
    			if ($certificate_is_valid && $already_enabled){ ?>
                    <a class="button button-default" href="<?php echo esc_url(add_query_arg(array("page"=>"really-simple-security"),admin_url("options-general.php") ) );?>"><?php _e("Go to dashboard", "really-simple-ssl"); ?></a>
                <?php } else if ( $certificate_is_valid ) {?>
                    <input type="submit" class='button button-primary'
                           value="<?php _e("Go ahead, activate SSL!", "really-simple-ssl"); ?>" id="rsssl_do_activate_ssl"
                           name="rsssl_do_activate_ssl">
                <?php } else { ?>
                    <input type="submit" class='button button-default'
                           value="<?php _e("Retry", "really-simple-ssl"); ?>" id="rsssl_recheck_ssl"
                           name="rsssl_recheck_ssl">
                <?php }?>

                <?php if (!defined("rsssl_pro_version") ) { ?>
                    <a class="button button-default" href="<?php echo RSSSL()->really_simple_ssl->pro_url ?>" target="_blank"><?php _e("Get ready with PRO!", "really-simple-ssl"); ?></a>
                <?php } ?>
            <?php
            return ob_get_clean();
		}

	}


} //class closure
