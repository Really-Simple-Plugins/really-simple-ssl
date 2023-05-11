<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( "rsssl_le_restapi" ) ) {
	class rsssl_le_restapi{

		private static $_this;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_filter("rsssl_run_test", array($this, 'handle_lets_encrypt_request'), 10, 3);
			add_action( 'rsssl_after_save_field', array( $this, 'after_save_field' ), 10, 4 );
		}

		static function this() {
			return self::$_this;
		}



		/**
         * Switch to DNS verification
         * @param array $data
		 * @return []
		 */
        public function update_verification_type($data){
	        $type = $data['id'];
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
            delete_option('rsssl_create_folders_in_root');
            delete_option('rsssl_hosting_dashboard');
            RSSSL_LE()->letsencrypt_handler->clear_keys_directory();

			return new RSSSL_RESPONSE(
				'success',
				'stop',
				''
			);
		}

		public function clean_up(){
			//clean up stored pw, if requested
			RSSSL_LE()->letsencrypt_handler->cleanup_on_ssl_activation();
		}

		/**
         * Process a Let's Encrypt test request
         *
		 * @param array $response
		 * @param string $test
		 * @param WP_REST_Request $request
		 *
		 * @return RSSSL_RESPONSE|array
		 */
        public function handle_lets_encrypt_request($response, $test, $data){
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
                    return $this->update_verification_type($data);
		        case 'skip_dns_check':
			        return $this->skip_dns_check();
		        case 'skip_challenge_directory_request':
			        return $this->skip_challenge_directory_request();
		        case 'installation_data':
			        return $this->installation_data();
		        case 'is_subdomain_setup':
		        case 'verify_dns':
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
		        case 'rsssl_install_cpanel_autossl':
		        case 'rsssl_cpanel_set_txt_record':
		        case 'rsssl_install_cpanel_default':
		        case 'rsssl_cloudways_server_data':
		        case 'rsssl_cloudways_install_ssl':
		        case 'rsssl_cloudways_auto_renew':
		        case 'rsssl_install_directadmin':
		        case 'rsssl_plesk_install':
		        case 'cleanup_on_ssl_activation':
                    return $this->get_installation_progress($response, $test, $data);
                default:
                    return $response;
            }
        }

		/**
         * Run a LE test
		 * @param $response
		 * @param $function
		 * @param $data
		 *
		 * @return RSSSL_RESPONSE
		 */
		public function get_installation_progress( $response, $function, $data ){
			$id = $data['id'];
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
                } else {
				    update_option('rsssl_hosting_dashboard', false, false);
                }
            }

			if ( $field_id === 'email_address'&& is_email($field_value) ) {
				RSSSL_LE()->letsencrypt_handler->update_account($field_value);
			}

		}

	}


} //class closure
