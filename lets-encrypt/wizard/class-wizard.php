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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'show_tab_letsencrypt', array($this, 'wizard') );
			add_action( 'rsssl_le_installation_step', array( $this, 'installation_progress' ), 10 );
			add_action( 'wp_ajax_rsssl_installation_progress', array($this, 'get_installation_progress'));
			add_action( 'rsssl_after_save_lets-encrypt_option', array( $this, 'after_save_wizard_option' ), 10, 4 );
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



		public function catch_settings_switches(){
		    if ( !rsssl_user_can_manage() ) {
		        return;
            }

			/*
			 * reset all option
             */
			if (isset($_GET['reset-letsencrypt'])) {
			    RSSSL_LE()->letsencrypt_handler->clear_order();
				delete_option('rsssl_verification_type');
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

		    if (isset($_POST['rsssl-switch-to-dns'])) {
			    update_option('rsssl_verification_type', 'DNS');
			    $step = $this->step();
			    rsssl_progress_add('directories');
			    //if we're in step directories, skip to DNS step
			    if ( $step == 3) {
				    wp_redirect(rsssl_letsencrypt_wizard_url().'&step=4');
				    exit;
			    }
            }

		    if (isset($_POST['rsssl-switch-to-directory'])) {
			    delete_option('rsssl_verification_type' );
            }

		    if (isset($_POST['rsssl-skip-dns-check'])) {
			    update_option('rsssl_skip_dns_check', true);
            }

		    if (isset($_POST['rsssl-skip-challenge-directory-request'])) {
			    update_option('rsssl_skip_challenge_directory_request', true);
            }

		    if (isset($_POST['rsssl-force-plesk'])) {
			    update_option('rsssl_force_plesk', true);
            }

			if (isset($_POST['rsssl-force-cpanel'])) {
				update_option('rsssl_force_cpanel', true);
			}

        }
		/**
		 *
		 * @param $step
		 */

		public function installation_progress(){
			$step = $this->calculate_next('step');

			if (empty($step)) return;

			$action_list = RSSSL_LE()->config->steps['lets-encrypt'][$step]['actions'];
			if (count($action_list)==0) return;
			$actions = array_column($action_list, 'action');
			$attempts = array_column($action_list, 'attempts');
			$descriptions = array_column($action_list, 'description');
			$speed = array_column($action_list, 'speed');
			?>
			<script>
                jQuery(document).ready(function ($) {
                    'use strict';
                    var progress = 0;
                    var stored_actions = ['<?php echo implode( "','",$actions) ?>'];
                    var stored_attempts = ['<?php echo implode( "','",$attempts) ?>'];
                    var stored_descriptions = ['<?php echo implode( "','",$descriptions) ?>'];
                    var actions = stored_actions;//enabled us to reset
                    var attempts = stored_attempts;//enabled us to reset
                    var descriptions = stored_descriptions;//enabled us to reset
                    var progress_step = Math.ceil(100/actions.length);
                    var attempt_string = '<?php _e("Attempt %s.", "really-simple-ssl")?>';
                    var startTime, endTime;
                    var actual_attempts_count = 1;
                    var previous_progress = 0;
                    $('.rsssl_letsencrypt_container').removeClass('rsssl-hidden');
                    rsssl_process_installation_step();

                    function rsssl_process_installation_step() {
                        //get next action to process
                        console.log("remaining actions");
                        console.log(actions);
                        var current_action = actions[0];
                        var max_attempts = attempts[0];
                        //set up a counter to slowly increment the progress value until we get a response.
                        clearInterval(window.rsssl_interval);
                        window.rsssl_interval = setInterval(function () {
                            progress += 0.2;
                            if (progress >= 100) {
                                progress = previous_progress;
                            }
                            rsssl_set_progress();
                        }, 100);

                        rsssl_start();
                        $.ajax({
                            type: "GET",
                            url: rsssl_wizard.admin_url,
                            dataType: 'json',
                            data: ({
                                action: 'rsssl_installation_progress',
                                function: current_action,
                            }),
                            success: function (response) {
                                var elapsedTime = rsssl_elapsed_time();
                                if (elapsedTime<1000) {
                                    rsssl_sleep(1000-elapsedTime);
                                }

                                var msg = response.message;
                                if (actual_attempts_count>1) {
                                    msg = attempt_string.replace('%s', actual_attempts_count)+' '+msg;
                                }
                                var current_action_container = $('.rsssl_action_'+current_action);
                                current_action_container.html(msg);
                                current_action_container.addClass('rsssl-'+response.status);
                                var event = new CustomEvent('rsssl_le_response', { detail: response });
                                document.dispatchEvent(event);
                                if (response.action === 'finalize' ) {
                                    rsssl_maybe_show_elements(current_action, response.status);
                                    rsssl_set_status(response.status);
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

                    function rsssl_start() {
                        startTime = new Date();
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
                <div class="rsssl-help-warning-wrap"><?php
                    if ($this->step() === 1) {
                        rsssl_sidebar_notice($this->support_form());
                    } ?>
                </div>
            </div>
			<?php
		}


		public function support_form(){
		    $url = $this->get_support_url();
			ob_start();?>
			<h2><?php _e("Before you begin!","really-simple-ssl") ?></h2>
            <p><?php _e("During the SSL generation process you might discover you want to give some feedback about your experiences or need some help. If so, keep our form ready and try to be as complete and precise as possible so we can assist as fast as possible.","really-simple-ssl")?></p>
            <?php wp_nonce_field('rsssl_save', 'rsssl_le_nonce') ?>
            <a target="_blank" href="<?php echo $url?>" type="submit" class="button button-default rsssl-priority" name="rsssl-letsencrypt-support"><?php _e("Support","really-simple-ssl")?></a>
			<?php
			return ob_get_clean();
		}


		public function get_installation_progress(){
			$error   = false;
			$action = '';
			$message = '';
			$output = '';
			$status = 'none';
			if ( ! is_user_logged_in() ) {
				$error = true;
			}

			if ( !isset($_GET['function']) ) {
				$error = true;
			}

			if ( !$error ) {
				$function = sanitize_title($_GET['function']);
			}

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
			$out = array(
				'success' => ! $error,
				'message' => $message,
				'action' => $action,
				'status' => $status,
				'output' => $output,
			);

			header( "Content-Type: application/json" );
			echo json_encode( $out );
			exit;
		}


		/**
		 * Initialize a page in the wizard
		 * @param $page
		 */
		public function initialize( $page ) {
			$this->last_section = $this->last_section( $page, $this->step() );
			$this->page_url     = rsssl_letsencrypt_wizard_url();
		}

		/**
		 * Handle some custom options after saving the wizard options
		 * @param string $fieldname
		 * @param mixed $fieldvalue
		 * @param mixed $prev_value
		 * @param string $type
		 */

		public function after_save_wizard_option( $fieldname, $fieldvalue, $prev_value, $type ) {
			//only run when changes have been made
			if ( $fieldvalue === $prev_value ) {
				return;
			}

			if ( $fieldname==='other_host_type'){
			    if ( isset(RSSSL_LE()->config->hosts[$fieldvalue]) ){
			        $dashboard = RSSSL_LE()->config->hosts[$fieldvalue]['hosting_dashboard'];
			        update_option('rsssl_hosting_dashboard', $dashboard);
                }
            }

			if ( $fieldname === 'email_address'&& is_email($fieldvalue) ) {
				RSSSL_LE()->letsencrypt_handler->update_account($fieldvalue);
			}
		}

		/**
		 * Get the next step with fields in it
		 * @param string $page
		 * @param int $step
		 *
		 * @return int
		 */

		public function get_next_not_empty_step( $page, $step ) {
		    if ( ! RSSSL_LE()->field->step_has_fields( $page, $step ) ) {
				if ( $step >= $this->total_steps( $page ) ) {
					return $step;
				}

				$step ++;
				$step = $this->get_next_not_empty_step( $page, $step );
			}

			return $step;
		}

		/**
		 * Get the next section which is not empty
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return int|bool
		 */
		public function get_next_not_empty_section( $page, $step, $section ) {
			if ( ! RSSSL_LE()->field->step_has_fields( $page, $step, $section ) ) {
				//some keys are missing, so we need to count the actual number of keys.
				if ( isset( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'] ) ) {
					$n = array_keys( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'] ); //<---- Grab all the keys of your actual array and put in another array
					$count = array_search( $section, $n ); //<--- Returns the position of the offset from this array using search

					//this is the actual list up to section key.
					$new_arr = array_slice( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'], 0, $count + 1, true );//<--- Slice it with the 0 index as start and position+1 as the length parameter.
					$section_count = count( $new_arr ) + 1;
				} else {
					$section_count = $section + 1;
				}

				$section ++;

				if ( $section_count > $this->total_sections( $page, $step ) ) {
					return false;
				}

				$section = $this->get_next_not_empty_section( $page, $step, $section );
			}

			return $section;
		}

		/**
		 * Get previous step which is not empty
		 *
		 * @param string $page
		 * @param int $step
		 *
		 * @return int
		 */
		public function get_previous_not_empty_step( $page, $step ) {
			if ( ! RSSSL_LE()->field->step_has_fields( $page, $step ) ) {
				if ( $step <= 1 ) {
					return $step;
				}
				$step --;
				$step = $this->get_previous_not_empty_step( $page, $step );
			}
			return $step;
		}

		/**
		 * Get previous section which is not empty
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return false|int
		 */
		public function get_previous_not_empty_section( $page, $step, $section
		) {

			if ( ! RSSSL_LE()->field->step_has_fields( $page, $step,
				$section )
			) {
				$section --;
				if ( $section < 1 ) {
					return false;
				}
				$section = $this->get_previous_not_empty_section( $page, $step,
					$section );
			}

			return $section;
		}

		/**
		 * Lock the wizard for further use while it's being edited by the current user.
		 *
		 *
		 * */

		public function lock_wizard() {
			$user_id = get_current_user_id();
			set_transient( 'rsssl_wizard_locked_by_user', $user_id, apply_filters( "rsssl_wizard_lock_time", 2 * MINUTE_IN_SECONDS ) );
		}


		/**
		 * Check if the wizard is locked by another user
		 *
		 *
		 * */

		public function wizard_is_locked() {
			$user_id      = get_current_user_id();
			$lock_user_id = $this->get_lock_user();
			if ( $lock_user_id && $lock_user_id != $user_id ) {
				return true;
			}

			return false;
		}

		/**
		 * Get user which is locking the wizard
		 * @return false|int
		 */
		public function get_lock_user() {
			return get_transient( 'rsssl_wizard_locked_by_user' );
		}

		/**
		 * Render wizard
		 * @param string $page
		 * @param string $wizard_title
		 */
		public function wizard( )
		{
			if (!rsssl_user_can_manage()) {
				return;
			}
			$page = 'lets-encrypt';

			if ($this->wizard_is_locked()) {
				$user_id = $this->get_lock_user();
				$user = get_user_by("id", $user_id);
				$lock_time = apply_filters("rsssl_wizard_lock_time", 2 * MINUTE_IN_SECONDS) / 60;

				rsssl_notice(sprintf(__("The wizard is currently being edited by %s",
						'really-simple-ssl'), $user->user_nicename) . '<br>'
				             . sprintf(__("If this user stops editing, the lock will expire after %s minutes.",
						'really-simple-ssl'), $lock_time), 'warning');

				return;
			}
			//lock the wizard for other users.
			$this->lock_wizard();
			$this->initialize($page);

            $step = $this->calculate_next( 'step');
            $section = $this->calculate_next('section');

			$menu = $this->wizard_menu( $page, '', $step, $section );
			$content = $this->wizard_content($page, $step, $section );

			$args = array(
				'page' => 'lets-encrypt',
				'content' => $menu.$content,
			);
			$html = RSSSL()->really_simple_ssl->get_template('admin_wrap.php', $path = rsssl_le_wizard_path, $args );
			echo '<div class="wrap">'.$html.'</div>';

		}


		public function calculate_next( $type ){
			$step = $this->step();
			$section = $this->section();
			$page = 'lets-encrypt';
			if ($this->section_is_empty($page, $step, $section) || (isset($_POST['rsssl-next']) ) ) {

				if (RSSSL_LE()->config->has_sections($page, $step)
				    && ($section < $this->last_section)
				) {
					$section++;
				} else {
					$step++;
					$section = $this->first_section($page, $step);
				}
				$step = $this->get_next_not_empty_step($page, $step);
				$section = $this->get_next_not_empty_section($page, $step, $section);
				//if the last section is also empty, it will return false, so we need to skip the step too.
				if (!$section) {
					$section = 1;
					$step++;
				}
			}

			if (isset($_POST['rsssl-previous'])) {
				if (RSSSL_LE()->config->has_sections($page, $step)
				    && $section > $this->first_section($page, $step)
				) {
					$section--;
				} else {
					$step--;
					$section = $this->last_section($page, $step);
				}

				$step = $this->get_previous_not_empty_step($page, $step);
				$section = $this->get_previous_not_empty_section($page, $step, $section);
			}

			if ($type==='step'){
			    return $step;
			} else {
			    return $section;
			}
        }

		/**
		 * Render Wizard menu
		 * @param string $page
		 * @param string $wizard_title
		 * @param int $active_step
		 * @param int $active_section
		 *
		 * @return false|string
		 */
		public function wizard_menu( $page, $wizard_title, $active_step, $active_section )
		{
			$args_menu['steps'] = "";
			for ($i = 1; $i <= $this->total_steps($page); $i++)
			{
				if ($this->step_is_empty($page, $i)) continue;
				$args['title'] = RSSSL_LE()->config->steps[$page][$i]['title'];
				$args['active'] = ($i == $active_step) ? 'active' : '';
				$args['completed'] = $this->required_fields_completed($page, $i, false) ? 'complete' : 'incomplete';
				$args['url'] = '#';
				//get id of step based on $i
                //if this id is in the progress list it has been completed once, we show the url
                $id = RSSSL_LE()->config->steps['lets-encrypt'][$i]['id'];
                if ( rsssl_is_ready_for($id) ) {
					$args['url'] = add_query_arg(array('tab' => 'letsencrypt', 'step' => $i), $this->page_url);
				}
				$args['sections'] = ($args['active'] == 'active') ? $this->wizard_sections($page, $active_step, $active_section) : '';
				$step_html = RSSSL()->really_simple_ssl->get_template( 'step.php', $path = rsssl_le_wizard_path , $args);
				$args_menu['steps'] .= $step_html;
			}
			$args_menu['percentage-complete'] = $this->wizard_percentage_complete($page, $active_step);
			$args_menu['title'] = !empty( $wizard_title ) ? '<div class="rsssl-wizard-subtitle"><h2>' . $wizard_title . '</h2></div>': '' ;

			$html = RSSSL()->really_simple_ssl->get_template( 'menu.php', $path = rsssl_le_wizard_path, $args_menu );
			return $html;
		}

		/**
		 * @param string $page
		 * @param int $step
		 * @param int $active_section
		 *
		 * @return string
		 */
		public function wizard_sections( $page, $step, $active_section ) {
			$sections = "";

			if ( RSSSL_LE()->config->has_sections( $page, $step )) {

				for ($i = $this->first_section( $page, $step ); $i <= $this->last_section( $page, $step ); $i ++) {
					$icon = rsssl_icon('check', 'empty');

					if ( $this->section_is_empty( $page, $step, $i ) ) continue;
					if ( $i < $this->get_next_not_empty_section( $page, $step, $i ) ) continue;

					$active = ( $i == $active_section ) ? 'active' : '';
					if ( $active == 'active' ) {
						$icon = rsssl_icon('arrow-right-alt2', 'success');
					} else if ($this->required_fields_completed( $page, $step, $i )) {
						$icon = rsssl_icon('check', 'success');
					}

					$completed = ( $this->required_fields_completed( $page, $step, $i ) ) ? "rsssl-done" : "rsssl-to-do";
					$url = add_query_arg( array('tab' => 'letsencrypt', 'step' => $step, 'section' => $i), $this->page_url );
					$title = RSSSL_LE()->config->steps[ $page ][ $step ]['sections'][ $i ]['title'];
					$args = array(
						'active' => $active,
						'completed' => $completed,
						'icon' => $icon,
						'url' => $url,
						'title' => $title,
					);
					$section_html = RSSSL()->really_simple_ssl->get_template( 'section.php', $path = rsssl_le_wizard_path, $args );
					$sections .= $section_html;

				}
			}

			return $sections;
		}

		/**
		 * Render wizard content
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return false|string
		 */

		public function wizard_content( $page, $step, $section ) {
			$args = array(
			        'save_notice' => '',
			        'previous_button' => '',
			        'next_button' => '',
			        'save_button' => '',
			        'intro' => $this->get_intro( $page, $step, $section ),
			        'page_url' => $this->page_url,
			        'page' => $page,
			        'step' => $step,
			        'section' => $section,
            );
			if (isset(RSSSL_LE()->config->steps[$page][$step]['sections'][$section]['title'])) {
				$args['title'] = RSSSL_LE()->config->steps[$page][$step]['sections'][$section]['title'];
			} else {
				$args['title'] = RSSSL_LE()->config->steps[$page][$step]['title'];
			}

			ob_start();
			RSSSL_LE()->field->get_fields( $page, $step, $section );
			$args['fields'] = ob_get_clean();

			if ( $step > 1 || $section > 1 ) {
				$args['previous_button'] = '<input class="button button-link rsssl-previous" type="submit" name="rsssl-previous" value="'. __( "Previous", 'really-simple-ssl' ) . '">';
			}

			if ( $step < $this->total_steps( $page ) ) {
				$action_list = RSSSL_LE()->config->steps['lets-encrypt'][$step]['actions'];
				$disabled = '';
				if ( count($action_list)>0 ) {
					$disabled = 'disabled';
				}
				$args['next_button'] = '<input '.$disabled.' class="button button-primary rsssl-next" type="submit" name="rsssl-next" value="'. __( "Save and continue", 'really-simple-ssl' ) . '">';
			}

			if ( $step > 0  && $step < $this->total_steps( $page )) {
				$args['save_button'] = RSSSL_LE()->field->save_button();
			} elseif ($step === $this->total_steps( $page )) {
				$args['save_button'] = $this->activate_ssl_buttons();
			}

			$html = RSSSL()->really_simple_ssl->get_template( 'content.php', $path = rsssl_le_wizard_path, $args );
			return $html;

		}

		public function get_support_url()
		{
            $user_info = get_userdata(get_current_user_id());
            $email = urlencode($user_info->user_email);
            $name = urlencode($user_info->display_name);
			$verification_type = get_option('rsssl_verification_type') === 'DNS' ? 'DNS' : 'DIR';
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

            $debug_log_contents = RSSSL()->really_simple_ssl->debug_log;
            $debug_log_contents = str_replace("\n", '--br--', $debug_log_contents );
            $debug_log_contents .= 'dashboard '.$dashboard.'--br--';
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
                    <a class="button button-default" href="<?php echo esc_url(add_query_arg(array("page"=>"rlrsssl_really_simple_ssl"),admin_url("options-general.php") ) );?>"><?php _e("Go to dashboard", "really-simple-ssl"); ?></a>
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

		/**
		 * If a section does not contain any fields to be filled, just drop it from the menu.
		 * @return bool
		 *
		 * */

		public function section_is_empty( $page, $step, $section ) {
			$section_compare = $this->get_next_not_empty_section( $page, $step, $section );
			if ( $section != $section_compare ) {
				return true;
			}

			return false;
		}

		public function step_is_empty( $page, $step ) {
			$step_compare = $this->get_next_not_empty_step( $page, $step );
			if ( $step != $step_compare ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue assets
		 * @param $hook
		 */
		public function enqueue_assets( $hook ) {
			if (!isset($_GET['tab']) || $_GET['tab']!=='letsencrypt') return;

			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_register_style( 'select2', rsssl_le_url . 'wizard/assets/select2/css/select2.min.css', false, rsssl_version );
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2', rsssl_le_url . "wizard/assets/select2/js/select2.min.js", array( 'jquery' ), rsssl_version, true );


			// Let's encrypt
			wp_register_style( 'rsssl-wizard', rsssl_le_url . "wizard/assets/css/wizard.css", false, rsssl_version );
			wp_enqueue_style( 'rsssl-wizard' );
			// @todo admin css in wizard.less
			wp_register_style( 'rsssl-wizard-admin', rsssl_le_url . "wizard/assets/css/admin.css", false, rsssl_version );
			wp_enqueue_style( 'rsssl-wizard-admin' );

			wp_enqueue_script( 'rsssl-wizard', rsssl_le_url . "wizard/assets/js/wizard$minified.js", array( 'jquery', 'select2' ), rsssl_version.time(), true );
			wp_localize_script(
				'rsssl-wizard',
				'rsssl_wizard',
				array(
					'admin_url'    => admin_url( 'admin-ajax.php' ),
					'no_results'    => __("I don't know, or not listed, proceed with installation","really-simple-ssl"),
				)
			);
		}


		/**
		 * Foreach required field, check if it's been answered
		 * if section is false, check all fields of the step.
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return bool
		 */


		public function required_fields_completed( $page, $step, $section ) {
			//get all required fields for this section, and check if they're filled in
			$fields = RSSSL_LE()->config->fields( $page, $step, $section );
			$fields = rsssl_array_filter_multidimensional( $fields, 'required', true );
			foreach ( $fields as $fieldname => $args ) {
				//if a condition exists, only check for this field if the condition applies.
				if ( isset( $args['condition'] )
				     || isset( $args['callback_condition'] )
				        && ! RSSSL_LE()->field->condition_applies( $args )
				) {
					continue;
				}
				$value = RSSSL_LE()->field->get_value( $fieldname );
				if ( empty( $value ) ) {
					return false;
				}
			}
			return true;
		}

		public function all_required_fields_completed_wizard(){
			return $this->all_required_fields_completed('lets-encrypt');
		}

		/**
		 * Check if all required fields are filled
		 * @return bool
		 *
		 * */

		public function all_required_fields_completed( $page ) {
			for ( $step = 1; $step <= $this->total_steps( $page ); $step ++ ) {
				if ( RSSSL_LE()->config->has_sections( $page, $step ) ) {
					for (
						$section = $this->first_section( $page, $step );
						$section <= $this->last_section( $page, $step );
						$section ++
					) {
						if ( ! $this->required_fields_completed( $page, $step,
							$section )
						) {
							return false;
						}
					}
				} else {
					if ( ! $this->required_fields_completed( $page, $step,
						false )
					) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Get a notice style header with an intro above a step or section
		 *
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return string
		 */

		public function get_intro( $page, $step, $section ) {
			//only show when in action
			$intro = '';
			if ( RSSSL_LE()->config->has_sections( $page, $step ) ) {
				if ( isset( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'][ $section ]['intro'] ) ) {
					$intro .= RSSSL_LE()->config->steps[ $page ][ $step ]['sections'][ $section ]['intro'];
				}
			} else {
				if ( isset( RSSSL_LE()->config->steps[ $page ][ $step ]['intro'] ) ) {
					$intro .= RSSSL_LE()->config->steps[ $page ][ $step ]['intro'];
				}
			}

			if ( strlen( $intro ) > 0 ) {
				$intro = '<div class="rsssl-wizard-intro">'
				         . $intro
				         . '</div>';
			}

			return $intro;
		}


		public function get_type( $post_id = false ) {
			$page = false;
			if ( $post_id ) {
				$post_type = get_post_type( $post_id );
				$page      = str_replace( 'rsssl-', '', $post_type );
			}
			if ( isset( $_GET['page'] ) ) {
				$page = str_replace( 'rsssl-', '',
					sanitize_title( $_GET['page'] ) );
			}

			return $page;
		}

		public function step( $page = false ) {
			$step = 1;
			if ( ! $page ) {
				$page = 'lets-encrypt';
			}

			$total_steps = $this->total_steps( $page );

			if ( isset( $_GET["step"] ) ) {
				$step = intval( $_GET['step'] );
			}

			if ( isset( $_POST["step"] ) ) {
				$step = intval( $_POST['step'] );
			}

			if ( $step > $total_steps ) {
				$step = $total_steps;
			}

			if ( $step <= 1 ) {
				$step = 1;
			}

			return $step;
		}

		public function section() {
			$section = 1;
			if ( isset( $_GET["section"] ) ) {
				$section = intval( $_GET['section'] );
			}

			if ( isset( $_POST["section"] ) ) {
				$section = intval( $_POST['section'] );
			}

			if ( $section > $this->last_section ) {
				$section = $this->last_section;
			}

			if ( $section <= 1 ) {
				$section = 1;
			}

			return $section;
		}

		/**
		 * Get total number of steps for a page
		 *
		 * @param $page
		 *
		 * @return int
		 */

		public function total_steps( $page ) {
			return count( RSSSL_LE()->config->steps[ $page ] );
		}

		public function total_sections( $page, $step ) {
			if ( ! isset( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'] ) ) {
				return 0;
			}

			return count( RSSSL_LE()->config->steps[ $page ][ $step ]['sections'] );
		}

		public function last_section( $page, $step ) {
			if ( ! isset( RSSSL_LE()->config->steps[ $page ][ $step ]["sections"] ) ) {
				return 1;
			}

			$array = RSSSL_LE()->config->steps[ $page ][ $step ]["sections"];

			return max( array_keys( $array ) );

		}

		public function first_section( $page, $step ) {
			if ( ! isset( RSSSL_LE()->config->steps[ $page ][ $step ]["sections"] ) ) {
				return 1;
			}

			$arr       = RSSSL_LE()->config->steps[ $page ][ $step ]["sections"];
			$first_key = key( $arr );

			return $first_key;
		}


		/**
		 *
		 * Check which percentage of the wizard is completed
		 * @param bool $count_warnings
		 *
		 * @return int
		 * */


		public function wizard_percentage_complete( $page, $step )
		{
			//store to make sure it only runs once.
			if ( $this->percentage_complete !== false ) {
				return $this->percentage_complete;
			}

			$total_steps = $this->total_steps( 'lets-encrypt' );
			$percentage = round( 100 * ( $step / $total_steps ) + 0.45 );
			$this->percentage_complete = $percentage;
			return $percentage;
		}

	}


} //class closure
