<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "rsssl_wizard" ) ) {
	class rsssl_wizard{

		private static $_this;
		public $position;
		public $total_steps = false;
		public $last_section;
		public $page_url;
		public $percentage_complete = false;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_filter("rsssl_run_test", array($this, 'handle_lets_encrypt_request'), 10, 3);

			add_action( 'rsssl_after_save_field', array( $this, 'after_save_field' ), 10, 4 );
			add_action( 'plugins_loaded', array( $this, 'catch_settings_switches' ), 10 );
			//add_filter( 'rsssl_fields_load_types', array( $this, 'maybe_drop_directories_step' )  );
			add_filter( 'rsssl_steps', array($this, 'adjust_for_dns_actions') );
			add_filter( 'rsssl_steps', array($this, 'maybe_add_multisite_test') );
		}

		static function this() {
			return self::$_this;
		}

		/**
         * Change the steps in the generation page if DNS verification is enabled
		 * @param $steps
		 *
		 * @return mixed
		 */
		public function adjust_for_dns_actions($steps){
			$use_dns = rsssl_dns_verification_required();
            if ($use_dns) {
	            $index_directories = array_search( 'directories', array_column( $steps['lets-encrypt'], 'id' ) );
	            $index_directories ++;
	            $challenge_key = array_search( 'check_challenge_directory', array_column( $steps['lets-encrypt'][ $index_directories ]['actions'], 'action' ) );
	            $challenge_reachable_key = array_search( 'challenge_directory_reachable', array_column( $steps['lets-encrypt'][ $index_directories ]['actions'], 'action' ) );
	            unset( $steps['lets-encrypt'][ $index_directories ]['actions'][$challenge_key] );
	            unset( $steps['lets-encrypt'][ $index_directories ]['actions'][$challenge_reachable_key] );

	            $index = array_search( 'generation', array_column( $steps['lets-encrypt'], 'id' ) );
	            $index ++;
                $steps['lets-encrypt'][ $index ]['actions'] = array (
                     array(
                        'description' => __("Verifying DNS records...", "really-simple-ssl"),
                        'action'=> 'verify_dns',
                        'attempts' => 2,
                        'speed' => 'slow',
                    ),
                    array(
                        'description' => __("Generating SSL certificate...", "really-simple-ssl"),
                        'action'=> 'create_bundle_or_renew',
                        'attempts' => 4,
                        'speed' => 'slow',
                    )
                );
            }
			return $steps;
		}

		/**
         * In case of multisite, we add a step to test for subdomains
		 * @param $steps
		 *
		 * @return mixed
		 */
		public function maybe_add_multisite_test($steps){
			if (is_multisite() ) {
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
		 * @return []
		 */
        public function switch_to_dns(){
            x_log("switch to dns");
	        rsssl_update_option('verification_type', 'DNS');
	        rsssl_progress_add('directories');
	        return array(
		        'success' => true,
	        );
        }


		public function catch_settings_switches(){
		    if ( !rsssl_user_can_manage() ) {
		        return;
            }

			/*
			 * reset all option
             */
			if (isset($_GET['reset-letsencrypt'])) {
			    RSSSL_LE()->letsencrypt_handler->clear_order();
				rsssl_update_option('verification_type', false );
				delete_option('rsssl_skip_dns_check' );
				delete_option('rsssl_skip_challenge_directory_request' );
				delete_option('rsssl_force_plesk' );
				delete_option('rsssl_force_cpanel' );
				delete_option('rsssl_create_folders_in_root');
				delete_option('rsssl_hosting_dashboard');
				wp_redirect(rsssl_letsencrypt_wizard_url().'&step=1');
				RSSSL_LE()->letsencrypt_handler->clear_keys_directory();
				exit;
			}

		    if (isset($_POST['rsssl-switch-to-directory'])) {
			    rsssl_update_option('verification_type', false );
            }

		    if (isset($_POST['rsssl-skip-dns-check'])) {
			    update_option('rsssl_skip_dns_check', true, false);
            }

		    if (isset($_POST['rsssl-skip-challenge-directory-request'])) {
			    update_option('rsssl_skip_challenge_directory_request', true, false);
            }

		    if (isset($_POST['rsssl-force-plesk'])) {
			    update_option('rsssl_force_plesk', true, false);
            }

			if (isset($_POST['rsssl-force-cpanel'])) {
				update_option('rsssl_force_cpanel', true, false);
			}

        }
		/**
		 *
		 * @param $step
		 */

		public function installation_progress(){

			?>
			<script>
                jQuery(document).ready(function ($) {
                    'use strict';
                    var progress = 0;
                    var stored_actions = ['<?php echo implode( "','",$actions) ?>'];
                    var stored_attempts = ['<?php echo implode( "','",$attempts) ?>'];
                    var stored_descriptions = ['<?php echo implode( "','",$descriptions) ?>'];
                    var actions = stored_actions;//enabled us to reset
                    var descriptions = stored_descriptions;//enabled us to reset
                    var progress_step = Math.ceil(100/actions.length);
                    var attempt_string = '<?php _e("Attempt %s.", "really-simple-ssl")?>';
                    var startTime, endTime;
                    var actual_attempts_count = 1;
                    var previous_progress = 0;
                    $('.rsssl_letsencrypt_container').removeClass('rsssl-hidden');
                    rsssl_process_installation_step();

                    function rsssl_process_installation_step() {


                        $.ajax({
                            type: "GET",
                            url: rsssl_wizard.admin_url,
                            dataType: 'json',
                            data: ({
                                action: 'rsssl_installation_progress',
                                function: current_action,
                            }),
                            success: function (response) {


                                if (response.action === 'finalize' ) {
                                    rsssl_maybe_show_elements(current_action, response.status);

                                    //do not remove current action
                                    //remove remaining list items.
                                    for (var action in actions) {
                                        if (actions.hasOwnProperty(action)) {
                                            if (current_action !== actions[action]) $('.rsssl_action_'+actions[action]).hide();
                                        }
                                    }
                                    //clear all arrays
                                    actions.length = 0;
                                    attempts.length = 0;
                                    descriptions.length = 0;
                                    console.log("action is finalize");
                                    $('.rsssl-next').prop('disabled', false);
                                    clearInterval(window.rsssl_interval);
                                    window.rsssl_interval = setInterval(function() {
                                        progress +=5;
                                        rsssl_set_progress(msg);
                                    }, 100 );
                                } else if (response.action === 'continue' || response.action === 'skip' ) {
                                    rsssl_maybe_show_elements(current_action, response.status);
                                    rsssl_set_status(response.status);
                                    //skip:  drop previous completely, skip to next.
                                    if (response.action === 'skip') {
                                        $('.rsssl_action_'+current_action).hide();
                                    }
                                    actions.shift();
                                    attempts.shift();
                                    descriptions.shift();
                                    //new action, so reset the attempts count
                                    actual_attempts_count = 1;
                                    progress = 100 - (progress_step * actions.length);
                                    //store last successful progress
                                    previous_progress = progress;
                                    rsssl_set_progress(100);
                                    if ( actions.length == 0 ) {
                                        rsssl_stop_progress(response.status);
                                        $('.rsssl-next').prop('disabled', false);
                                    } else {
                                        rsssl_process_installation_step();
                                    }

                                } else if (response.action === 'retry' ) {
                                    if ( actual_attempts_count >= max_attempts ) {
                                        rsssl_maybe_show_elements(current_action, response.status);
                                        progress = 100;
                                        rsssl_stop_progress(response.status);
                                    } else {
                                        actual_attempts_count++;
                                        actions = stored_actions;
                                        descriptions = stored_descriptions;
                                        attempts = stored_attempts;
                                        clearInterval(window.rsssl_interval);
                                        window.rsssl_interval = setInterval(function() {
                                            progress += 10;
                                            rsssl_set_progress(msg, true);
                                        }, 100 );
                                    }
                                } else if (response.action === 'stop'){
                                    rsssl_maybe_show_elements(current_action, response.status);
                                    rsssl_set_status(response.status);

                                    actions.shift();
                                    for (var action in actions) {
                                        if (actions.hasOwnProperty(action)) {
                                            var container = $('.rsssl_action_'+actions[action]);
                                            container.html(container.html() );
                                        }
                                    }
                                    progress = 100;
                                    rsssl_stop_progress(response.status);
                                } else {
                                    console.log("response.action not found ".response.action);
                                }
                            },
                            error: function(response) {
                                console.log("error");
                                console.log(response);
                                rsssl_set_status('error');
                                $('.rsssl-progress-container ul li:first-of-type').html(response.responseText);
                                rsssl_stop_progress('error');
                            }
                        });
                    }

                    function rsssl_set_status(status){
                        if (status)
                            if ($('.rsssl-'+status).length) {
                                $('.rsssl-'+status).removeClass('rsssl-hidden');
                            }
                    }

                    function rsssl_maybe_show_elements(action, status){
                        $('.rsssl-show-on-'+status+'.rsssl-'+action).removeClass('rsssl-hidden');
                        $('.rsssl-show-on-'+status+'.rsssl-general').removeClass('rsssl-hidden');
                    }

                    function rsssl_sleep(milliseconds) {
                        const date = Date.now();
                        let currentDate = null;
                        do {
                            currentDate = Date.now();
                        } while (currentDate - date < milliseconds);
                    }

                    function rsssl_stop_progress( status ){
                        var bar = $('.rsssl-installation-progress');
                        bar.css('width', '100%');
                        bar.addClass('rsssl-'+status);
                        clearInterval(window.rsssl_interval);
                    }

                    function rsssl_elapsed_time() {
                        endTime = new Date();
                        var timeDiff = endTime - startTime; //in ms
                        return Math.round(timeDiff);
                    }

                    function rsssl_set_progress(msg , restart_on_100){
                        if ( progress>=100 ) progress=100;
                        $('.rsssl-installation-progress').css('width',progress + '%');

                        if ( progress == 100 ) {
                            clearInterval(window.rsssl_interval);
                            if (typeof restart_on_100 !=='undefined' && restart_on_100){
                                progress = previous_progress;
                                rsssl_process_installation_step();
                            }
                        }
                    }
                });
			</script>

            <div class="field-group">
                <div class="rsssl-field">
                    <div class="rsssl-section">
                        <div class="rsssl_letsencrypt_container field-group rsssl-hidden">
                            <div class="rsssl-field">
                                <div class=" rsssl-wizard-progress-bar">
                                    <div class="rsssl-wizard-progress-bar-value rsssl-installation-progress" style="width:0"></div>
                                </div>
                            </div>
                        </div>
                        <div class="rsssl_letsencrypt_container rsssl-progress-container field-group rsssl-hidden">
                            <ul>
                                <?php foreach ($action_list as $action){?>
                                    <li class="rsssl_action_<?php echo $action['action']?>">
                                        <?php echo $action['description'] ?>
                                    </li>
                                <?php } ?>
                            </ul>

                        </div>
                    </div>
                </div>
                <div class="rsssl-help-warning-wrap">
                </div>
            </div>
			<?php
		}

        public function handle_lets_encrypt_request($data, $test, $request){
	        if ( ! current_user_can('manage_security') ) {
		        $error = true;
	        }
	        switch($test){
                case 'switch_to_dns':
                    return $this->switch_to_dns();
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
                    return $this->get_installation_progress($data, $test, $request);
            }
        }

		/**
         * Run a LE test
		 * @param $data
		 * @param $function
		 * @param $request
		 *
		 * @return array
		 */
		public function get_installation_progress( $data, $function, $request ){
			$id = $request->get_param('id');
			$error   = false;
			$action = '';
			$message = '';
			$output = '';
			$status = 'none';
			if ( ! current_user_can('manage_security') ) {
				$error = true;
			}

			rsssl_progress_add($id);

			if ( !$error ) {
				if (!function_exists($function) && !method_exists(RSSSL_LE()->letsencrypt_handler, $function)) {
					$error = true;
				}
			}

			if ( !$error ) {
				if ( function_exists($function) ){
					$response = $function();
				} else {
					$response = RSSSL_LE()->letsencrypt_handler->$function();
				}

				$message = $response->message;
				$action = $response->action;
				$status = $response->status;
				$output = $response->output;
			}
			return array(
				'success' => ! $error,
				'message' => $message,
				'action' => $action,
				'status' => $status,
				'output' => $output,
			);
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
			$verification_type = rsssl_get_option('verification_type') === 'DNS' ? 'DNS' : 'DIR';
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
