<?php

defined( 'ABSPATH' ) or die();

require_once rsssl_path . '/lib/admin/class-helper.php';
use RSSSL\lib\admin\Helper;
class rsssl_admin {
    use Helper;
	private static $_this;
	public $wpconfig_siteurl_not_fixed   = false;
	public $no_server_variable           = false;
	public $do_wpconfig_loadbalancer_fix = false;
	public $plugin_dir                   = 'really-simple-ssl';
	public $plugin_filename              = 'rlrsssl-really-simple-ssl.php';
	public $abs_path;
	public $ssl_type = 'NA';
	public $configuration_loaded = false;

	public function __construct() {

		if ( isset( self::$_this ) ) {
			wp_die( 'cannot create a second instance.' );
		}

		if ( defined( 'rsssl_pro' ) ) {
			if ( is_multisite() ) {
				$this->plugin_dir      = 'really-simple-ssl-pro-multisite';
				$this->plugin_filename = 'really-simple-ssl-pro-multisite.php';
			} else {
				$this->plugin_dir      = 'really-simple-ssl-pro';
				$this->plugin_filename = 'really-simple-ssl-pro.php';
			}
		}

		self::$_this    = $this;
		$this->abs_path = $this->getabs_path();

		register_deactivation_hook( __DIR__ . '/' . $this->plugin_filename, array( $this, 'deactivate' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_info' ) );
		add_action( 'admin_init', array( $this, 'maybe_dismiss_review_notice' ) );
		add_action( 'rsssl_daily_cron', array( $this, 'clear_admin_notices_cache' ) );

		//add the settings page for the plugin
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'listen_for_deactivation' ), 40 );
		add_action( 'plugins_loaded', array( $this, 'maybe_redirect_old_settings_url' ), 10 );

		//callbacks for the ajax dismiss buttons
		add_action( 'wp_ajax_rsssl_dismiss_review_notice', array( $this, 'dismiss_review_notice_callback' ) );

		//handle notices
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		//show review notice, only to free users
		if ( ! defined( 'rsssl_pro' ) && ! is_multisite() ) {
			add_action( 'admin_notices', array( $this, 'show_leave_review_notice' ) );
		}

		//hooks only needed on settings page
		if ( $this->is_settings_page() ) {
			/**
			 * Htaccess redirect handling
			 */
			add_action( 'rsssl_after_save_field', array( $this, 'maybe_flush_wprocket_htaccess' ), 100, 4 );
			add_action( 'admin_init', array( $this, 'insert_secure_cookie_settings' ), 70 );
			add_action( 'admin_init', array( $this, 'recheck_certificate' ) );
		}

		add_filter( 'rsssl_htaccess_security_rules', array( $this, 'add_htaccess_redirect' ) );
		add_filter( 'before_rocket_htaccess_rules', array( $this, 'add_htaccess_redirect_before_wp_rocket' ) );
		add_filter( 'admin_init', array( $this, 'handle_activation' ) );
		add_action( 'rocket_activation', 'rsssl_wrap_htaccess' );
		add_action( 'rocket_deactivation', 'rsssl_wrap_htaccess' );
		$plugin = rsssl_plugin;
		add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ) );
		add_filter( "network_admin_plugin_action_links_$plugin", array($this,'plugin_settings_link' ) );
		add_filter( 'rsssl_clear_test_caches', array( $this, 'clear_404_test_cache'), 10, 1 );

		add_action( 'rsssl_upgrade', array( $this, 'run_table_init_hook'), 10, 1);
		add_action( 'upgrader_process_complete', array( $this, 'run_table_init_hook'), 10, 1);
		add_action( 'wp_initialize_site', array( $this, 'run_table_init_hook'), 10, 1);
		add_action( "rsssl_after_save_field", array($this, 'maybe_delete_permission_detection_option'), 101, 4 );

	}

	public static function this() {
		return self::$_this;
	}

	/**
	 * On Multisite site creation, run table init hook as well.
	 * @return void
	 */
	public function run_table_init_hook(){
		//only load on front-end if it's a cron job
		if ( !is_admin() && !wp_doing_cron() ) {
			return;
		}

		if ( !wp_doing_cron() && !rsssl_user_can_manage() ) {
			return;
		}

        //if this is already triggered, exit.
        if ( defined('RSSSL_INSTALLING_TABLES') && RSSSL_INSTALLING_TABLES ) {
            return;
        }

        define('RSSSL_INSTALLING_TABLES', true);

		do_action( 'rsssl_install_tables' );
		//we need to run table creation across subsites as well.
		if ( is_multisite() ) {
			$sites = get_sites();
			if (count($sites)>0) {
				foreach ($sites as $site) {
					switch_to_blog($site->blog_id);
					do_action( 'rsssl_install_tables' );
					restore_current_blog();
				}
			}
		}
	}

    public function handle_activation(){
        if ( !rsssl_admin_logged_in() ) {
            return;
        }

        if ( get_option('rsssl_activation') ) {
	        if ( !class_exists('rsssl_le_hosts')) {
		        require_once( rsssl_path . 'lets-encrypt/config/class-hosts.php');
	        }
	        ( new rsssl_le_hosts() )->detect_host_on_activation();
	        $this->run_table_init_hook();
	        do_action('rsssl_activation');
            delete_option('rsssl_activation');
        }
    }

	/**
	 * Add settings link on plugins overview page
	 * @param array $links
	 *
	 * @return array
	 */

	public function plugin_settings_link($links) {
		//free version
		if ( ! rsssl_user_can_manage() || ( is_multisite() && ! is_network_admin() ) ) {
			return $links;
		}
		$settings_link = '';
		$url = rsssl_admin_url();
		//settings only on network wide activated, or no multisite at all.
		if ( is_multisite() && rsssl_is_networkwide_active() && is_super_admin() ) {
			$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'really-simple-ssl' ) . '</a>';
		} elseif ( ! is_multisite() ) {
			$settings_link = '<a href="' . $url . '">' . __( 'Settings', 'really-simple-ssl' ) . '</a>';
		}
		array_unshift( $links, $settings_link );

		//support
		$support = apply_filters( 'rsssl_support_link', '<a rel="noopener noreferrer" target="_blank" href="https://wordpress.org/support/plugin/really-simple-ssl/">' . __( 'Support', 'really-simple-ssl' ) . '</a>' );
		array_unshift( $links, $support );

		if ( ! defined( 'rsssl_pro' ) ) {
			$upgrade_link = '<a style="color:#2271b1;font-weight:bold" target="_blank" rel="noopener noreferrer" href="' .rsssl_link() . '">'
			                . __( 'Improve security - Upgrade', 'really-simple-ssl' ) . '</a>';
			array_unshift( $links, $upgrade_link );
		}

		return $links;
	}

	/**
	 * Redirect to the new settings page
	 *
	 * @return void
	 */
	public function maybe_redirect_old_settings_url() {
		if ( ! rsssl_user_can_manage() || is_multisite() ) {
			return;
		}
		if ( isset( $_GET['page'] ) && 'rlrsssl_really_simple_ssl' === $_GET['page'] ) {
			wp_redirect( rsssl_admin_url() );
			exit;
		}
	}

	/**
	 * Add some privacy info, telling our users we aren't tracking them
	 */

	public function add_privacy_info() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content =
			sprintf(
				// translators: %s is replaced with the hyperlink
				__( 'Really Simple Security and Really Simple Security add-ons do not process any personal identifiable information, so the GDPR does not apply to these plugins or usage of these plugins on your website. You can find our privacy policy <a href="%s" rel="noopener noreferrer" target="_blank">here</a>.', 'really-simple-ssl' ),
				'https://really-simple-ssl.com/privacy-statement/'
			);

		wp_add_privacy_policy_content(
			'Really Simple Security',
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Check if current day falls within required date range.
	 *
	 * @return bool
	 */

	public function is_bf() {

		if ( defined( 'rsssl_pro' ) ) {
			return false;
		}

		// Get current date and time in GMT as timestamp
		$current_date = strtotime( gmdate( 'Y-m-d H:i:s' ) );

		// Define the start and end dates for the range in GMT (including specific times)
		$start_date = strtotime( 'November 25 2024 00:00:00 GMT' );
		$end_date   = strtotime( 'December 2 2024 23:59:59 GMT' );

		// Check if the current date and time falls within the date range
		if ( $current_date >= $start_date && $current_date <= $end_date ) {
			return true;
		}

		return false;
	}

	/**
	 * Initializes the admin class
	 *
	 * @since  2.2
	 *
	 * @access public
	 *
	 */

	public function init() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( defined( 'RSSSL_FORCE_ACTIVATE' ) && RSSSL_FORCE_ACTIVATE ) {
			rsssl_update_option( 'ssl_enabled', true );
		}

		/*
		 * check if we're one minute past the activation. Then flush rewrite rules
		 * this way we lower the memory impact on activation
		 * Flush should happen on shutdown, not on init, as often happens in other plugins
		 * https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
		 * */

		$activation_time          = get_option( 'rsssl_flush_rewrite_rules' );
		$more_than_one_minute_ago = $activation_time < strtotime( '-1 minute' );
		$less_than_2_minutes_ago  = $activation_time > strtotime( '-2 minute' );
		if ( $more_than_one_minute_ago && $less_than_2_minutes_ago && get_option( 'rsssl_flush_rewrite_rules' ) ) {
			delete_option( 'rsssl_flush_rewrite_rules' );
			add_action( 'shutdown', 'flush_rewrite_rules' );
		}
		$more_than_2_minute_ago  = get_option( 'rsssl_flush_caches' ) < strtotime( '-2 minute' );
		$less_than_5_minutes_ago = get_option( 'rsssl_flush_caches' ) > strtotime( '-5 minute' );
		if ( $more_than_2_minute_ago && $less_than_5_minutes_ago && get_option( 'rsssl_flush_caches' ) ) {
			delete_option( 'rsssl_flush_caches' );
			add_action( 'shutdown', array( RSSSL()->cache, 'flush' ) );
		}

		/*
			Detect configuration when:
		- on settings page
		- SSL not enabled
		*/

		//when configuration detection should run again
		if ( ! rsssl_get_option( 'ssl_enabled' ) || $this->is_settings_page() || defined( 'RSSSL_DOING_SYSTEM_STATUS' ) ) {
			$this->detect_configuration();
			if ( ! $this->wpconfig_ok() ) {
				rsssl_update_option( 'ssl_enabled', false );
			} else {
				//when one of the used server variables was found, test if the redirect works
				if ( RSSSL()->server->uses_htaccess() && 'NA' !== $this->ssl_type ) {
					$this->htaccess_test_success();
				}
			}
		}
	}

	/**
	 * Add htaccess redirect
	 * @hooked
	 * @param array $rules
	 * @return []
	 */

	public function add_htaccess_redirect( $rules ) {
		//we don't want these rules added by rsssl if wp rocket active.
		//if it's deactivating, start adding them again.
		if ( $this->is_deactivating_wprocket() || ! function_exists( 'rocket_clean_domain' ) ) {
			$rule = $this->get_redirect_rules();
			if ( ! empty( $rule ) ) {
				$rules[] = [
					'rules'      => $rule,
					'identifier' => 'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1',
				];
			}
		}

		return $rules;
	}

	/**
	 * Check if we're in the middle of wp rocket deactivation
	 *
	 * @return bool
	 */
	public function is_deactivating_wprocket() {
		//default deactivating
		$is_deactivating = isset( $_GET['action'] ) && 'deactivate' === $_GET['action'] && isset( $_GET['plugin'] ) && strpos( $_GET['plugin'], 'wp-rocket.php' ) !== false;
		//deactivating with modal
		return $is_deactivating || ( isset( $_GET['action'] ) && 'rocket_deactivation' === $_GET['action'] );
	}

	/**
	 * Deactivate the plugin while keeping SSL
	 * Activated when the 'uninstall_keep_ssl' button is clicked in the settings tab
	 *
	 */

	public function listen_for_deactivation() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( ! isset( $_GET['token'] ) || ( ! wp_verify_nonce( $_GET['token'], 'rsssl_deactivate_plugin' ) ) ) {
			return;
		}

		do_action( 'rsssl_deactivate' );

		rsssl_clear_scheduled_hooks();

		if ( isset( $_GET['action'] ) && 'uninstall_keep_ssl' === $_GET['action'] ) {
			$this->deactivate_plugin();
		}

		if ( isset( $_GET['action'] ) && 'uninstall_revert_ssl' === $_GET['action'] ) {
			// Update site url from https:// to http://
			$this->remove_ssl_from_siteurl();
            if ( ! is_multisite() ) {
	            $this->remove_ssl_from_siteurl_in_wpconfig();
            }
            $this->deactivate_plugin();
		}

		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * @return void
     *
     * Deactivate plugin logic
	 */
    private function deactivate_plugin() {
	    //deactivate plugin, but don't revert to http.
	    $plugin = $this->get_current_rsssl_dirname() . '/' . $this->plugin_filename;
	    $plugin = plugin_basename( trim( $plugin ) );

	    if ( is_multisite() ) {
		    $network_current = get_site_option( 'active_sitewide_plugins', array() );
		    if ( is_plugin_active_for_network( $plugin ) ) {
			    unset( $network_current[ $plugin ] );
		    }
		    update_site_option( 'active_sitewide_plugins', $network_current );
		    //remove plugin one by one on each site
		    $sites = get_sites();
		    foreach ( $sites as $site ) {
			    switch_to_blog( $site->blog_id );
			    $current = get_option( 'active_plugins', array() );
			    $current = $this->remove_plugin_from_array( $plugin, $current );
			    update_option( 'active_plugins', $current );
			    restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
		    }
	    } else {
		    $current = get_option( 'active_plugins', array() );
		    $current = $this->remove_plugin_from_array( $plugin, $current );
		    update_option( 'active_plugins', $current );
	    }
    }

	/**
	 * Remove the plugin from the active plugins array when called from listen_for_deactivation
	 *
	 * */

	public function remove_plugin_from_array( $plugin, $current ) {
		$key = array_search( $plugin, $current, true );
		if ( false !== $key ) {
			unset( $current[ $key ] );
		}
		return $current;
	}

	/**
	 * @return bool
	 *
	 * Check if this site is a Bitnami site
	 */
	public function uses_bitnami(): bool {
		if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && $_SERVER['DOCUMENT_ROOT'] === '/opt/bitnami/wordpress' ) {
            return true;
        }

        return false;
	}

	/**
	 * Check if site uses an htaccess.conf file, used in bitnami installations
	 *
	 * @Since 3.1
	 */

	public function uses_htaccess_conf() {
		$htaccess_conf_file = dirname( ABSPATH ) . '/conf/htaccess.conf';
		//conf/htaccess.conf can be outside of open basedir, return false if so
		$open_basedir = ini_get( 'open_basedir' );
		if ( ! empty( $open_basedir ) ) {
			return false;
		}
		return is_file( $htaccess_conf_file );
	}

	/**
	 * If the user has clicked "recheck certificate, clear the cache for the certificate check.
	 * Used in a form in the dashboard notices.
	 * @return void
	 */
	public function recheck_certificate(): void
    {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
        if ( ! isset($_POST['rsssl_recheck_nonce_field']) ||
            ! wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['rsssl_recheck_nonce_field' ])) , 'rsssl_recheck_nonce')
        ) {
            return; // nonce failed, do not proceed
        }
		if ( isset( $_POST['rsssl_recheck_certificate'] ) ) {
			delete_transient( 'rsssl_certinfo' );
		}
	}

	/**
	 *  Activate the SSL for this site
	 */
    public function activate_ssl($data) {
        //skip activation if safe mode
	    if ( defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE ) {
            return [
                'success'          => true,
                'site_url_changed' => false,
            ];
        }

	    if ( !rsssl_user_can_manage()  ) {
		    return [
			    'success' => false,
			    'site_url_changed' => false,
		    ];
        }

		$safe_mode        = defined( 'RSSSL_SAFE_MODE' ) && RSSSL_SAFE_MODE;
		$error            = false;
		$is_rest_request  = isset( $data['is_rest_request'] );
		$site_url_changed = false;
		$wpcli            = defined( 'WP_CLI' ) && WP_CLI;

		if ( rsssl_get_option( 'site_has_ssl' ) || get_option( 'rsssl_ssl_detection_overridden' ) || $wpcli ) {
			//in a configuration reverse proxy without a set server variable https, add code to wpconfig
			if ( $this->do_wpconfig_loadbalancer_fix || $this->no_server_variable ) {
				$this->wpconfig_loadbalancer_fix();
			}

			if ( ! $safe_mode && $this->wpconfig_siteurl_not_fixed && ! $this->uses_bitnami() ) {
				$this->fix_siteurl_defines_in_wpconfig();
			}

			$this->insert_secure_cookie_settings();
			if ( ! $safe_mode ) {
				rsssl_update_option( 'redirect', 'wp_redirect' );
				rsssl_update_option( 'mixed_content_fixer', true );

				//flush caches when just activated ssl
				//flush the permalinks
				update_option( 'rsssl_activation_timestamp', time(), false );
				if ( ! defined( 'RSSSL_NO_FLUSH' ) || ! RSSSL_NO_FLUSH ) {
					update_option( 'rsssl_flush_rewrite_rules', time(), false );
				}
				update_option( 'rsssl_flush_caches', time(), false );
			}

			rsssl_update_option( 'ssl_enabled', true );
			$site_url_changed = $this->set_siteurl_to_ssl();
			delete_option( 'rsssl_admin_notices' );
		} else {
			$error = true;
		}

		//if this is true, this is a request from the network admin. We save an option to ensure we know that this part is completed
		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			update_site_option( 'rsssl_network_activation_status', 'main_site_activated' );
		}

		if ( $is_rest_request ) {
			return [
				'success'          => ! $error,
				'site_url_changed' => $site_url_changed,
				'request_success'  => true,
			];
		}
		return ! $error;
	}

	/**
	 * Check if the wp config configuration is ok for SSL activation
	 *
	 * @return bool
	 */
	public function wpconfig_ok() {
		//return false;
		if ( ( $this->do_wpconfig_loadbalancer_fix || $this->no_server_variable || $this->wpconfig_siteurl_not_fixed ) && ! $this->wpconfig_is_writable()  && ! $this->uses_bitnami() ) {
			$result = false;
		} else {
			$result = true;
		}
		return apply_filters( 'rsssl_wpconfig_ok_check', $result );
	}

	/**
	 * @param string      $class
	 * @param string      $content
	 * @param string|bool $more_info
	 * @param string|bool $dismiss_id
	 *
	 * @return false|string
	 *
	 * @since 4.0
	 * Return the notice HTML
	 *
	 */

	public function notice_html( string $css_class, string $content, $more_info = false, $logo=false, $dismiss_id = false, $dashboard_button=false ) {
		if ( ! rsssl_user_can_manage() ) {
			return '';
		}

		$css_class       .= ' notice ';
		$is_internal_link = strpos( $more_info, 'really-simple-ssl.com' ) === false;
		$target           = ! $is_internal_link ? 'target="_blank"' : '';
		$url              = is_ssl() ? 'https://' : 'http://';
		$url             .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$url              = wp_validate_redirect( $url, apply_filters( 'wp_safe_redirect_fallback', admin_url(), 302 ) );

		$url = esc_url_raw( $url );
		ob_start();?>
			<style>
				#rsssl-message {
					margin:10px 0;
					padding: 0;
					border-left-color: #333;
				}
				#rsssl-message.error{
					border-left-color:#d7263d;
				}
				#rsssl-message.warning{
					border-left-color:#ffb900;
				}
				.rsssl-notice {
					display:flex;
					margin:15px;
                    flex-direction: column;
				}
                .rsssl-notice-logo {
                    width: 225px;
                }
				.rsssl-notice-content {
					padding-top: 5px;
				}
				.rsssl-admin-notice-more-info {
                    margin-top: 10px;
				}
				.settings_page_really-simple-security #wpcontent #rsssl-message, .settings_page_really-simple-ssl #wpcontent #rsssl-message {
					margin: 20px;
				}
                #rsssl-dashboard-button {
                    margin-right: 5px;
                }
			</style>
			<?php if ( is_rtl() ) { ?>
				<style>
					#rsssl-message {
						border-right-color: #333;
					}
					#rsssl-message.error{
						border-right-color:#d7263d;
					}
				</style>
			<?php } ?>
		<div id="rsssl-message" class="<?php echo esc_attr( $css_class ); ?> really-simple-plugins">
			<div class="rsssl-notice">
                <?php if ( $logo ) { ?>
                    <div class="rsssl-notice-logo">
                        <img src="<?php echo esc_url( trailingslashit( rsssl_url ) . 'assets/img/really-simple-security-logo.svg' ); ?>" alt="Really Simple Security">
                    </div>
                <?php } ?>
				<div class="rsssl-notice-content">
                    <?php echo $content; ?>
				</div>
				<?php if ( $more_info || $dismiss_id ) { ?>
					<div class="rsssl-admin-notice-more-info">
						<?php if ( $dashboard_button ) { ?>
                            <a id="rsssl-dashboard-button" class="button button-primary" rel="noopener noreferrer" href="<?php echo add_query_arg(array('page' => 'really-simple-security', 'dismiss_notice' => $dismiss_id), rsssl_admin_url() ); ?>"><?php _e( 'View Dashboard', 'really-simple-ssl' ); ?></a>
						<?php } ?>
						<?php if ( $dismiss_id ) { ?>
							<a class="button" rel="noopener noreferrer" href="<?php echo add_query_arg( [ 'dismiss_notice' => $dismiss_id ], $url ); ?>"><?php _e( 'Dismiss', 'really-simple-ssl' ); ?></a>
						<?php } ?>
						<?php if ( $more_info ) { ?>
							<a class="button" <?php echo $target; ?> rel="noopener noreferrer" href="<?php echo esc_url_raw( $more_info ); ?>"><?php $is_internal_link ? _e( 'View', 'really-simple-ssl' ) : _e( 'More info', 'really-simple-ssl' ); ?></a>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @return bool
	 *
	 * Check if wp-config.php is writeable
	 *
	 * @access public
	 */

	public function wpconfig_is_writable() {
		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return false;
		}

		if ( is_writable( $wpconfig_path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the uninstall file is renamed to .php
	 *
	 * @return string
	 */

	public function check_for_uninstall_file() {
		if ( file_exists( __DIR__ . '/force-deactivate.php' ) ) {
			return 'fail';
		}
		return 'success';
	}

	/**
	 * Check to see if we are on the settings page, action hook independent
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 */

	public function is_settings_page() {
		if ( rsssl_is_logged_in_rest() ) {
			return true;
		}

		if ( ! isset( $_SERVER['QUERY_STRING'] ) ) {
			return false;
		}

		if ( isset( $_GET['action'] ) && 'rsssl_rest_api_fallback' === $_GET['action'] ) {
			return true;
		}

		parse_str( $_SERVER['QUERY_STRING'], $params );
		return array_key_exists( 'page', $params ) && 'really-simple-security' === $params['page'];
	}

	/**
	 * remove https from defined siteurl and homeurl in the wpconfig, if present
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 * @return void
	 */

	public function remove_ssl_from_siteurl_in_wpconfig() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$wpconfig_path = $this->wpconfig_path();
		if ( ! empty( $wpconfig_path ) ) {
			$wpconfig    = file_get_contents( $wpconfig_path );
			$homeurl_pos = strpos( $wpconfig, "define('WP_HOME','https://" );
			$siteurl_pos = strpos( $wpconfig, "define('WP_SITEURL','https://" );

			if ( false !== $homeurl_pos || false !== $siteurl_pos ) {
				if ( is_writable( $wpconfig_path ) ) {
					$search_array = array( "define('WP_HOME','https://", "define('WP_SITEURL','https://" );
					$ssl_array    = array( "define('WP_HOME','http://", "define('WP_SITEURL','http://" );
					//now replace these urls
					$wpconfig = str_replace( $search_array, $ssl_array, $wpconfig );
					file_put_contents( $wpconfig_path, $wpconfig );
				}
			}
		}
	}


	/**
	 * Checks if the wp config contains any defined siteurl and homeurl
	 *
	 * @return void
	 */
	private function check_for_siteurl_in_wpconfig() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return;
		}

		$wpconfig        = file_get_contents( $wpconfig_path );
		$homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
		$siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

		$this->wpconfig_siteurl_not_fixed = false;
		if ( preg_match( $homeurl_pattern, $wpconfig ) || preg_match( $siteurl_pattern, $wpconfig ) ) {
			$this->wpconfig_siteurl_not_fixed = true;
		}
	}


	/**
	 * Runs only when siteurl or homeurl define was found in the wpconfig, with the check_for_siteurl_in_wpconfig function
	 * and only when wpconfig is writable.
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 */

	private function fix_siteurl_defines_in_wpconfig() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return;
		}

		$wpconfig        = file_get_contents( $wpconfig_path );
		$homeurl_pattern = '/(define\(\s*\'WP_HOME\'\s*,\s*\'http\:\/\/)/';
		$siteurl_pattern = '/(define\(\s*\'WP_SITEURL\'\s*,\s*\'http\:\/\/)/';

		if ( preg_match( $homeurl_pattern, $wpconfig ) || preg_match( $siteurl_pattern, $wpconfig ) ) {
			if ( is_writable( $wpconfig_path ) ) {
				$wpconfig = preg_replace( $homeurl_pattern, "define('WP_HOME','https://", $wpconfig );
				$wpconfig = preg_replace( $siteurl_pattern, "define('WP_SITEURL','https://", $wpconfig );
				file_put_contents( $wpconfig_path, $wpconfig );
			} else {
				//only when siteurl or homeurl is defined in wpconfig, and wpconfig is not writable is there a possible issue because we cannot edit the defined urls.
				$this->wpconfig_siteurl_not_fixed = true;
			}
		}
	}


	/**
	 * Check if the wpconfig is already fixed
	 *
	 * @since  2.2
	 *
	 * @access public
	 *
	 */

	public function wpconfig_has_fixes() {
		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return false;
		}
		$wpconfig = file_get_contents( $wpconfig_path );

		//only one of two fixes possible.
		if ( strpos( $wpconfig, '//Begin Really Simple Security Load balancing fix' ) !== false ) {
			return true;
		}

		if ( strpos( $wpconfig, '//Begin Really Simple Security Server variable fix' ) !== false ) {
			return true;
		}

		return false;
	}


	/**
	 * In case of load balancer without server https on, add fix in wp-config
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 */


	public function wpconfig_loadbalancer_fix() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return;
		}
		$wpconfig = file_get_contents( $wpconfig_path );
		if ( strpos( $wpconfig, '//Begin Really Simple Security Server variable fix' ) !== false ) {
			return;
		}

		if ( strpos( $wpconfig, '//Begin Really Simple Security Load balancing fix' ) !== false ) {
			return;
		}

		if ( is_writable( $wpconfig_path ) ) {
			$rule  = "\n" . '//Begin Really Simple Security Server variable fix' . "\n";
			$rule .= '$_SERVER["HTTPS"] = "on";' . "\n";
			$rule .= '//END Really Simple Security Server variable fix' . "\n";

			$insert_after = '<?php';
			$pos          = strpos( $wpconfig, $insert_after );
			if ( false !== $pos ) {
				$wpconfig = substr_replace( $wpconfig, $rule, $pos + 1 + strlen( $insert_after ), 0 );
			}

			file_put_contents( $wpconfig_path, $wpconfig );
		}
	}

	/**
	 * Removing changes made to the wpconfig
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 */

	public function remove_wpconfig_edit() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return;
		}

		//check for permissions
		$wpconfig = file_get_contents( $wpconfig_path );
		if ( ! is_writable( $wpconfig_path ) ) {
			return;
		}

		//remove edits
		$wpconfig = preg_replace( '/\/\/Begin\s?Really\s?Simple\s?SSL\s?Server\s?variable\s?fix.*?\/\/END\s?Really\s?Simple\s?SSL\s?Server\s?variable\s?fix/s', '', $wpconfig );
		$wpconfig = preg_replace( "/\n+/", "\n", $wpconfig );
		file_put_contents( $wpconfig_path, $wpconfig );
	}

	/**
	 * Changes the siteurl and homeurl to https
	 *
	 * @since  2.0
	 *
	 * @access public
	 * @return bool
	 */

	public function set_siteurl_to_ssl() {
		$site_url_changed = false;
		$site_url         = get_option( 'siteurl' );
		$home_url         = get_option( 'home' );
		if ( strpos( $site_url, 'https://' ) === false || strpos( $home_url, 'https://' ) === false ) {
			update_option( 'siteurl', str_replace( 'http://', 'https://', $site_url ) );
			update_option( 'home', str_replace( 'http://', 'https://', $home_url ) );
			$site_url_changed = true;
		}

		//RSSSL has it's own, more extensive mixed content fixer.
		update_option( 'https_migration_required', false );
		return $site_url_changed;
	}

	/**
	 * On de-activation, siteurl and homeurl are reset to http
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function remove_ssl_from_siteurl() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		$siteurl_no_ssl = str_replace( 'https://', 'http://', get_option( 'siteurl' ) );
		$homeurl_no_ssl = str_replace( 'https://', 'http://', get_option( 'home' ) );
		update_option( 'siteurl', $siteurl_no_ssl );
		update_option( 'home', $homeurl_no_ssl );
	}

	/**
	 * Handles deactivation of this plugin
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function deactivate() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

        if ( get_option('rsssl_free_deactivated') ) {
            // Deactivation call from upgrade to Pro. Do not disable features
            return;
        }

		if ( is_multisite() ) {
			RSSSL()->multisite->deactivate();
		} else {
			$ssl_was_enabled = rsssl_get_option( 'ssl_enabled' );
			$this->deactivate_site( $ssl_was_enabled );
		}
	}

	/**
	 * Deactivate SSL for the currently loaded site
	 *
	 * @param bool $ssl_was_enabled
	 *
	 * @return void
	 */
	public function deactivate_site( bool $ssl_was_enabled ) {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		$this->remove_secure_cookie_settings();
		if ( $ssl_was_enabled ) {
			$this->remove_ssl_from_siteurl();
			if ( ! is_multisite() || is_main_site() ) {
				$this->remove_ssl_from_siteurl_in_wpconfig();
				$this->remove_wpconfig_edit();
				rsssl_remove_htaccess_security_edits();
			}
		}

		do_action( 'rsssl_deactivate' );
		rsssl_update_option( 'ssl_enabled', false );
	}

	/**
	 * remove secure cookie settings
	 *
	 * @since  4.0.10
	 *
	 * @access public
	 *
	 */

	public function remove_secure_cookie_settings() {

		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( $this->secure_cookie_settings_status() !== 'set' ) {
			return;
		}

		$wpconfig_path = $this->wpconfig_path();
		if ( empty( $wpconfig_path ) ) {
			return;
		}

		if ( ! is_writable( $wpconfig_path ) ) {
			return;
		}

		if ( ! empty( $wpconfig_path ) ) {
			$wpconfig = file_get_contents( $wpconfig_path );
			$wpconfig = preg_replace( '/\/\/Begin\s?Really\s?Simple\s?SSL\s?session\s?cookie\s?settings.*?\/\/END\s?Really\s?Simple\s?SSL\s?cookie\s?settings/s', '', $wpconfig );
			$wpconfig = preg_replace( "/\n+/", "\n", $wpconfig );
			file_put_contents( $wpconfig_path, $wpconfig );
		}
	}


	/**
	 * Checks if we are currently on SSL protocol, but extends standard wp with loadbalancer check.
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function is_ssl_extended() {
		$server_var = false;
		if ( ( isset( $_ENV['HTTPS'] ) && ( 'on' === $_ENV['HTTPS'] ) )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && ( strpos( $_SERVER['HTTP_X_FORWARDED_SSL'], '1' ) !== false ) )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && ( strpos( $_SERVER['HTTP_X_FORWARDED_SSL'], 'on' ) !== false ) )
			|| ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && ( strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) !== false ) )
			|| ( isset( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) && ( strpos( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'], 'https' ) !== false ) )
			|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) !== false ) )
			|| ( isset( $_SERVER['HTTP_X_PROTO'] ) && ( strpos( $_SERVER['HTTP_X_PROTO'], 'SSL' ) !== false ) )
		) {
			$server_var = true;
		}

		return is_ssl() || $server_var;
	}

	/**
	 * Checks for SSL by opening a test page in the plugin directory
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function detect_configuration() {
		$this->configuration_loaded = true;
		//if current page is on SSL, we can assume SSL is available, even when an errormsg was returned
		if ( $this->is_ssl_extended() ) {
			$site_has_ssl = true;
		} else {
			//if certificate is valid
			$site_has_ssl = RSSSL()->certificate->is_valid();
		}

		if ( $site_has_ssl ) {
			$filecontents = $this->get_test_page_contents();
			//get filecontents to check .htaccess redirection method and wpconfig fix
			//check the type of SSL, either by parsing the returned string, or by reading the server vars.
			if ( ( false !== strpos( $filecontents, '#CLOUDFRONT#' ) ) || ( isset( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) && ( 'https' === $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) ) ) {
				$this->ssl_type = 'CLOUDFRONT';
			} elseif ( ( false !== strpos( $filecontents, '#CLOUDFLARE#' ) ) || ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && ( false !== strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) ) ) ) {
				$this->ssl_type = 'CLOUDFLARE';
			} elseif ( ( false !== strpos( $filecontents, '#LOADBALANCER#' ) ) || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ) {
				$this->ssl_type = 'LOADBALANCER';
			} elseif ( ( false !== strpos( $filecontents, '#HTTP_X_PROTO#' ) ) || ( isset( $_SERVER['HTTP_X_PROTO'] ) && ( 'SSL' === $_SERVER['HTTP_X_PROTO'] ) ) ) {
				$this->ssl_type = 'HTTP_X_PROTO';
			} elseif ( ( false !== strpos( $filecontents, '#HTTP_X_FORWARDED_SSL_ON#' ) ) || ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && 'on' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) {
				$this->ssl_type = 'HTTP_X_FORWARDED_SSL_ON';
			} elseif ( ( false !== strpos( $filecontents, '#HTTP_X_FORWARDED_SSL_1#' ) ) || ( isset( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && '1' === $_SERVER['HTTP_X_FORWARDED_SSL'] ) ) {
				$this->ssl_type = 'HTTP_X_FORWARDED_SSL_1';
			} elseif ( ( false !== strpos( $filecontents, '#SERVER-HTTPS-ON#' ) ) || ( isset( $_SERVER['HTTPS'] ) && 'on' === strtolower( $_SERVER['HTTPS'] ) ) ) {
				$this->ssl_type = 'SERVER-HTTPS-ON';
			} elseif ( ( false !== strpos( $filecontents, '#SERVER-HTTPS-1#' ) ) || ( isset( $_SERVER['HTTPS'] ) && '1' === strtolower( $_SERVER['HTTPS'] ) ) ) {
				$this->ssl_type = 'SERVER-HTTPS-1';
			} elseif ( ( false !== strpos( $filecontents, '#SERVERPORT443#' ) ) || ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' === $_SERVER['SERVER_PORT'] ) ) ) {
				$this->ssl_type = 'SERVERPORT443';
			} elseif ( ( false !== strpos( $filecontents, '#ENVHTTPS#' ) ) || ( isset( $_ENV['HTTPS'] ) && ( 'on' === $_ENV['HTTPS'] ) ) ) {
				$this->ssl_type = 'ENVHTTPS';
			} elseif ( ( false !== strpos( $filecontents, '#NO KNOWN SSL CONFIGURATION DETECTED#' ) ) ) {
				//if we are here, SSL was detected, but without any known server variables set.
				//So we can use this info to set a server variable ourselves.
				if ( ! $this->wpconfig_has_fixes() ) {
					$this->no_server_variable = true;
				}
				$this->ssl_type = 'NA';
			} else {
				//no valid response, so set to NA
				$this->ssl_type = 'NA';
			}

			//check for is_ssl()
			if ( ( ! $this->is_ssl_extended() &&
					( strpos( $filecontents, '#SERVER-HTTPS-ON#' ) === false ) &&
					( strpos( $filecontents, '#SERVER-HTTPS-1#' ) === false ) &&
					( strpos( $filecontents, '#SERVERPORT443#' ) === false )
				) ||
				( ! is_ssl() && $this->is_ssl_extended() ) ) {
				//when is_ssl would return false, we should add some code to wp-config.php
				if ( ! $this->wpconfig_has_fixes() ) {
					$this->do_wpconfig_loadbalancer_fix = true;
				}
			}
		}
		$this->check_for_siteurl_in_wpconfig();
        //check againt current status, to prevent unnecessary loading of fields array during update_option
        $current_ssl_status = rsssl_get_option( 'site_has_ssl' );
        if ( (bool) $current_ssl_status !== (bool) $site_has_ssl ) {
	        rsssl_update_option( 'site_has_ssl', $site_has_ssl );
        }

	}

	/**
	 * Test if the htaccess redirect will work
	 * This way, no redirect loops should occur.
	 *
	 * @since  2.1
	 *
	 * @access public
	 *
	 */

	public function htaccess_test_success() {
		$test = get_transient( 'rsssl_htaccess_test_success' );
		if ( ! $test ) {
			$filecontents = '';
			$testpage_url = trailingslashit( $this->test_url() ) . 'testssl/';
			switch ( $this->ssl_type ) {
				case 'CLOUDFRONT':
					$testpage_url .= 'cloudfront';
					break;
				case 'CLOUDFLARE':
					$testpage_url .= 'cloudflare';
					break;
				case 'LOADBALANCER':
					$testpage_url .= 'loadbalancer';
					break;
				case 'HTTP_X_PROTO':
					$testpage_url .= 'serverhttpxproto';
					break;
				case 'HTTP_X_FORWARDED_SSL_ON':
					$testpage_url .= 'serverhttpxforwardedsslon';
					break;
				case 'HTTP_X_FORWARDED_SSL_1':
					$testpage_url .= 'serverhttpxforwardedssl1';
					break;
				case 'SERVER-HTTPS-ON':
					$testpage_url .= 'serverhttpson';
					break;
				case 'SERVER-HTTPS-1':
					$testpage_url .= 'serverhttps1';
					break;
				case 'SERVERPORT443':
					$testpage_url .= 'serverport443';
					break;
				case 'ENVHTTPS':
					$testpage_url .= 'envhttps';
					break;
				default:
					$testpage_url .= 'serverhttpson';
			}

			$testpage_url .= ( '/ssl-test-page.html' );
			$response      = wp_remote_get( $testpage_url );
			if ( is_array( $response ) ) {
				$filecontents = wp_remote_retrieve_body( $response );
			}

			if ( ! is_wp_error( $response ) && ( strpos( $filecontents, '#SSL TEST PAGE#' ) !== false ) ) {
				$test = 'success';
			} else {
				//.htaccess rewrite rule seems to be giving problems.
				$test = 'error';
			}
			if ( empty( $filecontents ) ) {
				$test = 'no-response';
			}
			set_transient( 'rsssl_htaccess_test_success', $test, 600 );
		}

		if ( 'no-response' === $test || 'error' === $test ) {
			return false;
		}

		if ( 'success' === $test ) {
			return true;
		}
	}


	/**
	 * Get an url with which we can test the SSL connection and htaccess redirect rules.
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function test_url() {
		$plugin_url     = str_replace( 'http://', 'https://', trailingslashit( rsssl_url ) );
		$https_home_url = str_replace( 'http://', 'https://', home_url() );

		//in some case we get a relative url here, so we check that.
		//we compare to urls replaced to https, in case one of them is still on http.
		if ( ( strpos( $plugin_url, 'https://' ) === false ) &&
			( strpos( $plugin_url, $https_home_url ) === false )
		) {
			//make sure we do not have a slash at the start
			$plugin_url = ltrim( $plugin_url, '/' );
			$plugin_url = trailingslashit( home_url() ) . $plugin_url;
		}

		//for subdomains or domain mapping situations, we have to convert the plugin_url from main site to the subdomain url.
		if ( is_multisite() && ! is_main_site( get_current_blog_id() ) && ! RSSSL()->multisite->is_multisite_subfolder_install() ) {
			$mainsiteurl = trailingslashit( str_replace( 'http://', 'https://', network_site_url() ) );
			$home        = trailingslashit( $https_home_url );
			$plugin_url  = str_replace( $mainsiteurl, $home, $plugin_url );
		}

		return $plugin_url;
	}

	/**
	 * @return bool
	 *
	 * Check if the .htaccess redirect is allowed on this setup
	 *
	 * @since 2.0
	 *
	 */

	public function htaccess_redirect_allowed() {
		if ( is_multisite() && ! $this->can_apply_networkwide() ) {
			return false;
		} if ( RSSSL()->server->uses_htaccess() ) {
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 *
	 * Checks if the htaccess contains redirect rules, either actual redirect or a rsssl marker.
	 *
	 * @since 2.0
	 *
	 */

	public function htaccess_contains_redirect_rules() {
		if ( ! file_exists( $this->htaccess_file() ) ) {
			return false;
		}

		$pattern  = '/RewriteRule \^\(\.\*\)\$ https:\/\/%{HTTP_HOST}(\/\$1|%{REQUEST_URI}) (\[R=301,.*L\]|\[L,.*R=301\])/i';
		$htaccess = file_get_contents( $this->htaccess_file() );
		return preg_match( $pattern, $htaccess );
	}

	/**
	 * @return bool
	 *
	 * Checks if a 301 redirect is set
	 * this is the case if either the wp_redirect is set, or the htaccess redirect is set.
	 *
	 */

	public function has_301_redirect() {
		if ( 'htaccess' === rsssl_get_option( 'redirect' ) || 'wp_redirect' === rsssl_get_option( 'redirect' ) ) {
			return true;
		}

		if ( RSSSL()->server->uses_htaccess() && $this->htaccess_contains_redirect_rules() ) {
			return true;
		}

		return false;
	}

	/**
	 * returns list of recommended, but not active security headers for this site
	 * returns empty array if no .htacces file exists
	 * Uses cURL, fallback to .htaccess check upon cURL failure
	 * @return array
	 *
	 * @since  4.0
	 *
	 * @access public
	 *
	 */

	public function get_recommended_security_headers() {

		$used_headers     = array();
		$not_used_headers = array();
		$check_headers    = apply_filters(
			'rsssl_recommended_security_headers',
			array(
				array(
					'name'    => 'Upgrade Insecure Requests',
					'pattern' => 'upgrade-insecure-requests',
				),
				array(
					'name'    => 'X-XSS protection',
					'pattern' => 'X-XSS-Protection',
				),
				array(
					'name'    => 'X-Content Type Options',
					'pattern' => 'X-Content-Type-Options',
				),
				array(
					'name'    => 'Referrer-Policy',
					'pattern' => 'Referrer-Policy',
				),
				array(
					'name'    => 'Permissions-Policy',
					'pattern' => 'Permissions-Policy',
				),
				array(
					'name'    => 'HTTP Strict Transport Security',
					'pattern' => 'Strict-Transport-Security',
				),
			)
		);

		// cURL check.
		$curl_check_done = get_transient( 'rsssl_can_use_curl_headers_check' );//no, yes or false
		if ( ! $curl_check_done ) {
			//set a default
			set_transient( 'rsssl_can_use_curl_headers_check', 'no', WEEK_IN_SECONDS );
			if ( function_exists( 'curl_init' ) ) {
				$url     = get_site_url();
				$ch      = curl_init();
				$headers = [];
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_TIMEOUT, 3 ); //timeout in seconds
				curl_setopt(
					$ch,
					CURLOPT_HEADERFUNCTION,
					function ( $curl, $header ) use ( &$headers ) {
						$len    = strlen( $header );
						$header = explode( ':', $header, 2 );
						if ( count( $header ) < 2 ) {
							return $len;
						}

						$headers[ strtolower( trim( $header[0] ) ) ][] = trim( $header[1] );

						return $len;
					}
				);

				curl_exec( $ch );
				// Check if any headers have been found
				if ( ! empty( $headers ) && is_array( $headers ) ) {

					// Loop through each header and check if it's one of the recommended security headers. If so, add to used_headers array.
					foreach ( $headers as $name => $value ) {
						foreach ( $check_headers as $check_header ) {
							// If the pattern occurs in either the header name or value, it's a security header.
							if ( stripos( $name, $check_header['pattern'] ) !== false || stripos( $value[0], $check_header['pattern'] ) !== false ) {
								// Prevent duplicate entries
								if ( ! in_array( $check_header['name'], $used_headers, true ) ) {
									$used_headers[] = $check_header['name'];
								}
							}
						}
					}

					// Now check which headers are unused. Compare the used headers against the $check_headers array.
					foreach ( $check_headers as $header ) {
						if ( in_array( $header['name'], $used_headers, true ) ) {
							// Header is used, do not add to unused array
							continue;
						} else {
							// Header is not used. Add to not used array
							$not_used_headers[] = $header['name'];
						}
					}
					$curl_check_done = $not_used_headers;
				} else {
					$curl_check_done = 'no';
				}
			} else {
				$curl_check_done = 'no';
			}
			set_transient( 'rsssl_can_use_curl_headers_check', $curl_check_done, WEEK_IN_SECONDS );
		}

		if ( 'no' === $curl_check_done ) {
			if ( RSSSL()->server->uses_htaccess() && file_exists( $this->htaccess_file() ) ) {
				$htaccess = file_get_contents( $this->htaccess_file() );
				foreach ( $check_headers as $check_header ) {
					if ( ! preg_match( '/' . $check_header['pattern'] . '/', $htaccess, $check ) ) {
						$not_used_headers[] = $check_header['name'];
					}
				}
			}
		} else {
			$not_used_headers = $curl_check_done;
		}

		return $not_used_headers;
	}

	/**
	 * Check if the recommended headers are enabled
	 *
	 * @return bool
	 */

	public function recommended_headers_enabled() {

		$unused_headers = $this->get_recommended_security_headers();
		if ( empty( $unused_headers ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Regenerate the wp rocket .htaccess rules
	 */

	public function maybe_flush_wprocket_htaccess( $field_id, $field_value, $prev_value, $field_type ) {
		if ( 'redirect' === $field_id && $field_value !== $prev_value && rsssl_user_can_manage() ) {
			if ( function_exists( 'flush_rocket_htaccess' ) ) {
				flush_rocket_htaccess();
			}

			if ( function_exists( 'rocket_generate_config_file' ) ) {
				rocket_generate_config_file();
			}
		}
	}

	/**
	 * Return .htaccess redirect when using WP Rocket
	 * @return string
	 */
	public function add_htaccess_redirect_before_wp_rocket() {
		$rules = $this->get_redirect_rules();
		if ( ! empty( $rules ) ) {
			$start = "\n" . '#Begin Really Simple Security Redirect';
			$end   = "\n" . '#End Really Simple Security Redirect' . "\n";
			$rules = $start . $rules . $end;
		}
		return $rules;
	}

	/**
	 * Check if the mixed content fixer is functioning on the front end, by scanning the source of the homepage for the fixer comment.
	 * @since 2.2
	 * @access public
	 * @return string
	 */

	public function mixed_content_fixer_detected() {

		//no need to check for the mixed content fixer if it's not enabled yet.
		if ( ! rsssl_get_option( 'mixed_content_fixer' ) ) {
			return 'not-enabled';
		}

		//it's enabled, so check if we can find it on the front-end.
		$status = 0;
		$result = get_transient( 'rsssl_mixed_content_fixer_detected' );
		if ( ! $result ) {
			$web_source = '';
			//check if the mixed content fixer is active
			$response = wp_remote_get( home_url() );
			if ( ! is_wp_error( $response ) ) {
				if ( is_array( $response ) ) {
					$status     = wp_remote_retrieve_response_code( $response );
					$web_source = wp_remote_retrieve_body( $response );
				}

				if ( 200 !== $status ) {
					//Could not connect to website
					$result = 'no-response';
				} elseif ( strpos( $web_source, 'data-rsssl=' ) === false ) {
					//Mixed content fixer marker not found in the websource
					$result = 'not-found';
				} else {
					$result = 'found';
				}
			}

			if ( is_wp_error( $response ) ) {
				//Fallback since most errors will be cURL errors, Error encountered while retrieving the webpage.
				$result = 'error';
				$error  = $response->get_error_message();
				set_transient( 'rsssl_curl_error', $error, DAY_IN_SECONDS );
				if ( ! empty( $error ) && ( strpos( $error, 'cURL error' ) !== false ) ) {
					$result = 'curl-error';
				}
			}
			set_transient( 'rsssl_mixed_content_fixer_detected', $result, 600 );
		}

		return 'found' === $result;
	}

	/**
	 * Create redirect rules for the .htaccess.
	 * @since  2.1
	 *
	 * @access public
	 *
	 * @param bool $manual
	 *
	 * @return string
	 */

	public function get_redirect_rules( $manual = false ) {
		//ensure the configuration check has run always.
		if ( ! $this->configuration_loaded ) {
			$this->detect_configuration();
		}
		//only add the redirect rules when a known type of SSL was detected. Otherwise, we use https.
		$rule = '';
		//if the htaccess test was successfull, and we know the redirect type, edit
		if (
				rsssl_get_option( 'ssl_enabled' ) &&
				'htaccess' === rsssl_get_option( 'redirect' ) &&
				( $manual || $this->htaccess_test_success() ) &&
				'NA' !== $this->ssl_type
		) {
			$rule .= "\n" . '<IfModule mod_rewrite.c>' . "\n";
			$rule .= 'RewriteEngine on' . "\n";
			if ( 'SERVER-HTTPS-ON' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTPS} !=on [NC]' . "\n";
			} elseif ( 'SERVER-HTTPS-1' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTPS} !=1' . "\n";
			} elseif ( 'LOADBALANCER' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTP:X-Forwarded-Proto} !https' . "\n";
			} elseif ( 'HTTP_X_PROTO' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTP:X-Proto} !SSL' . "\n";
			} elseif ( 'CLOUDFLARE' === $this->ssl_type ) {
				$rule .= "RewriteCond %{HTTP:CF-Visitor} '" . '"scheme":"http"' . "'" . "\n";//some concatenation to get the quotes right.
			} elseif ( 'SERVERPORT443' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{SERVER_PORT} !443' . "\n";
			} elseif ( 'CLOUDFRONT' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTP:CloudFront-Forwarded-Proto} !https' . "\n";
			} elseif ( 'HTTP_X_FORWARDED_SSL_ON' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTP:X-Forwarded-SSL} !on' . "\n";
			} elseif ( 'HTTP_X_FORWARDED_SSL_1' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{HTTP:X-Forwarded-SSL} !=1' . "\n";
			} elseif ( 'ENVHTTPS' === $this->ssl_type ) {
				$rule .= 'RewriteCond %{ENV:HTTPS} !=on' . "\n";
			}

			//fastest cache compatibility
			if ( class_exists( 'WpFastestCache' ) ) {
				$rule .= 'RewriteCond %{REQUEST_URI} !wp-content\/cache\/(all|wpfc-mobile-cache)' . "\n";
			}

			//Exclude .well-known/acme-challenge for Let's Encrypt validation
			if ( $this->has_acme_challenge_directory() ) {
				$rule .= 'RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/' . "\n";
			}

			$rule .= 'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]' . "\n";
			$rule .= '</IfModule>' . "\n";
		}

		$rule = apply_filters( 'rsssl_htaccess_output', $rule );
		return preg_replace( "/\n+/", "\n", $rule );
	}

	/**
	 *
	 * @return bool
	 * since 3.1
	 * Check if .well-known/acme-challenge directory exists
	 * @access public
	 */

	public function has_acme_challenge_directory() {
		if ( file_exists( "$this->abs_path.well-known/acme-challenge" ) ) {
			return true;
		}
		return false;
	}

	/**
	 *
	 * @return bool
	 * since 3.1
	 * Check if there are already .well-known rules in .htaccess file
	 * @access public
	 *
	 */

	public function has_well_known_needle() {
		$file = $this->htaccess_file();
		if ( ! file_exists( $file ) ) {
			return false;
		}
		$htaccess          = file_get_contents( $file );
		$well_known_needle = 'RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/';

		if ( strpos( $htaccess, $well_known_needle ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Shows a notice, asking users for a review.
	 */

	public function show_leave_review_notice() {
		if ( defined( 'rsssl_pro' ) ) {
			return;
		}

		if ( rsssl_get_option( 'dismiss_all_notices' ) ) {
			return;
		}
		//      update_option('rsssl_activation_timestamp', strtotime('-2 month'), false );
		//      rsssl_update_option('review_notice_shown', false);
		//prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			return;
		}

		//this user has never had the review notice yet.
		if ( rsssl_get_option( 'ssl_enabled' ) && ! get_option( 'rsssl_activation_timestamp' ) ) {
			$month               = rand( 0, 11 );
			$trigger_notice_date = time() + $month * MONTH_IN_SECONDS;
			update_option( 'rsssl_activation_timestamp', $trigger_notice_date, false );
			update_option( 'rsssl_before_review_notice_user', true, false );
		}

        $reviewNoticeHasNotBeenShownBefore = (rsssl_get_option( 'review_notice_shown' ) === false );
        $activationLongerThanOneMonthAgo = (
                get_option( 'rsssl_activation_timestamp' )
                && (get_option( 'rsssl_activation_timestamp' ) < strtotime( '-1 month' ))
        );

		if ( $reviewNoticeHasNotBeenShownBefore && $activationLongerThanOneMonthAgo ) {

			//checking legacy options, just in case.
			$options = get_option( 'rlrsssl_options' );
			if ( is_array( $options ) && isset( $options['review_notice_shown'] ) && $options['review_notice_shown'] ) {
				rsssl_update_option( 'review_notice_shown', true );
				return;
			}

			add_action( 'admin_print_footer_scripts', array( $this, 'insert_dismiss_review' ) );
			?>

			<style>
				.rsssl-review {
					border-left:4px solid #333
				}
				.rsssl-review .rsssl-container {
					display: flex;
					padding:12px;
				}
				.rsssl-review .rsssl-container .dashicons {
					margin-right:5px;
					margin-left:15px;
				}
				.rsssl-review .rsssl-review-image img{
					margin-top:0.5em;
				}
				.rsssl-review .rsssl-buttons-row {
					margin-top:10px;
					display: flex;
					align-items: center;
				}
			</style>
			<?php if ( is_rtl() ) { ?>
				<style>
					.rsssl-review .rsssl-container .dashicons {
						margin-left:5px;
						margin-right:15px;
					}
					.rsssl-review {
						border-right:4px solid #333
					}
				</style>
			<?php } ?>
			<div id="message" class="updated fade notice is-dismissible rsssl-review really-simple-plugins">
				<div class="rsssl-container">
					<div class="rsssl-review-image"><img width=80px" src="<?php echo rsssl_url; ?>/assets/img/icon.png" alt="review-logo"></div>
					<div style="margin-left:30px">
						<?php if ( get_option( 'rsssl_before_review_notice_user' ) ) { ?>
							<p>
							<?php
								// translators: %1$ and %2$ are replaced with opening and closing a tag containing hyperlink
								printf( __( 'Hi, Really Simple Security has kept your site secure for some time now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %1$smessage%2$s.', 'really-simple-ssl' ), '<a href="https://really-simple-ssl.com/contact"  rel="noopener noreferrer"  target="_blank">', '</a>' );
							?>
								</p>
						<?php } else { ?>
							<p>
							<?php
								// translators: %1$ and %2$ are replaced with opening and closing a tag containing hyperlink
								printf( __( 'Hi, Really Simple Security has kept your site secure for a month now, awesome! If you have a moment, please consider leaving a review on WordPress.org to spread the word. We greatly appreciate it! If you have any questions or feedback, leave us a %1$smessage%2$s.', 'really-simple-ssl' ), '<a href="https://really-simple-ssl.com/contact"  rel="noopener noreferrer"  target="_blank">', '</a>' );
							?>
								</p>
						<?php } ?>

						<i>- Rogier</i>
						<div class="rsssl-buttons-row">
							<a class="button button-primary" target="_blank" rel="noopener noreferrer"
							   href="https://wordpress.org/support/plugin/really-simple-ssl/reviews/#new-post"><?php _e( 'Leave a review', 'really-simple-ssl' ); ?></a>
							<div class="dashicons dashicons-calendar"></div><a rel="noopener noreferrer" href="
							<?php
							echo esc_url( rsssl_admin_url(['rsssl_review_notice' => 'later']) );
							?>
																						"><?php _e( 'Maybe later', 'really-simple-ssl' ); ?></a>
							<div class="dashicons dashicons-no-alt"></div><a rel="noopener noreferrer" href="
							<?php echo esc_url(	rsssl_admin_url(['rsssl_review_notice' => 'dismiss']) ); ?>
																					"><?php _e( 'Don\'t show again', 'really-simple-ssl' ); ?></a>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Insert some ajax script to dismiss the review notice, and stop nagging about it
	 *
	 * @since  3.0
	 *
	 * @access public
	 */

	public function insert_dismiss_review() {

		?>
		<script>
			document.addEventListener('click', e => {
				if ( e.target.closest('.rsssl-review.notice.is-dismissible .notice-dismiss') ) {
					window.location.href='<?php echo esc_url_raw(rsssl_admin_url(['rsssl_review_notice' => 'dismiss']) );?>';
				}
			});
		</script>
		<?php
	}

	/**
	 * Dismiss review notice of dismissed by the user
	 */

	public function maybe_dismiss_review_notice() {
		if ( isset( $_GET['rsssl_review_notice'] ) && 'dismiss' === $_GET['rsssl_review_notice'] ) {
			rsssl_update_option( 'review_notice_shown', true );
		}
		if ( isset( $_GET['rsssl_review_notice'] ) && 'later' === $_GET['rsssl_review_notice'] ) {
			//Reset activation timestamp, notice will show again in one month.
			update_option( 'rsssl_activation_timestamp', time(), false );
		}
	}

	/**
	 * Show notices
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function show_notices() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/screen.php'; //temp fix for WordPress until it gets fixed in core

		//prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
		$screen = get_current_screen();
		if ( $screen && 'post' === $screen->base ) {
			return;
		}

		//don't show admin notices on our own settings page: we have the warnings there
		if ( $this->is_settings_page() ) {
			return;
		}
		$notices = $this->get_notices_list( array( 'admin_notices' => true ) );
		if ( is_array( $notices ) ) {
			foreach ( $notices as $id => $notice ) {
				$notice = $notice['output'];
				if ( isset( $notice['msg'] ) ) {
					//if there is an open status, we change error to warning.
					$class            = 'open' === $notice['status'] ? 'warning' : 'error';
					$more_info        = $notice['url'] ?? false;
					$logo             = $notice['logo'] ?? false;
					$dismiss_id       = isset( $notice['dismissible'] ) && $notice['dismissible'] ? $id : false;
					$dashboard_button = $notice['dashboard_button'] ?? false;
					echo $this->notice_html( $class . ' ' . $id, $notice['msg'], $more_info, $logo, $dismiss_id, $dashboard_button );
				}
			}
		}
	}

	/**
	 * Helper function to check if the wpconfig needs fixing
	 * Used in notices
	 *
	 * @return bool
	 */
	public function wpconfig_siteurl_not_fixed() {
		return $this->wpconfig_siteurl_not_fixed;
	}

	/**
	 * Helper function to check if the wpconfig needs fixing
	 * Used in notices
	 *
	 * @return bool
	 */
	public function no_server_variable() {
		return $this->no_server_variable;
	}

	/**
	 * Helper function to check if a site url has to be fixed
	 * Used in notices
	 *
	 * @return bool
	 */
	public function do_wpconfig_loadbalancer_fix() {
		return $this->do_wpconfig_loadbalancer_fix;
	}

	/**
	 * Clear the cached admin notices list
	 * @return void
	 */
	public function clear_admin_notices_cache() {
		delete_option( 'rsssl_admin_notices' );
		delete_option( 'rsssl_plusone_count' );
	}

	/**
	 * Get array of notices
	 * - condition: function returning boolean, if notice should be shown or not
	 * - callback: function, returning boolean or string, with multiple possible answers, and resulting messages and icons
	 * @param array $args
	 * @return array
	 */

	public function get_notices_list( $args = array() ) {
		if ( ! rsssl_user_can_manage() ) {
			return [];
		}
		$icon_labels = [
			'success' => __( 'Completed', 'really-simple-ssl' ),
			'warning' => __( 'Warning', 'really-simple-ssl' ),
			'open'    => __( 'Open', 'really-simple-ssl' ),
			'premium' => __( 'Upgrade', 'really-simple-ssl' ),
		];

		$defaults = array(
			'admin_notices'      => false,
			'premium_only'       => false,
			'dismiss_on_upgrade' => false,
			'status'             => [ 'open', 'warning' ], //status can be "all" (all tasks, regardless of dismissed or open), "open" (not success/completed) or "completed"
		);
		$args     = wp_parse_args( $args, $defaults );

		//ensure the status is an an array
		$args['status'] = is_array( $args['status'] ) ? $args['status'] : [ 'open', 'warning' ];

		//if we're on the settings page, we need to clear the admin notices transient, because this list won't get refreshed otherwise
		if ( $this->is_settings_page() && ! get_option( 'rsssl_6_notice_dismissed' ) ) {
			update_option( 'rsssl_6_notice_dismissed', true, false );
		}

		if ( ! $this->is_settings_page() ) {
			$cached_notices = get_option( 'rsssl_admin_notices' );
			if ( 'empty' === $cached_notices ) {
				return [];
			}
			if ( false !== $cached_notices ) {
				return $cached_notices;
			}
		}
		//not cached, set a default here
		//only cache if the admin_notices are retrieved.
		if ( $args['admin_notices'] ) {
			update_option( 'rsssl_admin_notices', 'empty' );
		}

		$rules = $this->get_redirect_rules( true );
		if ( 'NA' !== $this->ssl_type ) {
			$arr_search  = array( '<', '>', "\n" );
			$arr_replace = array( '&lt', '&gt', '<br>' );
			$rules       = str_replace( $arr_search, $arr_replace, $rules );
			$rules       = substr( $rules, 4, -4 );
		} else {
			$rules = __( 'No recommended redirect rules detected.', 'really-simple-ssl' );
		}
		$rules = '<br><code>' . $rules . '</code><br>';

		$notice_defaults = array(
			'condition' => array(),
			'callback'  => false,
		);

		$curl_error            = get_transient( 'rsssl_curl_error' );
		$current_plugin_folder = $this->get_current_rsssl_dirname();

		//get expiry date, if we have one.
		$certinfo    = get_transient( 'rsssl_certinfo' );
		$end_date    = $certinfo['validTo_time_t'] ?? false;
		$expiry_date = ! empty( $end_date ) ? gmdate( get_option( 'date_format' ), $end_date ) : __( '(unknown)', 'really-simple-ssl' );

		$notices = array(
			'load_balancer_fix'                    => array(
				'condition' => [ 'NOT rsssl_ssl_enabled' ],
				'callback'  => 'RSSSL()->admin->do_wpconfig_loadbalancer_fix',
				'score'     => 30,
				'output'    => array(
					'true' => array(
						'msg'          => __( 'Your wp-config.php has to be edited, but is not writable.', 'really-simple-ssl' ) . ' ' .
								__( 'Set your wp-config.php to writable and reload this page.', 'really-simple-ssl' ) . ' ' .
								__( 'To safely enable SSL on your server configuration, you should add the following line of code to your wp-config.php.', 'really-simple-ssl' ) .
								'<br><code>
                                //Begin Really Simple Security Server variable fix<br>
                                &nbsp;&nbsp; $_SERVER["HTTPS"] = "on";<br>
                                //END Really Simple Security
                            </code><br>',
						'icon'         => 'warning',
						'admin_notice' => true,
						'plusone'      => true,
						'dismissible'  => false,
						'url'          => 'knowledge-base/htaccess-wp-config-files-not-writable/',
					),
				),
			),
			'site_url_in_wpconfig'                 => array(
				'condition' => [ 'NOT rsssl_ssl_enabled', 'NOT RSSSL()->admin->uses_bitnami' ],
				'callback'  => 'RSSSL()->admin->wpconfig_siteurl_not_fixed',
				'score'     => 30,
				'output'    => array(
					'true' => array(
						'msg'          => __( 'A definition of a site url or home url was detected in your wp-config.php, but the file is not writable.', 'really-simple-ssl' ) . ' ' . __( 'Set your wp-config.php to writable and reload this page.', 'really-simple-ssl' ),
						'icon'         => 'warning',
						'admin_notice' => true,
						'plusone'      => true,
						'dismissible'  => true,
						'url'          => 'knowledge-base/htaccess-wp-config-files-not-writable',
					),
				),
			),

			'deactivation_file_detected'           => array(
				'callback' => 'RSSSL()->admin->check_for_uninstall_file',
				'score'    => 30,
				'output'   => array(
					'true' => array(
						'msg'          => __( "The 'force-deactivate.php' file has to be renamed to .txt. Otherwise your ssl can be deactivated by anyone on the internet.", 'really-simple-ssl' ) . ' ' .
								'<a href="' . rsssl_admin_url(). '">' . __( 'Check again', 'really-simple-ssl' ) . '</a>',
						'icon'         => 'warning',
						'admin_notice' => true,
						'plusone'      => true,
						'dismissible'  => false,
					),
				),
			),
			'mixed_content_scan'                   => array(
				'dismiss_on_upgrade' => true,
				'condition'          => array( 'rsssl_ssl_enabled' ),
				'callback'           => '_true_',
				'score'              => 5,
				'output'             => array(
					'true' => array(
						'url'         => 'steps-after-activating-ssl',
						'msg'         => __( 'SSL is now activated. Follow the three steps in this article to check if your website is secure.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
						'plusone'     => true,
					),
				),
			),
			'ssl_enabled'                          => array(
				'callback' => 'rsssl_ssl_enabled',
				'score'    => 30,
				'output'   => array(
					'true'  => array(
						'msg'  => __( 'SSL is enabled on your site.', 'really-simple-ssl' ),
						'icon' => 'success',
					),
					'false' => array(
						'msg'     => __( 'SSL is not enabled yet.', 'really-simple-ssl' ),
						'title'   => 'SSL',
						'icon'    => 'warning',
						'plusone' => true,
					),
				),
				'menu_id'  => 'encryption',
				'field_id' => 'redirect',
			),

			'ssl_detected'                         => array(
				'condition' => array( 'NOT rsssl_ssl_detection_overridden', 'NOT RSSSL()->admin->uses_bitnami' ),
				'callback'  => 'rsssl_ssl_detected',
				'score'     => 30,
				'output'    => array(
					'fail'            => array(
						'url'  => 'wp-config-fix-needed',
						'msg'  => __( 'The wp-config.php file is not writable, and needs to be edited. Please set this file to writable.', 'really-simple-ssl' ),
						'icon' => 'warning',
					),
					'no-ssl-detected' => array(
						'title'       => __( 'No SSL detected', 'really-simple-ssl' ),
						'msg'         => __( 'No SSL detected. Use the retry button to check again.', 'really-simple-ssl' ) .
								'<form class="rsssl-task-form"  action="" method="POST">' .
                            wp_nonce_field( 'rsssl_recheck_nonce', 'rsssl_recheck_nonce_field', true, false ) .
                            '<a href="' . rsssl_admin_url(['letsencrypt' => '1'], '#letsencrypt')
								 . '" type="submit" class="button button-default  rsssl-button-small">' . __( 'Install SSL certificate', 'really-simple-ssl' ) . '</a>' .
								'<input type="submit" class="button button-default rsssl-button-small" value="' . __( 'Retry', 'really-simple-ssl' ) . '" id="rsssl_recheck_certificate" name="rsssl_recheck_certificate"></form>',
						'icon'        => 'warning',
						'dismissible' => rsssl_get_option( 'ssl_enabled' ),
					),
					'no-response'     => array(
						'title'       => __( 'Could not test certificate', 'really-simple-ssl' ),
						'msg'         => __( 'Automatic certificate detection is not possible on your server.', 'really-simple-ssl' ) . '<br>' .
								'<a href="' .
									rsssl_admin_url(['letsencrypt' => 1], '#letsencrypt')
								 . '" type="submit" class="button button-default  rsssl-button-small">' . __( 'Install SSL certificate', 'really-simple-ssl' ) . '</a>' .
								'<button class="button button-default rsssl-button-small" id="ssl-labs-check-button">' . __( 'Check manually', 'really-simple-ssl' ) . '</button>',
						'icon'        => 'warning',
						'dismissible' => true,
					),
					'about-to-expire' => array(
						'title' => __( 'Your SSL certificate will expire soon.', 'really-simple-ssl' ),
						'msg'   =>
							// translators: %s is replaced with date.
							sprintf( __( 'SSL certificate will expire on %s.', 'really-simple-ssl' ), $expiry_date ) . '&nbsp;' . __( 'If your hosting provider auto-renews your certificate, no action is required. Alternatively, you have the option to generate an SSL certificate with Really Simple Security.', 'really-simple-ssl' ) . '&nbsp;' .
							// translators: %1$ and %2$s are replaced with the opening and closing tag with link.
							sprintf( __( 'Depending on your hosting provider, %1$smanual installation%2$s may be required.', 'really-simple-ssl' ), '<a target="_blank" rel="noopener noreferrer" href="https://really-simple-ssl.com/install-ssl-certificate">', '</a>' ) .

								'<br><br><form action="" method="POST"><a href="' .
									rsssl_admin_url(['letsencrypt' => 1], '#letsencrypt')
								 . '" type="submit" class="button button-default">' . __( 'Install SSL certificate', 'really-simple-ssl' ) . '</a>' .
								'&nbsp;<input type="submit" class="button button-default" value="' . __( 'Re-check', 'really-simple-ssl' ) . '" id="rsssl_recheck_certificate" name="rsssl_recheck_certificate"></form>',
						'icon'  => 'warning',
					),
				),
			),

			'mixed_content_fixer_detected'         => array(
				'condition' => array( 'rsssl_ssl_enabled' ),
				'callback'  => 'RSSSL()->admin->mixed_content_fixer_detected',
				'score'     => 10,
				'output'    => array(
					'no-response' => array(
						'url'         => 'knowledge-base/how-to-fix-no-response-from-webpage-warning/',
						'msg'         => __( 'Really Simple Security has received no response from the webpage.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
						'plusone'     => true,
					),
					'not-found'   => array(
						'url'         => 'knowledge-base/how-to-check-if-the-mixed-content-fixer-is-active',
						'msg'         => __( 'The mixed content fixer is active, but was not detected on the frontpage.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
					),
					'error'       => array(
						'msg'         => __( 'Error occurred when retrieving the webpage.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
					),
					'not-enabled' => array(
						'highlight_field_id' => 'mixed_content_fixer',
						'msg'                => __( 'Mixed content fixer not enabled. Enable the option to fix mixed content on your site.', 'really-simple-ssl' ),
						'icon'               => 'open',
						'dismissible'        => true,
					),
					'curl-error'  => array(
						'url'         => 'knowledge-base/curl-errors',
						'msg'         => // translators: %s is replaced with the error description.
										sprintf( __( "The mixed content fixer could not be detected due to a cURL error: %s. cURL errors are often caused by an outdated version of PHP or cURL and don't affect the front-end of your site. Contact your hosting provider for a fix.", 'really-simple-ssl' ), '<b>' . $curl_error . '</b>' ),
						'icon'        => 'open',
						'dismissible' => true,
					),
				),
			),

			'wordpress_redirect'                   => array(
				'condition' => array( 'rsssl_ssl_enabled', 'NOT RSSSL()->admin->htaccess_redirect_allowed' ),
				'callback'  => 'RSSSL()->admin->has_301_redirect',
				'score'     => 10,
				'output'    => array(
					'true'  => array(
						'msg'  => __( '301 redirect to https set.', 'really-simple-ssl' ),
						'icon' => 'success',
					),
					'false' => array(
						'msg'  => __( 'No 301 redirect is set. Enable the WordPress 301 redirect in the settings to get a 301 permanent redirect.', 'really-simple-ssl' ),
						'icon' => 'open',
					),
				),
			),

			'check_redirect'                       => array(
				'condition'         => array( 'rsssl_ssl_enabled', 'RSSSL()->admin->htaccess_redirect_allowed' ),
				'callback'          => 'RSSSL()->admin->redirect_status',
				'score'             => 10,
				'show_with_options' => [
					'redirect',
				],
				'output'            => array(
					'htaccess-redirect-set'      => array(
						'title' => __( '301 .htaccess redirect', 'really-simple-ssl' ),
						'msg'   => __( 'The 301 redirect with .htaccess to HTTPS is now enabled.', 'really-simple-ssl' ),
						'icon'  => 'success',
					),
					'wp-redirect-to-htaccess'    => array(
						'highlight_field_id' => 'redirect',
						'title'              => __( '301 .htaccess redirect', 'really-simple-ssl' ),
						'msg'                => __( 'WordPress 301 redirect enabled. We recommend to enable a 301 .htaccess redirect.', 'really-simple-ssl' ),
						'icon'               => 'open',
						'plusone'            => RSSSL()->server->uses_htaccess(),
						'dismissible'        => true,
					),
					'no-redirect-set'            => array(
						'highlight_field_id' => 'redirect',
						'msg'                => __( 'Enable a .htaccess redirect or PHP redirect in the settings to create a 301 redirect.', 'really-simple-ssl' ),
						'icon'               => 'open',
						'dismissible'        => false,
					),
					'htaccess-rules-test-failed' => array(
						'title'       => __( '.htaccess redirect.', 'really-simple-ssl' ),
						'url'         => 'knowledge-base/manually-insert-htaccess-redirect-http-to-https',
						'msg'         => __( 'The .htaccess redirect rules selected by this plugin failed in the test. Set manually or dismiss to leave on PHP redirect.', 'really-simple-ssl' ) . $rules,
						'icon'        => 'warning',
						'dismissible' => true,
						'plusone'     => true,
					),
				),
			),

			'elementor'                            => array(
				'condition' => array( 'rsssl_ssl_activation_time_no_longer_then_3_days_ago' ),
				'callback'  => 'rsssl_uses_elementor',
				'score'     => 5,
				'output'    => array(
					'true' => array(
						'url'         => 'knowledge-base/how-to-fix-mixed-content-in-elementor-after-moving-to-ssl',
						'msg'         => __( 'Your site uses Elementor. This can require some additional steps before getting the secure lock.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
					),
				),
			),

			'divi'                                 => array(
				'condition' => array( 'rsssl_ssl_activation_time_no_longer_then_3_days_ago' ),
				'callback'  => 'rsssl_uses_divi',
				'score'     => 5,
				'output'    => array(
					'true' => array(
						'url'         => 'knowledge-base/mixed-content-when-using-divi-theme/',
						'msg'         => __( 'Your site uses Divi. This can require some additional steps before getting the secure lock.', 'really-simple-ssl' ),
						'icon'        => 'open',
						'dismissible' => true,
					),
				),
			),
			'recommended_security_headers_not_set' => array(
				'callback' => 'RSSSL()->admin->recommended_headers_enabled',
				'score'    => 5,
				'output'   => array(
					'false' => array(
						'msg'         => __( 'See which recommended security headers are not present on your website.', 'really-simple-ssl' ),
						'icon'        => 'premium',
						'dismissible' => false,
						'url' => add_query_arg(
							array( 'domain' => site_url() ),
							'https://scan.really-simple-ssl.com'
						),
					),
					'true'  => array(
						'msg'  => __( 'Recommended security headers enabled.', 'really-simple-ssl' ),
						'icon' => 'success',
					),
				),
			),
			'enable_two_fa' => array(
				'callback' => 'option_login_protection_enabled',
				'score'    => 5,
				'output'   => array(
					'false' => array(
						'highlight_field_id' => 'login_protection_enabled',
						'msg'                => __( 'Implement Two-Factor Authentication or Passkey login.', 'really-simple-ssl' ),
						'icon'               => 'premium',
                        'url'                => 'login-protection',
					),
				),
			),
			'enable_lla' => array(
                'callback' => 'option_enable_limited_login_attempts',
                'score'    => 5,
                'output'   => array(
                    'false' => array(
                        'highlight_field_id' => 'enable_limited_login_attempts',
                        'msg'                => __( 'Protect your login form with Limit Login Attempts.', 'really-simple-ssl' ),
                        'icon'               => 'premium',
                        'url'                => 'login-protection',
				    ),
			    ),
		    ),
			'enable_firewall' => array(
                'callback' => 'option_enable_firewall',
                'score'    => 5,
                'output'   => array(
                    'false' => array(
                        'highlight_field_id' => 'enable_firewall',
                        'msg'                => __( 'Protect your site with a performant Firewall.', 'really-simple-ssl' ),
                        'icon'               => 'premium',
                        'url'                => 'firewall',
                    ),
                ),
			),
			'duplicate-ssl-plugins'                => array(
				'condition' => array( 'rsssl_detected_duplicate_ssl_plugin' ),
				'callback'  => '_true_',
				'output'    => array(
					'true' => array(
						'msg'         => // translators: %s is replaced with the plugin name.
										sprintf( __( 'We have detected the %s plugin on your website.', 'really-simple-ssl' ), rsssl_detected_duplicate_ssl_plugin( true ) ) . '&nbsp;' . __( 'As Really Simple Security handles all the functionality this plugin provides, we recommend to disable this plugin to prevent unexpected behavior.', 'really-simple-ssl' ),
						'icon'        => 'warning',
						'dismissible' => true,
						'plusone'     => true,
					),
				),
			),
			'bf_notice2023'                        => array(
				'condition' => array(
					'RSSSL()->admin->is_bf',
				),
				'callback'  => '_true_',
				'output'    => array(
					'true' => array(
						'msg'         => __( 'Black Friday sale! Get 40% Off Really Simple Security Pro', 'really-simple-ssl' ),
						'icon'        => 'premium',
						'url'         => 'pro',
						'dismissible' => true,
						'plusone'     => true,
					),
				),
			),
			'ajax_fallback' => array(
	            'condition'  => array(
                        'wp_option_rsssl_ajax_fallback_active',
                ),
	            'callback' => '_true_',
	            'output' => array(
		            'true' => array(
			            'msg' => __( "Please check if your REST API is loading correctly. Your site currently is using the slower Ajax fallback method to load the settings.", 'really-simple-ssl' ),
			            'icon' => 'warning',
			            'admin_notice' => false,
			            'url' => 'instructions/how-to-debug-a-blank-settings-page-in-really-simple-ssl',
			            'dismissible' => true,
			            'plusone' => true,
		            ),
	            ),
            ),
	        'email_verification_not_verified' => array(
		        'callback' => 'RSSSL()->mailer_admin->email_verification_completed',
		        'output' => array(
			        'false' => array(
				        'highlight_field_id' => 'notifications_email_address',
				        'msg' => __( "Complete email validation and enable notifications to make sure you will receive security warnings.", 'really-simple-ssl' ),
				        'icon' => 'open',
				        'admin_notice' => false,
				        'url' => 'instructions/email-verification',
				        'dismissible' => true,
				        'plusone' => true,
			        ),
			        'true' => array(
				        'msg' => __( "Email address successfully verified.", 'really-simple-ssl' ),
				        'icon' => 'success',
				        'admin_notice' => false,
				        'url' => 'instructions/email-verification',
				        'dismissible' => true,
				        'plusone' => false,
			        ),
		        ),
	        ),
			'plain_permalinks' => array(
				'condition'  => array(
					'rsssl_plain_permalinks_enabled',
				),
				'callback' => '_true_',
				'output' => array(
					'true' => array(
						'msg' => __( "Your site uses plain permalinks, which causes issues with the REST API. Please use a different permalinks configuration.", 'really-simple-ssl' ),
						'icon' => 'open',
						'admin_notice' => false,
						'dismissible' => true,
						'plusone' => false,
						'url' => admin_url('options-permalink.php'),
					),
				),
			),
            'upgraded_to_nine' => array(
                'condition' => array(
                    'rsssl_show_upgrade_to_nine_notice',
                ),
                'callback' => '_true_',
                'output' => array(
                    'true' => array(
	                    'msg'              => rsssl_upgrade_to_nine_notice(),
	                    'icon'             => 'open',
	                    'admin_notice'     => true,
	                    'logo'             => true,
	                    'dashboard_button' => true,
	                    'dismissible'      => true,
	                    'plusone'          => true,
                    ),
                ),
            ),

			'test_404s' => array(
                'condition' => array(
                    'wp_option_rsssl_homepage_contains_404_resources',
                ),
				'callback' => '_true_',
				'output'   => array(
					'true' => array(
						'msg'         => __("404 errors detected on your homepage. 404 blocking is unavailable, to prevent blocking of legitimate visitors. It is strongly recommended to resolve these errors.", 'really-simple-ssl'),
						'url'         => '404-not-found-errors',
						'icon'        => 'warning',
						'dismissible' => true,
						'plusone'     => false,
						'clear_cache_id' => 'rsssl_homepage_contains_404_resources',
					),
				),
			),
        );

		//on multisite, don't show the notice on subsites.
		$notices = apply_filters( 'rsssl_notices', $notices );
		foreach ( $notices as $id => $notice ) {
			$notices[ $id ] = wp_parse_args( $notice, $notice_defaults );
		}

		/**
		 * If a list of notices that should be dismissed on upgrade is requested
		 */
		if ( $args['dismiss_on_upgrade'] ) {
			$output = array();
			foreach ( $notices as $key => $notice ) {
				if ( isset( $notice['dismiss_on_upgrade'] ) && $notice['dismiss_on_upgrade'] ) {
					$output[] = $key;
				}
			}
			return $output;
		}

		/**
		 * Filter out notice that do not apply, or are dismissed
		 */
		$statuses = $args['status'];
		foreach ( $notices as $id => $notice ) {
			$func   = $notice['callback'];
			$output = $this->validate_function( $func );

			//check if all notices should be dismissed
			if ( isset( $notice['output'][ $output ]['dismissible'] )
				&& $notice['output'][ $output ]['dismissible']
				&& rsssl_get_option( 'dismiss_all_notices' )
			) {
				unset( $notices[ $id ] );
				continue;
			}

			if ( ! isset( $notice['output'][ $output ] ) ) {
				unset( $notices[ $id ] );
				continue;
			}

            if ( isset($notice['output'][ $output ]['url'] ) ) {
                $url = $notice['output'][ $output ]['url'];
                if ( strpos( $url, 'https://') ===false && strpos( $url, 'http://') === false ) {
	                $notice['output'][ $output ]['url'] = rsssl_link($url);
                }
            }

			$notices[ $id ]['output']           = $notice['output'][ $output ];
			$notices[ $id ]['output']['status'] = 'success' === $notices[ $id ]['output']['icon'] ? 'completed' : $notices[ $id ]['output']['icon'];
			if ( ! in_array( $notices[ $id ]['output']['status'], $statuses, true ) ) {
				unset( $notices[ $id ] );
				continue;
			}
			$condition_functions = $notice['condition'];
			foreach ( $condition_functions as $func ) {
				$condition = $this->validate_function( $func, true );
				if ( ! $condition ) {
					unset( $notices[ $id ] );
				}
			}

			if ( isset( $notices[ $id ] ) ) {
				$notices[ $id ]['output']['label'] = $icon_labels[ $notices[ $id ]['output']['icon'] ];
			}

			//only remove this option if it's both dismissed AND not completed. This way we keep completed notices in the list.
			if ( isset( $notices[ $id ] ) && get_option( 'rsssl_' . $id . '_dismissed' ) && 'completed' !== $notices[ $id ]['output']['status'] ) {
				unset( $notices[ $id ] );
			}
		}

		//if only admin_notices are required, filter out the rest.
		if ( $args['admin_notices'] ) {
			foreach ( $notices as $id => $notice ) {
				if ( ! isset( $notice['output']['admin_notice'] ) || ! $notice['output']['admin_notice'] ) {
					unset( $notices[ $id ] );
				}
			}
		}

//sort notices in the order: warning, open, premium, success
		$warnings = array();
		$open     = array();
		$premium  = array();
		$success  = array();
		$other    = array();
		foreach ( $notices as $key => $notice ) {
			if ( ! isset( $notice['output']['icon'] ) ) {
				$other[$key] = $notice;
				continue;
			}

			switch ( $notice['output']['icon'] ) {
				case 'warning':
					$warnings[$key] = $notice;
					break;
				case 'open':
					$open[$key] = $notice;
					break;
				case 'premium':
					$premium[$key] = $notice;
					break;
				case 'success':
					$success[$key] = $notice;
					break;
				default:
					$other[$key] = $notice;
			}
		}
		$notices = $warnings + $open + $premium + $success + $other;

		//if we only want a list of premium notices
		if ( $args['premium_only'] ) {
			foreach ( $notices as $key => $notice ) {
				if ( ! isset( $notice['output']['icon'] ) || 'premium' !== $notice['output']['icon'] ) {
					unset( $notices[ $key ] );
				}
			}
		}

		// Check if the 'black_friday_notice' exists and move it to the top of the array
		if ( isset( $notices['bf_notice2023'] ) ) {
			// Remove the 'black_friday_notice' from its current position
			$black_friday_notice = array( 'bf_notice2023' => $notices['bf_notice2023'] );
			unset( $notices['bf_notice2023'] );

			// Add the 'black_friday_notice' back at the beginning of the array
			$notices = $black_friday_notice + $notices;
		}

//		// Check if the 'upgraded_to_nine' exists and move it to the top of the array
//		if ( isset( $notices['upgraded_to_nine'] ) ) {
//			// Remove the 'black_friday_notice' from its current position
//			$upgraded_to_nine_notice = array( 'upgraded_to_nine' => $notices['upgraded_to_nine'] );
//			unset( $notices['upgraded_to_nine'] );
//
//			// Add the 'black_friday_notice' back at the beginning of the array
//			$notices = $upgraded_to_nine_notice + $notices;
//		}

		//ensure an empty list is also cached
		$cache_notices = empty( $notices ) ? 'empty' : $notices;
		//only cache if the admin_notices are retrieved.
		if ( $args['admin_notices'] ) {
			update_option( 'rsssl_admin_notices', $cache_notices );
		}

		return $notices;
	}

	private function is_upgraded_to_6() {
		return get_option( 'rsssl_show_onboarding' ) && ! get_option( 'rsssl_6_notice_dismissed' );
	}

	private function is_upgraded() {
		$previous_version = get_option( 'rsssl_previous_version' );
		//if there's no first version yet, we assume it's not upgraded
		if ( ! $previous_version ) {
			return false;
		}
		//if the previous version is below current, we just upgraded.
		if ( version_compare( $previous_version, rsssl_version, '<' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get output of function, in format 'function', or 'class()->sub()->function'
	 * @param string $func
	 * @param bool $is_condition // if the check is a condition, which should return a boolean
	 * @return string|bool
	 */

	private function validate_function( $func, $is_condition = false ) {
		if ( ! rsssl_user_can_manage() ) {
			return false;
		}

		$invert = false;
		if ( false !== strpos( $func, 'NOT ' ) ) {
			$func   = str_replace( 'NOT ', '', $func );
			$invert = true;
		}

		if ( false !== strpos( $func, 'wp_option_' ) ) {
            // False when: no option - option is bool false - option is string false
			$output = get_option( str_replace( 'wp_option_', '', $func ) ) !== false && get_option( str_replace( 'wp_option_', '', $func ) ) !== 'false';
		} elseif ( false !== strpos( $func, 'option_' ) ) {
			$output = rsssl_get_option( str_replace( 'option_', '', $func ) ) == 1; //phpcs:ignore
		} elseif ( '_true_' === $func ) {
			$output = true;
		} elseif ( '_false_' === $func ) {
			$output = false;
		} else {
			if ( preg_match( '/(.*)\(\)\-\>(.*)->(.*)/i', $func, $matches ) ) {
				$base     = $matches[1];
				$class    = $matches[2];
				$function = $matches[3];
				if ( property_exists($base(), $class) && $base()->{$class} && method_exists($base()->{$class}, $function) ) {
					$output   = call_user_func(array($base()->{$class}, $function));
				} else {
					$output = false;
                    $this->log( $func . ' not found');
				}
			} else {
				if ( !is_string($func) || !function_exists($func) ) {
                    if ( defined('WP_DEBUG') && WP_DEBUG ) {
                        error_log("missing function:");
                        error_log(print_r($func, true));
                    }
					return false;
				}
                $output = $func();
			}
		}

		if ( $invert ) {
			$output = ! $output;
		}

		//stringyfy booleans
		if ( ! $is_condition ) {
			if ( false === $output || 0 === $output ) {
				$output = 'false';
			}
			if ( true === $output || 1 === $output ) {
				$output = 'true';
			}
		}
		return sanitize_text_field( $output );
	}

	/**
	 * Count the plusones.
	 *
	 * @return int
	 *
	 * @since 3.2
	 */

	public function count_plusones() {
		if ( ! rsssl_user_can_manage() ) {
			return 0;
		}

		$cache = $this->is_settings_page() ? false : true;
		$count = get_option( 'rsssl_plusone_count' );
		if ( ! $cache || false === $count ) {
			$count   = 0;
			$notices = $this->get_notices_list();
			if ( is_array( $notices ) ) {
				foreach ( $notices as $notice ) {
					$success = isset( $notice['output']['icon'] ) && 'success' === $notice['output']['icon'];
					if ( ! $success
						&& isset( $notice['output']['plusone'] )
						&& $notice['output']['plusone']
					) {
						++$count;
					}
				}
			}
			if ( 0 === $count ) {
				$count = 'empty';
			}
			update_option( 'rsssl_plusone_count', $count );
		}

		if ( 'empty' === $count ) {
			return 0;
		}
		return $count;
	}

	/**
	 * Add some css for the settings page
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function enqueue_assets( $hook ) {

		if ( 'toplevel_page_really-simple-security' !== $hook ) {
			return;
		}

		//only on settings page
		$min  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl  = is_rtl() ? 'rtl/' : '';
		$url  = trailingslashit( rsssl_url ) . "assets/css/{$rtl}admin{$min}.css";
		$path = trailingslashit( rsssl_path ) . "assets/css/{$rtl}admin{$min}.css";
		wp_enqueue_style( 'rsssl-css', $url, [ 'wp-components' ], filemtime( $path ) );
	}

	/**
	 * Check if wpconfig contains httponly cookie settings
	 *
	 * @return string
	 */

	public function secure_cookie_settings_status() {
		$wpconfig_path = $this->wpconfig_path();
		if ( ! $wpconfig_path ) {
			return 'wpconfig-not-writable';
		}

		$wpconfig = file_get_contents( $wpconfig_path );
		if ( ( strpos( $wpconfig, '//Begin Really Simple Security session cookie settings' ) !== false ) || ( strpos( $wpconfig, 'cookie_httponly' ) !== false ) ) {
			return 'set';
		}

		if ( ! is_writable( $wpconfig_path ) ) {
			return 'wpconfig-not-writable';
		}

		return 'not-set';
	}

	/**
	 * Insert secure cookie settings
	 */

	public function insert_secure_cookie_settings() {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( ! $this->is_settings_page() ) {
			return;
		}

		//if multisite, only on network wide activated setups
		if ( is_multisite() && ! rsssl_is_networkwide_active() ) {
			return;
		}

		//only if cookie settings were not inserted yet
		if ( $this->secure_cookie_settings_status() !== 'set' ) {
			$wpconfig_path = $this->wpconfig_path();

			if ( empty( $wpconfig_path ) ) {
				return;
			}

			$wpconfig = file_get_contents( $wpconfig_path );
			if ( '' !== $wpconfig && is_writable( $wpconfig_path ) ) {
				$rule  = "\n" . '//Begin Really Simple Security session cookie settings' . "\n";
				$rule .= "@ini_set('session.cookie_httponly', true);" . "\n";
				$rule .= "@ini_set('session.cookie_secure', true);" . "\n";
				$rule .= "@ini_set('session.use_only_cookies', true);" . "\n";
				$rule .= '//END Really Simple Security cookie settings' . "\n";

				$insert_after = '<?php';
				$pos          = strpos( $wpconfig, $insert_after );
				if ( false !== $pos ) {
					$wpconfig = substr_replace( $wpconfig, $rule, $pos + 1 + strlen( $insert_after ), 0 );
				}

				file_put_contents( $wpconfig_path, $wpconfig );
			}
		}
	}

	/**
	 * Get the absolute path the the www directory of this site, where .htaccess lives.
	 *
	 * @since  2.0
	 *
	 * @access public
	 *
	 */

	public function getabs_path() {
		$path = ABSPATH;
		if ( $this->is_subdirectory_install() ) {
			$site_url = site_url();
			$home_url = home_url();
			$diff     = str_replace( $home_url, '', $site_url );
			$diff     = trim( $diff, '/' );
			$pos      = strrpos( $path, $diff );
			if ( false !== $pos ) {
				$path = substr_replace( $path, '', $pos, strlen( $diff ) );
				$path = trim( $path, '/' );
				$path = '/' . $path . '/';
			}
		}

		return $path;
	}

	/**
	 * Check if it's either a single site, or when multisite, network enabled.
	 * @return bool
	 */
	public function can_apply_networkwide() {
		if ( ! is_multisite() ) {
			return true;
		}

		if ( is_multisite() && rsssl_is_networkwide_active() ) {
			return true;
		}

		return false;
	}

	/**
	 * Find if this WordPress installation is installed in a subdirectory
	 *
	 * @since  2.0
	 *
	 * @access protected
	 *
	 */

	protected function is_subdirectory_install() {
		return strlen( site_url() ) > strlen( home_url() );
	}

	/**
	 * Retrieve the contents of the test page
	 *
	 * @return string
	 *
	 */

	protected function get_test_page_contents() {
		$filecontents = get_transient( 'rsssl_testpage' );
		if ( ! $filecontents ) {
			$testpage_url = trailingslashit( $this->test_url() ) . 'ssl-test-page.php';
			$response     = wp_remote_get( $testpage_url );
			if ( is_array( $response ) ) {
				$filecontents = wp_remote_retrieve_body( $response );
			}

			if ( empty( $filecontents ) ) {
				$filecontents = 'not-valid';
			}
			set_transient( 'rsssl_testpage', $filecontents, DAY_IN_SECONDS );
		}
		return $filecontents;
	}

	/**
	 * Determine dirname to show in admin_notices() in really-simple-ssl-pro.php to show a warning when free folder has been renamed
	 *
	 * @return string
	 *
	 * since 3.1
	 *
	 */

	public function get_current_rsssl_dirname() {
		return basename( __DIR__ );
	}

	/**
	 * Determine the htaccess file. This can be either the regular .htaccess file, or an htaccess.conf file on bitnami installations.
	 *
	 * since 3.1
	 *
	 * @return string
	 */

	public function htaccess_file() {
		if ( $this->uses_htaccess_conf() ) {
			$htaccess_file = realpath( dirname( ABSPATH ) . '/conf/htaccess.conf' );
		} else {
			$htaccess_file = $this->abs_path . '.htaccess';
		}
		return $htaccess_file;
	}

	/**
	 * Check the current redirect status
	 *
	 * @return string
	 */
	public function redirect_status() {
		if ( ! RSSSL()->admin->has_301_redirect() ) {
			return 'no-redirect-set';
		}

		if ( RSSSL()->admin->has_301_redirect() && RSSSL()->server->uses_htaccess() && RSSSL()->admin->htaccess_contains_redirect_rules() ) {
			return 'htaccess-redirect-set';
		}

		if ( $this->can_apply_networkwide() && ! RSSSL()->admin->htaccess_contains_redirect_rules() && rsssl_get_option( 'redirect' ) === 'wp_redirect' ) {
			return 'wp-redirect-to-htaccess';
		}

		if ( rsssl_get_option( 'redirect' ) === 'htaccess' && ! RSSSL()->admin->htaccess_test_success() && $this->can_apply_networkwide() ) {
			return 'htaccess-rules-test-failed';
		}

		return 'default';
	}

	/**
	 * @return void
     *
     * Update branding in .htaccess, wp-config.php
	 */
    public function update_branding_in_files() {
        $this->maybe_update_branding_in_htaccess();
        $this->maybe_update_branding_in_wp_config();
    }

	/**
	 * @return void
     *
     * Update branding in .htaccess. Load .htaccess if it exists and is writable, replace branding, write back
	 */
    public function maybe_update_branding_in_htaccess(): void {

	    if ( ! file_exists( $this->htaccess_file() ) || ! is_writable( $this->htaccess_file() ) ) {
		    return;
	    }

	    $htaccess = file_get_contents( $this->htaccess_file() );

	    if ( strpos( $htaccess, 'Really Simple Security') !== false ) {
		    str_replace("Really Simple SSL", "Really Simple Security", $htaccess);
	    }

        file_put_contents( $this->htaccess_file(), $htaccess );

    }

	/**
	 * @return void
	 *
	 * Update branding in .htaccess. Load .htaccess if it exists and is writable, replace branding, write back
	 */
    public function maybe_update_branding_in_wp_config() {

	   if ( $this->wpconfig_is_writable() ) {

		   $wp_config = file_get_contents( $this->wpconfig_path() );

		   if ( strpos( $wp_config, 'Really Simple SSL' ) !== false ) {
			   str_replace( "Really Simple SSL", "Really Simple Security", $wp_config );
		   }

		   file_put_contents( $this->wpconfig_path(), $wp_config );
	   }
    }

	/**
	 * Delete the permission detection options when the setting is disabled.
	 *
	 * @param string $field_id
	 * @param mixed  $field_value
	 * @param mixed  $prev_value
	 * @param string $field_type
	 *
	 * @return void
	 */
	public function maybe_delete_permission_detection_option( string $field_id, $field_value, $prev_value, $field_type ): void {
		if ( ! rsssl_user_can_manage() ) {
			return;
		}

		if ( $field_value === $prev_value ) {
			return;
		}

		if ( $field_id === 'permission_detection' ) {
			// If permission_detection option is disabled, reset options
			if ( ! $field_value ) {
				delete_option( 'rsssl_permission_check_completed' );
				delete_option( 'rsssl_files_with_wrong_permissions' );
				delete_option( 'rsssl_permission_check_next_index' );
			}
		}

	}

    /**
     * @param $data
     *
	 * @return array
	 *
	 * Clear 404 test cache
	 */
	public function clear_404_test_cache( $data ) {

		if ( ! rsssl_user_can_manage() ) {
			return [];
		}

		$cache_id = sanitize_title($data['cache_id']);

        if ( 'rsssl_homepage_contains_404_resources' !== $cache_id ) {
            return [];
        }

		delete_option( 'rsssl_homepage_contains_404_resources' );
		delete_option( 'rsssl_404_resources_to_check' );
		$this->clear_admin_notices_cache();

		return [];
	}

} //class closure

if ( ! function_exists( 'rsssl_ssl_enabled' ) ) {
	function rsssl_ssl_enabled() {
		return boolval( rsssl_get_option( 'ssl_enabled' ) );
	}
}

if ( ! function_exists( 'rsssl_ssl_detected' ) ) {
	function rsssl_ssl_detected() {
		if ( ! RSSSL()->admin->wpconfig_ok() ) {
			return apply_filters( 'rsssl_ssl_detected', 'fail' );
		}

		$valid = RSSSL()->certificate->is_valid();
		if ( ! $valid ) {
			if ( ! function_exists( 'stream_context_get_params' ) || RSSSL()->certificate->detection_failed() ) {
				return apply_filters( 'rsssl_ssl_detected', 'no-response' );
			} else {
				return apply_filters( 'rsssl_ssl_detected', 'no-ssl-detected' );
			}
		} else {
			$about_to_expire = RSSSL()->certificate->about_to_expire();
			if ( ! $about_to_expire ) {
				return apply_filters( 'rsssl_ssl_detected', 'ssl-detected' );
			} else {
				return apply_filters( 'rsssl_ssl_detected', 'ssl-detected' );
			}
		}
	}
}

if ( ! function_exists( 'rsssl_uses_elementor' ) ) {
	function rsssl_uses_elementor() {
		return ( defined( 'ELEMENTOR_VERSION' ) || defined( 'ELEMENTOR_PRO_VERSION' ) );
	}
}

if ( ! function_exists( 'rsssl_uses_divi' ) ) {
	function rsssl_uses_divi() {
		return defined( 'ET_CORE_PATH' );
	}
}

if ( ! function_exists( 'rsssl_uses_wp_engine' ) ) {
	function rsssl_uses_wp_engine() {
		if ( function_exists( 'is_wpe' ) && is_wpe() ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'rsssl_beta_5_addon_active' ) ) {
	function rsssl_beta_5_addon_active() {
		if ( defined( 'rsssl_beta_addon' ) && rsssl_beta_addon ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'rsssl_ssl_activation_time_no_longer_then_3_days_ago' ) ) {
	function rsssl_ssl_activation_time_no_longer_then_3_days_ago() {

		$activation_time             = get_option( 'rsssl_activation_timestamp' );
		$three_days_after_activation = $activation_time + 3 * DAY_IN_SECONDS;

		if ( time() < $three_days_after_activation ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'rsssl_letsencrypt_wizard_url' ) ) {
	/**
	 * Get link to SSL certificate generation page
	 *
	 * @param string $page
	 *
	 * @return string
	 */
	function rsssl_letsencrypt_wizard_url( $page = '' ) {
		if ( ! empty( $page ) ) {
			$page = '/' . $page;
		}
		if ( is_multisite() && ! is_main_site() ) {
			return add_query_arg(
				array(
					'page'        => 'really-simple-security',
					'letsencrypt' => 1,
				),
				get_admin_url( get_main_site_id(), 'options-general.php' )
			) . "#letsencrypt$page";
		} else {
			return add_query_arg(
				array(
					'page'        => 'really-simple-security',
					'letsencrypt' => 1,
				),
				admin_url( 'options-general.php' )
			) . "#letsencrypt$page";
		}
	}
}

if ( ! function_exists('rsssl_plain_permalinks_enabled')) {
    function rsssl_plain_permalinks_enabled() {
        return ! get_option( 'permalink_structure' );
    }
}

if ( ! function_exists( 'rsssl_detected_duplicate_ssl_plugin' ) ) {
	/**
	 * Duplicate functionality test
	 *
	 * @param string $return_name
	 *
	 * @return bool|string
	 */
	function rsssl_detected_duplicate_ssl_plugin( $return_name = false ) {
		$plugin = false;
		if ( defined( 'WPLE_PLUGIN_VERSION' ) ) {
			$plugin = 'WP Encryption';
		} elseif ( defined( 'WPSSL_VER' ) ) {
			$plugin = 'WP Free SSL';
		} elseif ( defined( 'SSL_ZEN_PLUGIN_VERSION' ) ) {
			$plugin = 'SSL Zen';
		} elseif ( defined( 'WPSSL_VER' ) ) {
			$plugin = 'WP Free SSL';
		} elseif ( defined( 'SSLFIX_PLUGIN_VERSION' ) ) {
			$plugin = 'SSL Insecure Content Fixer';
		} elseif ( class_exists( 'OCSSL', false ) ) {
			$plugin = 'One Click SSL';
		} elseif ( class_exists( 'JSM_Force_SSL', false ) ) {
			$plugin = "JSM's Force HTTP to HTTPS (SSL)";
		} elseif ( function_exists( 'httpsrdrctn_plugin_init' ) ) {
			$plugin = 'Easy HTTPS (SSL) Redirection';
		} elseif ( defined( 'WPSSL_VER' ) ) {
			$plugin = 'WP Free SSL';
		} elseif ( defined( 'WPFSSL_OPTIONS_KEY' ) ) {
			$plugin = 'WP Force SSL';
		} elseif ( defined( 'ESSL_REQUIRED_PHP_VERSION' ) ) {
			$plugin = 'EasySSL';
		}

		if ( false !== $plugin && ! $return_name ) {
			return true;
		}

		return $plugin;
	}
}

if ( ! function_exists( 'rsssl_ssl_detection_overridden' ) ) {
	function rsssl_ssl_detection_overridden() {
		return get_option( 'rsssl_ssl_detection_overridden' ) !== false;
	}
}

if ( ! function_exists('maybe_disable_frame_ancestors_url_field' ) ) {
	function maybe_disable_frame_ancestors_url_field() {
		if ( rsssl_get_option( 'csp_frame_ancestors' ) === 'disabled' ) {
			return true;
		}

		return false;
	}
}

/**
 * Upgraded to nine notice text
 */
if ( ! function_exists('rsssl_upgrade_to_nine_notice' ) ) {
	function rsssl_upgrade_to_nine_notice() {

		$link = rsssl_link('our-journey-towards-really-simple-security');
		$msg = sprintf(
		       /* translators: %1$s: opening bold tag, %2$s: closing bold tag */
			       __( 'Really Simple SSL is now %1$sReally Simple Security!%2$s', 'really-simple-ssl' ),
			       '<b>',
			       '</b>' )
		       . "<br>" .
		       sprintf(
			       "<a href='%s'>%s</a>",
			       $link,
			       __( "Read about our journey towards Really Simple Security", 'really-simple-ssl' )
		       );

		if ( ! defined( 'rsssl_pro' ) ) {
			$link = rsssl_link( 'checkout/?edd_action=add_to_cart&download_id=860&edd_options%5Bprice_id%5D=1&currency=EUR', 'notification', 'free', 'REALLYSIMPLESECURITY' );
			$msg  .= "<br>" . sprintf(
					__( "Experience all powerful features of Really Simple Security Pro using this %slimited time discount%s: %s", 'really-simple-ssl' ),
					'<strong>',
					'</strong>',
					"<a href='$link'>REALLYSIMPLESECURITY</a>"
				);
		}

		return $msg;
	}
}

/**
 * Do not show notice after 2024-10-01

 */
if ( ! function_exists('rsssl_show_upgrade_to_nine_notice' ) ) {
	function rsssl_show_upgrade_to_nine_notice() {
		if ( time() >= strtotime('2024-10-01') ) {
			return false;
		}

        return true;
	}
}
