<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Allows plugins to use their own update API.
 *
 * @author Easy Digital Downloads
 * @version 1.7
 */
if ( !class_exists('rsp_upgrade_to_pro') ){
	class rsp_upgrade_to_pro {
		private $version = 1;
		private $api_url = "";
		private $license = "";
		private $item_id = "";
		private $slug = "";
		private $health_check_timeout = 5;
		private $plugin_name = "";
		private $plugin_constant = "";
		private $steps;
		private $prefix;
		private $dashboard_url;
		private $instructions;
		private $account_url;

		/**
		 * Class constructor.
		 *
		 */
		public function __construct() {

			if ( isset($_GET['license']) ) {
				$this->license = sanitize_title($_GET['license']);
			}

			if ( isset($_GET['item_id']) ) {
				$this->item_id = sanitize_title($_GET['item_id']);
			}

			if ( isset($_GET['plugin']) ) {
				$plugin = sanitize_title($_GET['plugin']);
				switch ($plugin) {
					case "rsssl_pro":
						$rsssl_admin_url = is_multisite() ? network_admin_url('settings.php') : admin_url("options-general.php");
						$this->slug = is_multisite() ? "really-simple-ssl-pro-multisite/really-simple-ssl-pro-multisite.php" :  "really-simple-ssl-pro/really-simple-ssl-pro.php";
						$this->plugin_name = "Really Simple Security Pro";
						$this->plugin_constant = "rsssl_pro";
						$this->prefix = "rsssl_";
						$this->api_url = "https://really-simple-ssl.com";
						$this->dashboard_url = add_query_arg(["page" => "really-simple-security"], $rsssl_admin_url );
						$this->account_url = 'https://really-simple-ssl.com/account';
						$this->instructions = 'https://really-simple-ssl.com/knowledge-base/install-really-simple-ssl-pro';
						break;
					case "burst_pro":
						$this->slug = "burst-pro/burst-pro.php";
						$this->plugin_name = "Burst";
						$this->plugin_constant = "burst_pro";
						$this->prefix = "burst_";
						$this->api_url = "https://burst-statistics.com";
						$this->dashboard_url = add_query_arg(["page" => "burst"], admin_url( "index.php" ));
						$this->account_url = 'https://burst-statistics.com/account';
						$this->instructions = 'https://burst-statistics.com/how-to-install-burst-premium';
						break;
					case "cmplz_pro":
					default:
                        $this->slug = is_multisite() ? "complianz-gdpr-premium-multisite/complianz-gpdr-premium.php" : "complianz-gdpr-premium/complianz-gpdr-premium.php";
						$this->plugin_name = "Complianz";
						$this->plugin_constant = "cmplz_premium";
						$this->prefix = "cmplz_";
						$this->api_url = "https://complianz.io";
						$this->dashboard_url = add_query_arg(["page" => "complianz"], admin_url("admin.php") );
                        $this->account_url = 'https://complianz.io/account';
                        $this->instructions = 'https://complianz.io/how-to-install-complianz-gdpr-premium-plugin';
						break;
				}
			}

			$this->steps = array(
				array(
					'action' => 'rsp_upgrade_destination_clear',
					'doing' => __("Checking if plugin folder exists...", "really-simple-ssl"),
					'success' => __("Able to create destination folder", "really-simple-ssl"),
					'error' => __("Destination folder already exists", "really-simple-ssl"),
					'type' => 'folder',
				),
				array(
					'action' => 'rsp_upgrade_activate_license',
					'doing' => __("Validating license...", "really-simple-ssl"),
					'success' => __("License valid", "really-simple-ssl"),
					'error' => __("License invalid", "really-simple-ssl"),
					'type' => 'license',
				),
				array(
					'action' => 'rsp_upgrade_package_information',
					'doing' => __("Retrieving package information...", "really-simple-ssl"),
					'success' => __("Package information retrieved", "really-simple-ssl"),
					'error' => __("Failed to gather package information", "really-simple-ssl"),
					'type' => 'package',
				),
				array(
					'action' => 'rsp_upgrade_install_plugin',
					'doing' => __("Installing plugin...", "really-simple-ssl"),
					'success' => __("Plugin installed", "really-simple-ssl"),
					'error' => __("Failed to install plugin", "really-simple-ssl"),
					'type' => 'install',
				),
				array(
					'action' => 'rsp_upgrade_activate_plugin',
					'doing' => __("Activating plugin...", "really-simple-ssl"),
					'success' => __("Plugin activated", "really-simple-ssl"),
					'error' => __("Failed to activate plugin", "really-simple-ssl"),
					'type' => 'activate',
				)
			);

			// Set up hooks.
			$this->init();
		}

		private function get_suggested_plugin($attr){
			$plugin_to_be_installed = $current_plugin = false;
			if (isset($_GET['plugin']) && $_GET['plugin']==='cmplz_pro' ) {
				$plugin_to_be_installed = 'complianz-gdpr';
			} else if (isset($_GET['plugin']) && $_GET['plugin']==='rsssl_pro' ) {
				$plugin_to_be_installed = 'really-simple-ssl';
			} else if (isset($_GET['plugin']) && $_GET['plugin']==='burst_pro' ) {
				$plugin_to_be_installed = 'burst';
			}

			$path = __FILE__;
			if (strpos($path, 'really-simple-ssl')!==false) {
				$current_plugin = 'really-simple-ssl';
			} else if (strpos($path, 'complianz')!==false){
				$current_plugin = 'complianz-gdpr';
			} else if (strpos($path, 'burst')!==false){
				$current_plugin = 'burst';
			}
			$dir_url = plugin_dir_url(__FILE__).'img/';

			$suggestion = $fallback_suggestion = [
				'icon_url' => $dir_url.'burst.png',
				'constant' => 'burst_version',
				'title' => 'Burst – Privacy Friendly Statistics',
				'description_short' => __('Self-hosted and privacy-friendly analytics tool.', "really-simple-ssl"),
				'disabled' => '',
				'button_text' => __("Install", "really-simple-ssl"),
				'slug' => 'burst-statistics',
				'description' => __("Get detailed insights into visitors' behaviour with Burst Statistics, the privacy-friendly analytics dashboard from Really Simple Plugins.", "really-simple-ssl"),
				'install_url' => 'burst%20statistics%20hesseldejong%20%20burst-statistics.com&tab=search&type=term',
			];

			if ( $plugin_to_be_installed === 'really-simple-ssl' || $plugin_to_be_installed === 'burst' ){
				$suggestion = [
					'icon_url' => $dir_url.'complianz-gdpr.png',
					'constant' => 'cmplz_version',
					'title' => 'Complianz GDPR/CCPA',
					'description_short' => __('GDPR/CCPA Privacy Suite', "really-simple-ssl"),
					'disabled' => '',
					'button_text' => __("Install", "really-simple-ssl"),
					'slug' => 'complianz-gdpr',
					'description' => __('Configure your Cookie Notice, Consent Management and Cookie Policy with our Wizard and Cookie Scan. Supports GDPR, DSGVO, TTDSG, LGPD, POPIA, RGPD, CCPA and PIPEDA.', "really-simple-ssl"),
					'install_url' => 'complianz+gdpr+POPIA&tab=search&type=term',
				];
				if ($current_plugin==='complianz-gdpr') {
					$suggestion = $fallback_suggestion;
				}
			}

			if ( $plugin_to_be_installed === 'complianz-gdpr' ){
				$suggestion = [
					'icon_url' => $dir_url.'really-simple-ssl.png',
					'constant' => 'rsssl_version',
					'title' => 'Really Simple Security',
					'description_short' => __('One click SSL optimization', "really-simple-ssl"),
					'disabled' => '',
					'button_text' => __("Install", "really-simple-ssl"),
					'slug' => 'really-simple-ssl',
					'description' => __('Easily improve site security with WordPress Hardening, Two-Factor Authentication (2FA), Login Protection, Vulnerability Detection and SSL certificate generation.', "really-simple-ssl"),
					'install_url' => 'ssl%20really%20simple%20plugins%20complianz+HSTS&tab=search&type=term',
				];
				if ( $current_plugin==='really-simple-ssl' ) {
					$suggestion = $fallback_suggestion;
				}
			}

			$admin_url = is_multisite() ? network_admin_url('plugin-install.php?s=') : admin_url('plugin-install.php?s=');
			$suggestion['install_url'] = $admin_url.$suggestion['install_url'];
			if (defined($suggestion['constant'])){
				$suggestion['install_url'] = '#';
				$suggestion['button_text'] = __("Installed", "really-simple-ssl");
				$suggestion['disabled'] = 'disabled';
			}

			return $suggestion[$attr];
		}

		/**
		 * Set up WordPress filters to hook into WP's update process.
		 *
		 * @uses add_filter()
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_footer', array( $this, 'print_install_modal' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets') );
			add_action( 'wp_ajax_rsp_upgrade_destination_clear', array($this, 'process_ajax_destination_clear') );
			add_action( 'wp_ajax_rsp_upgrade_activate_license', array($this, 'process_ajax_activate_license') );
			add_action( 'wp_ajax_rsp_upgrade_package_information', array($this, 'process_ajax_package_information') );
			add_action( 'wp_ajax_rsp_upgrade_install_plugin', array($this, 'process_ajax_install_plugin') );
			add_action( 'wp_ajax_rsp_upgrade_activate_plugin', array($this, 'process_ajax_activate_plugin') );
		}

		/**
		 * Enqueue javascript
		 * @todo minification
		 */
		public function enqueue_assets( $hook ) {
			if ( $hook === "plugins.php" && isset($_GET['install_pro']) ) {
				$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
				wp_register_style( 'rsp-upgrade-css', plugin_dir_url(__FILE__) . "upgrade-to-pro$minified.css", false, $this->version );
				wp_enqueue_style( 'rsp-upgrade-css' );
				wp_enqueue_script( 'rsp-ajax-js', plugin_dir_url(__FILE__) . "ajax$minified.js", array(), $this->version, true );
				wp_enqueue_script( 'rsp-upgrade-js', plugin_dir_url(__FILE__) . "upgrade-to-pro$minified.js", array(), $this->version, true );
				wp_localize_script(
					'rsp-upgrade-js',
					'rsp_upgrade',
					array(
						'steps' => $this->steps,
						'admin_url' => admin_url( 'admin-ajax.php' ),
						'token'     => wp_create_nonce( 'upgrade_to_pro_nonce'),
						'cmplz_nonce'     => wp_create_nonce( 'complianz_save'),
						'finished_title' => __("Installation finished", "really-simple-ssl"),
					)
				);
			}
		}

		/**
		 * Calls the API and, if successfull, returns the object delivered by the API.
		 *
		 * @uses get_bloginfo()
		 * @uses wp_remote_post()
		 * @uses is_wp_error()
		 *
		 * @return false|object
		 */
		private function api_request() {
			if ( !rsssl_user_can_manage() ) {
				return false;
			}
			global $edd_plugin_url_available;

			// Do a quick status check on this domain if we haven't already checked it.
			$store_hash = md5( $this->api_url );
			if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
				$test_url_parts = parse_url( $this->api_url );
				$port   = ! empty( $test_url_parts['port'] ) ? ':' . $test_url_parts['port'] : '';
				$host   = ! empty( $test_url_parts['host'] ) ? $test_url_parts['host'] : '';
				$test_url = 'https://' . $host . $port;
				$response = wp_remote_get( $test_url, array( 'timeout' => $this->health_check_timeout, 'sslverify' => true ) );
				$edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
			}

			if ( false === $edd_plugin_url_available[ $store_hash ] ) {
				return false;
			}

			if( $this->api_url == trailingslashit ( home_url() ) ) {
				return false; // Don't allow a plugin to ping itself
			}

			$api_params = array(
				'edd_action' => 'get_version',
				'license'    => ! empty( $this->license ) ? $this->license : '',
				'item_id'    => isset( $this->item_id ) ? $this->item_id : false,
				'url'        => home_url(),
			);
			$request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );
			if ( ! is_wp_error( $request ) ) {
				$request = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( $request && isset( $request->sections ) ) {
				$request->sections = maybe_unserialize( $request->sections );
			} else {
				$request = false;
			}

			if ( $request && isset( $request->banners ) ) {
				$request->banners = maybe_unserialize( $request->banners );
			}

			if ( $request && isset( $request->icons ) ) {
				$request->icons = maybe_unserialize( $request->icons );
			}

			if( ! empty( $request->sections ) ) {
				foreach( $request->sections as $key => $section ) {
					$request->$key = (array) $section;
				}
			}

			return $request;
		}

		/**
		 * Prints a modal with bullets for each step of the install process
		 */
		public function print_install_modal()
		{
			if ( !rsssl_user_can_manage() ) {
				return false;
			}

			if ( is_admin() && isset($_GET['install_pro']) && isset($_GET['license']) && isset($_GET['item_id']) && isset($_GET['plugin']) ) {
				$dashboard_url = $this->dashboard_url;
				$plugins_url = admin_url( "plugins.php" );
				?>
                <div id="rsp-step-template">
                    <div class="rsp-install-step {step}">
                        <div class="rsp-step-color">
                            <div class="rsp-grey rsp-bullet"></div>
                        </div>
                        <div class="rsp-step-text">
                            <span>{doing}</span>
                        </div>
                    </div>
                </div>
                <div id="rsp-plugin-suggestion-template">
                    <div class="rsp-recommended"><?php _e("Recommended by Really Simple Plugins","really-simple-ssl")?></div>
                    <div class="rsp-plugin-suggestion">
                        <div class="rsp-icon"><img alt="suggested plugin icon" src="<?=$this->get_suggested_plugin('icon_url')?>"></div>
                        <div class="rsp-summary">
                            <div class="rsp-title"><?=$this->get_suggested_plugin('title')?></div>
                            <div class="rsp-description_short"><?=$this->get_suggested_plugin('description_short')?></div>
                            <div class="rsp-rating"><?php
								$plugin_info = $this->get_plugin_info($this->get_suggested_plugin('slug'));

								if (!is_wp_error($plugin_info) && !empty($plugin_info->rating)) {
									wp_star_rating([
											'rating' => $plugin_info->rating,
											'type' => 'percent',
											'number' => $plugin_info->num_ratings
										]
									);
								}
								?></div>
                        </div>
                        <div class="rsp-description"><?=$this->get_suggested_plugin('description')?></div>
                        <div class="rsp-install-button"><a class="button-secondary" <?=$this->get_suggested_plugin('disabled')?> href="<?=$this->get_suggested_plugin('install_url')?>"><?=$this->get_suggested_plugin('button_text')?></a></div>
                    </div>
                </div>
                <div class="rsp-modal-transparent-background">
                    <div class="rsp-install-plugin-modal">
                        <h3><?php echo __("Installing", "really-simple-ssl") . " " . $this->plugin_name ?></h3>
                        <div class="rsp-progress-bar-container">
                            <div class="rsp-progress rsp-grey">
                                <div class="rsp-bar rsp-green" style="width:0%"></div>
                            </div>
                        </div>
                        <div class="rsp-install-steps">

                        </div>
                        <div class="rsp-footer">
                            <a href="<?php echo $dashboard_url ?>" role="button" class="button-primary rsp-yellow rsp-hidden rsp-btn rsp-visit-dashboard">
								<?php echo __("Visit Dashboard", "really-simple-ssl") ?>
                            </a>
                            <a href="<?php echo $plugins_url ?>" role="button" class="button-primary rsp-red rsp-hidden rsp-btn rsp-cancel">
								<?php echo __("Cancel", "really-simple-ssl") ?>
                            </a>
                            <div class="rsp-error-message rsp-folder rsp-package rsp-install rsp-activate rsp-hidden"><span><?php _e('An error occurred:',"really-simple-ssl")?></span>&nbsp;<?php printf(__('Install %sManually%s.',"really-simple-ssl").'&nbsp;', '<a target="_blank" rel="noopener noreferrer" href="'.$this->account_url.'">','</a>')?></div>
                            <div class="rsp-error-message rsp-license rsp-hidden"><span><?php _e('An error occurred:',"really-simple-ssl")?></span>&nbsp;<?php printf(__('Check your %slicense%s.',"really-simple-ssl").'&nbsp;', '<a target="_blank" rel="noopener noreferrer" href="'.$this->instructions.'">','</a>')?></div>
                        </div>
                    </div>
                </div>
				<?php
			}
		}


		/**
		 * Retrieve plugin info for rating use
		 *
		 * @uses plugins_api() Get the plugin data
		 *
		 * @param  string $slug The WP.org directory repo slug of the plugin
		 *
		 * @version 1.0
		 */
		private function get_plugin_info($slug = '')
		{
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			$plugin_info = get_transient('rsp_'.$slug . '_plugin_info');
			if ( empty($plugin_info) ) {
				$plugin_info = plugins_api('plugin_information', array('slug' => $slug));
				if (!is_wp_error($plugin_info)) {
					set_transient('rsp_'.$slug . '_plugin_info', $plugin_info, WEEK_IN_SECONDS);
				}
			}
			return $plugin_info;
		}

		/**
		 * Ajax GET request
		 *
		 * Checks if the destination folder already exists
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'plugin' (This will set $this->slug (Ex. 'really-simple-ssl-pro/really-simple-ssl-pro.php'), based on which plugin)
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_destination_clear()
		{
			$error = false;
			$response = [
				'success' => false,
			];

			if ( !rsssl_user_can_manage() ) {
				$error = true;
			}

            if ( !isset($_GET['token']) || !wp_verify_nonce($_GET['token'], 'upgrade_to_pro_nonce')) {
                $error = true;
            }

            if (!$error) {
	            if ( defined( $this->plugin_constant ) ) {
		            deactivate_plugins( $this->slug );
	            }

	            $file = trailingslashit( WP_CONTENT_DIR ) . 'plugins/' . $this->slug;
	            if ( file_exists( $file ) ) {
		            $dir     = dirname( $file );
		            $new_dir = $dir . '_' . time();
		            set_transient( 'rsssl_upgrade_dir', $new_dir, WEEK_IN_SECONDS );
		            rename( $dir, $new_dir );
		            //prevent uninstalling code by previous plugin
		            unlink( trailingslashit( $new_dir ) . 'uninstall.php' );
	            }
            }

			if ( !$error && file_exists($file ) ) {
				$error = true;
				$response = [
					'success' => false,
					'message' => __("Could not rename folder!", "really-simple-ssl"),
				];
			}

			if ( !$error && isset($_GET['plugin']) ) {
				if ( !file_exists(WP_PLUGIN_DIR . '/' . $this->slug) ) {
					$response = [
						'success' => true,
					];
				}
			}

			$response = json_encode($response);
			header("Content-Type: application/json");
			echo $response;
			exit;
		}


		/**
		 * Ajax GET request
		 *
		 * Links the license on the website to this site
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'license'
		 * - 'item_id'
		 *
		 * (Without this link you cannot download the pro package from the website)
		 *
		 * Echoes array [license status, response message]
		 */
		public function process_ajax_activate_license()
		{
			$error = false;
			$response = [
				'success' => false,
				'message' => '',
			];

			if ( !rsssl_user_can_manage() ) {
				$error = true;
			}

			if (!$error && isset($_GET['token']) && wp_verify_nonce($_GET['token'], 'upgrade_to_pro_nonce') && isset($_GET['license']) && isset($_GET['item_id']) ) {
				$license  = sanitize_title($_GET['license']);
				$item_id = (int) $_GET['item_id'];
				$response = $this->validate($license, $item_id);
				update_site_option($this->prefix.'auto_installed_license', $license);
			}

			$response = json_encode($response);
			header("Content-Type: application/json");
			echo $response;
			exit;
		}


		/**
		 * Activate the license on the websites url at EDD
		 *
		 * Stores values in database:
		 * - {$this->pro_prefix}license_activations_left
		 * - {$this->pro_prefix}license_expires
		 * - {$this->pro_prefix}license_activation_limit
		 *
		 * @param $license
		 * @param $item_id
		 *
		 * @return array [license status, response message]
		 */

		private function validate( $license, $item_id ): array {
			$message = "";
			$success = false;

			if ( !rsssl_user_can_manage() ) {
				return [
					'success' => $success,
					'message' => $message,
				];
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => $item_id,
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.', "really-simple-ssl");
				}
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( false === $license_data->success ) {
					switch( $license_data->error ) {
						case 'expired' :
							$message = sprintf(
								__( 'Your license key expired on %s.', "really-simple-ssl"),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							);
							break;
						case 'disabled' :
						case 'revoked' :
							$message = __( 'Your license key has been disabled.', "really-simple-ssl");
							break;
						case 'missing' :
							$message = __('Missing license.', "really-simple-ssl");
							break;
						case 'invalid' :
							$message = __( 'Invalid license.', "really-simple-ssl");
							break;
						case 'site_inactive' :
							$message = __( 'Your license is not active for this URL.', "really-simple-ssl" );
							break;
						case 'item_name_mismatch' :
							$message = __( 'This appears to be an invalid license key for this plugin.', "really-simple-ssl" );
							break;
						case 'no_activations_left':
							$message = __( 'Your license key has reached its activation limit.', "really-simple-ssl");
							break;
						default :
							$message = __( 'An error occurred, please try again.', "really-simple-ssl" );
							break;
					}
                    //in case of failure, rename back to default
					$new_dir = get_transient('rsssl_upgrade_dir');
                    if ( $new_dir ) {
	                    if ( file_exists($new_dir ) ) {
		                    $default_file = trailingslashit(WP_CONTENT_DIR).'plugins/'.$this->slug;
		                    $default_dir = dirname($default_file);
		                    rename($new_dir, $default_dir);
	                    }
                    }


				} else {
					$success = $license_data->license === 'valid';
				}
			}

			return [
				'success' => $success,
				'message' => $message,
			];
        }


		/**
		 * Ajax GET request
		 *
		 * Do an API request to get the download link where to download the pro package
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'license'
		 * - 'item_id'
		 *
		 * Echoes array [success, download_link]
		 */
		public function process_ajax_package_information()
		{
			if ( !rsssl_user_can_manage() ) {
				return false;
			}

			if ( isset($_GET['token']) && wp_verify_nonce($_GET['token'], 'upgrade_to_pro_nonce') && isset($_GET['license']) && isset($_GET['item_id']) ) {
				$api = $this->api_request();
				if ( $api && isset($api->download_link) ) {
					$response = [
						'success' => true,
						'download_link' => $api->download_link,
					];
				} else {
					$response = [
						'success' => false,
						'download_link' => "",
					];
				}
				$response = json_encode($response);
				header("Content-Type: application/json");
				echo $response;
				exit;

			}
		}


		/**
		 * Ajax GET request
		 *
		 * Download and install the plugin
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'download_link'
		 * (Linked license on the website to this site)
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_install_plugin()
		{
			$message = '';

			if ( !rsssl_user_can_manage() ) {
				return [
					'success' => false,
					'message' => $message,
				];
			}

			if ( isset($_GET['token']) && wp_verify_nonce($_GET['token'], 'upgrade_to_pro_nonce') && isset($_GET['download_link']) ) {

				$download_link = esc_url_raw($_GET['download_link']);
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

				$skin     = new WP_Ajax_Upgrader_Skin();
				$upgrader = new Plugin_Upgrader( $skin );
				$result   = $upgrader->install( $download_link );

				if ( $result ) {
					$response = [
						'success' => true,
					];
				} else {
					if ( is_wp_error($result) ){
						$message = $result->get_error_message();
					}
					$response = [
						'success' => false,
						'message' => $message,
					];
				}

				$response = json_encode($response);
				header("Content-Type: application/json");
				echo $response;
				exit;
			}
		}


		/**
		 * Ajax GET request
		 *
		 * Do an API request to get the download link where to download the pro package
		 *
		 * Requires from GET:
		 * - 'token' => wp_nonce 'upgrade_to_pro_nonce'
		 * - 'plugin' (This will set $this->slug (Ex. 'really-simple-ssl-pro/really-simple-ssl-pro.php'), based on which plugin)
		 *
		 * Echoes array [success]
		 */
		public function process_ajax_activate_plugin()
		{
			if ( !rsssl_user_can_manage() ) {
				return;
			}

			if ( isset($_GET['token']) && wp_verify_nonce($_GET['token'], 'upgrade_to_pro_nonce') && isset($_GET['plugin']) ) {
				$networkwide = is_multisite() && rsssl_is_networkwide_active();
				$result = activate_plugin( $this->slug, '', $networkwide  );
				if ( !is_wp_error($result) ) {
					$response = [
						'success' => true,
					];
				} else {
					$response = [
						'success' => false,
					];
				}
				$response = json_encode($response);
				header("Content-Type: application/json");
				echo $response;
				exit;
			}
		}
	}
	$rsp_upgrade_to_pro = new rsp_upgrade_to_pro();
}
