<?php /** @noinspection PhpComposerExtensionStubsInspection */

use security\wordpress\vulnerabilities\FileStorage;

defined('ABSPATH') or die();
//including the file storage class
require_once(rsssl_path . 'security/wordpress/vulnerabilities/FileStorage.php');

/**
 * @package Really Simple SSL
 * @subpackage RSSSL_VULNERABILITIES
 */
if (!class_exists("rsssl_vulnerabilities")) {
    /**
     *
     * Class rsssl_vulnerabilities
     * Checks for vulnerabilities in the core, plugins and themes.
     *
     * @property $notices
     * @author Marcel Santing
     * this class handles import of vulnerabilities, notifying and informing the user.
     *
     */
    class rsssl_vulnerabilities
    {
        const RSSSL_VULNERABILITIES_LOCATION = '/really-simple-ssl';
        const RSSSL_SECURITY_API = 'https://downloads.really-simple-security.com/rsssl/vulnerabilities/V1/';
        public $workable_plugins = [];

        /**
         * interval to download new jsons
         */
        public $interval = 12 * HOUR_IN_SECONDS;
        public $update_count = 0;

        protected $risk_naming = [];

        /**
         * @var array|int[]
         */
        public $risk_levels = [
            'l' => 1,
            'm' => 2,
            'h' => 3,
            'c' => 4,
        ];
        public $jsons_files_updated = false;

        public function __construct()
        {
            $this->risk_naming = [
                'l' => __('low-risk', 'really-simple-ssl'),
                'm' => __('medium-risk', 'really-simple-ssl'),
                'h' => __('high-risk', 'really-simple-ssl'),
                'c' => __('critical', 'really-simple-ssl'),
            ];

	        $this->init();
	        add_filter('rsssl_vulnerability_data', array($this, 'get_stats'));

	        //now we add the action to the cron.
	        add_filter('rsssl_every_three_hours_cron', array($this, 'run_cron'));
	        add_filter('rsssl_notices', [$this, 'show_help_notices'], 10, 1);

        }

        public function riskNaming($risk = null)
        {
            if (is_null($risk)) {
                return $this->risk_naming;
            }
            return $this->risk_naming[$risk];
        }

        /* Public Section 1: Class Build-up initialization and instancing */

        public function run_cron(): void {
	        $this->check_files();
	        $this->cache_installed_plugins(true);
	        if ( $this->jsons_files_updated ) {
                if ($this->should_send_mail()) {
	                $this->send_vulnerability_mail();
                }

                $this->check_notice_reset();
	        }
        }

	    /**
         * Check if dismissed notices have to be reset
	     * @return void
	     */
        private function check_notice_reset(): void {
            $this->cache_installed_plugins();
	        $clear_admin_notices_cache = false;
	        foreach ( $this->risk_levels as $level => $int_level ) {
		        if ( $this->should_reset_notification($level) ) {
			        delete_option("rsssl_" . 'risk_level_' . $level . "_dismissed");
                    $clear_admin_notices_cache = true;
		        }
	        }
            if ($clear_admin_notices_cache) {
	            RSSSL()->admin->clear_admin_notices_cache();
            }
        }

	    /**
	     * Checks the files on age and downloads if needed.
	     * @return void
	     */
	    public function reload_files_on_update(): void {
		    if ( ! rsssl_admin_logged_in() ) {
			    return;
		    }
		    //if the manifest is not older than 4 hours, we don't download it again.
		    if ( $this->get_file_stored_info(false, true) < time() - 14400) {
			    $this->download_manifest();
		    }
		    $this->download_plugin_vulnerabilities();
		    $this->download_core_vulnerabilities();
		    $this->check_notice_reset();
	    }

        public function init(): void {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }
            //we check the rsssl options if the enable_feedback_in_plugin is set to true
            if ( rsssl_get_option('enable_feedback_in_plugin') ) {
                // we enable the feedback in the plugin
                $this->enable_feedback_in_plugin();
                $this->enable_feedback_in_theme();
            }

            //we check if upgrader_process_complete is called, so we can reload the files.
            add_action('upgrader_process_complete', array($this, 'reload_files_on_update'), 10, 2);
            //After activation, we need to reload the files.
            add_action( 'activate_plugin', array($this, 'reload_files_on_update'), 10, 2);

            //same goes for themes.
            add_action('after_switch_theme', array($this, 'reload_files_on_update'), 10, 2);
            add_action('current_screen', array($this, 'show_inline_code'));
        }

        /**
         * Function used for first run of the plugin.
         *
         * @return array
         */
        public static function firstRun(): array
        {
	        if ( ! rsssl_user_can_manage() ) {
		        return [];
	        }
            $self = new self();
	        $self->check_files();
	        $self->cache_installed_plugins(true);

	        return [
		        'request_success' => true,
		        'data' =>   $self->workable_plugins
	        ];
        }

	    /**
         * Get site health notice for vulnerabilities
	     * @return array
	     */
	    public function get_site_health_notice()
	    {
            if (!rsssl_admin_logged_in()){
                return [];
            }

		    $this->cache_installed_plugins();
		    $risks = $this->count_risk_levels();
            if (count($risks) === 0) {
	            return array(
		            'label'       => __( 'No known vulnerabilities detected', 'really-simple-ssl' ),
		            'status'      => 'good',
		            'badge'       => array(
			            'label' => __('Security'),
			            'color' => 'blue',
		            ),
		            'description' => sprintf(
			            '<p>%s</p>',
			            __( 'No known vulnerabilities detected.', 'really-simple-ssl' )
		            ),
		            'actions'     => '',
		            'test'        => 'health_test',
	            );
            }
            $total = 0;
		    foreach ($this->risk_levels as $risk_level => $value) {
                $total += $risks[ $risk_level ] ?? 0;
            }

		    return array(
			    'label'       => __( 'Vulnerabilities detected','really-simple-ssl' ),
			    'status'      => 'critical',
			    'badge'       => array(
				    'label' => __( 'Security' ),
				    'color' => 'blue',
			    ),
			    'description' => sprintf(
				    '<p>%s</p>',
				    sprintf(_n( '%s vulnerability has been detected.', '%s vulnerabilities have been detected.', $total, 'really-simple-ssl' ), number_format_i18n( $total )) . ' '.
				    __( 'Please check the vulnerabilities overview for more information and take appropriate action.' ,'really-simple-ssl' )
			    ),
			    'actions'     => sprintf(
				    '<p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
				    esc_url( __( add_query_arg(array('page'=>'really-simple-security#settings/vulnerabilities/vulnerabilities-overview'), rsssl_admin_url() ) ) ),
				    __( 'View vulnerabilities', 'really-simple-ssl' )
			    ),
			    'test' => 'rsssl_vulnerabilities',
		    );
        }

        public function show_help_notices($notices)
        {
            $this->cache_installed_plugins();
            $risks = $this->count_risk_levels();
	        $level_to_show_on_dashboard = rsssl_get_option('vulnerability_notification_dashboard');
	        $level_to_show_sitewide = rsssl_get_option('vulnerability_notification_sitewide');
            foreach ($this->risk_levels as $key => $value) {
                if ( !isset($risks[$key]) ) {
                    continue;
                }
                //this is shown bases on the config of vulnerability_notification_dashboard
                $siteWide = false;
                $dashboardNotice = false;
                if ( $level_to_show_on_dashboard && $level_to_show_on_dashboard !== '*') {
                    if ($value >= $this->risk_levels[$level_to_show_on_dashboard]) {
	                    $dashboardNotice = true;
                    }
                }
                if ($level_to_show_sitewide && $level_to_show_sitewide !== '*') {
                    if ($value >= $this->risk_levels[$level_to_show_sitewide]) {
                        $siteWide = true;
                    }
                }
                if ( !$dashboardNotice && !$siteWide ) {
                    continue;
                }

                $count = $risks[$key];
                $count_label = _n('vulnerability', 'vulnerabilities', $count, 'really-simple-ssl');
                $notice = [
                    'callback' => '_true_',
                    'score' => 1,
                    'show_with_options' => ['enable_vulnerability_scanner'],
                    'output' => [
                        'true' => [
                            'title' => sprintf(__('You have %s %s %s', 'really-simple-ssl'), $count, $this->risk_naming[$key], $count_label),
                            'msg' => sprintf(__('You have %s %s %s. Please take appropriate action.','really-simple-ssl'), $count, $this->risk_naming[$key],$count_label ),
                            'url' => add_query_arg(['page'=>'really-simple-security#settings/vulnerabilities/vulnerabilities-overview'], rsssl_admin_url() ),
                            'icon' => ($key === 'c' || $key==='h') ? 'warning' : 'open',
                            'type' => 'warning',
                            'dismissible' => true,
                            'admin_notice' => $siteWide,
                            'plusone' => true,
                        ]
                    ],
                ];
                $notices['risk_level_' . $key] = $notice;

            }
            //now we add the test notices for admin and dahboard.

            //if the option is filled, we add the test notice.
            $test_id = get_option('test_vulnerability_tester');
            if($test_id) {
	            $dashboard = rsssl_get_option('vulnerability_notification_dashboard');
	            $side_wide = rsssl_get_option('vulnerability_notification_sitewide');

	            $site_wide_icon = $side_wide === 'l' || $side_wide === 'm' ? 'open' : 'warning';
	            if ( $side_wide === 'l' || $side_wide === 'm' || $side_wide === 'h' || $side_wide === 'c') {
		            $notices[ 'test_vulnerability_sitewide_' .$test_id ] = [
			            'callback'          => '_true_',
			            'score'             => 1,
			            'show_with_options' => [ 'enable_vulnerability_scanner' ],
			            'output'            => [
				            'true' => [
					            'title'        => __( 'Site wide - Test Notification', 'really-simple-ssl' ),
					            'msg'          => __( 'This is a test notification from Really Simple SSL. You can safely dismiss this message.', 'really-simple-ssl' ),
					            'url' => add_query_arg(['page'=>'really-simple-security#settings/vulnerabilities/vulnerabilities-overview'], rsssl_admin_url() ),
					            'icon'         => $site_wide_icon,
					            'dismissible'  => true,
					            'admin_notice' => true,
					            'plusone'      => true,
				            ]
			            ]
		            ];
	            }

                //don't add this one if the same level
	            $dashboard_icon = $dashboard === 'l' || $dashboard === 'm' ? 'open' : 'warning';
	            if ($dashboard_icon !== $site_wide_icon) {
	                if ( $dashboard === 'l' || $dashboard === 'm' || $dashboard === 'h' || $dashboard === 'c' ) {
		                $notices[ 'test_vulnerability_dashboard_' .$test_id ] = [
			                'callback'          => '_true_',
			                'score'             => 1,
			                'show_with_options' => [ 'enable_vulnerability_scanner' ],
			                'output'            => [
				                'true' => [
					                'title'        => __( 'Dashboard - Test Notification', 'really-simple-ssl' ),
					                'msg'          => __( 'This is a test notification from Really Simple SSL. You can safely dismiss this message.', 'really-simple-ssl' ),
					                'icon'         => $dashboard_icon,
					                'dismissible'  => true,
					                'admin_notice' => false,
					                'plusone'      => true,
				                ]
			                ]
		                ];
	                }
                }
            }

            return $notices;
        }

        /**
         * Generate plugin files for testing purposes.
         *
         * @return array
         */
        public static function testGenerator(): array
        {
            $mail_notification = rsssl_get_option('vulnerability_notification_email_admin');
            if ( $mail_notification === 'l' || $mail_notification === 'm' || $mail_notification === 'h' || $mail_notification === 'c' ) {
                $mailer = new rsssl_mailer();
                $mailer->send_test_mail();
            }
            return [];
        }


        /* Public Section 2: DataGathering */

        /**
         * @param $data
         *
         * @return array
         */
        public function get_stats($stats): array
        {
	        if ( ! rsssl_user_can_manage() ) {
		        return $stats;
	        }

	        $this->cache_installed_plugins();
            //now we only get the data we need.
            $vulnerabilities = array_filter($this->workable_plugins, static function ($plugin) {
                if (isset($plugin['vulnerable']) && $plugin['vulnerable']) {
                    return $plugin;
                }
                return false;
            });

	        $time = $this->get_file_stored_info(true);
	        $stats['vulnerabilities'] = count($vulnerabilities);
	        $stats['vulList'] = $vulnerabilities;
            $riskData = $this->measures_data();
            $stats['riskData'] = $riskData['data'];
	        $stats['lastChecked'] = $time;
            return $stats;
        }


	    /**
	     * This combines the vulnerabilities with the installed plugins
	     *
	     * And loads it into a memory cache on page load
	     *
	     */
	    public function cache_installed_plugins($force_update=false): void
	    {
		    if ( ! rsssl_admin_logged_in() ) {
			    return;
		    }

            if ( !$force_update && !empty($this->workable_plugins) ) {
                return;
            }
		    //first we get all installed plugins
		    $installed_plugins = get_plugins();

		    $installed_themes = wp_get_themes();
		    //we flatten the array
		    $update = get_site_transient('update_themes');
		    //we make the installed_themes look like the installed_plugins
		    $installed_themes = array_map( static function ($theme) use ($update) {
			    return [
				    'Name' => $theme->get('Name'),
				    'Slug' => $theme->get('TextDomain'),
				    'description' => $theme->get('Description'),
				    'Version' => $theme->get('Version'),
				    'Author' => $theme->get('Author'),
				    'AuthorURI' => $theme->get('AuthorURI'),
				    'PluginURI' => $theme->get('ThemeURI'),
				    'TextDomain' => $theme->get('TextDomain'),
				    'RequiresWP' => $theme->get('RequiresWP'),
				    'RequiresPHP' => $theme->get('RequiresPHP'),
                    'update_available' => isset($update->response[$theme->get('TextDomain')]),
			    ];
		    }, $installed_themes);

		    //we add a column type to all values in the array
		    $installed_themes = array_map( static function ($theme) {
			    $theme['type'] = 'theme';
			    return $theme;
		    }, $installed_themes);

		    //we add a column type to all values in the array
            //this resets the array keys (currently slugs) so we preserve them in the 'Slug' column.
		    $update = get_site_transient('update_plugins');
		    $installed_plugins = array_map( static function ($plugin, $slug) use ($update) {
  			    $plugin['type'] = 'plugin';
			    $plugin['update_available'] = isset($update->response[$slug]);
                $plugin['Slug'] = dirname($slug);
                $plugin['File'] = $slug;
			    return $plugin;
		    }, $installed_plugins, array_keys($installed_plugins) );

		    //we merge the two arrays
		    $installed_plugins = array_merge($installed_plugins, $installed_themes);

		    //now we get the components from the file
		    $components = $this->get_components();
            //We loop through plugins and check if they are in the components array
		    foreach ($installed_plugins as $plugin) {
			    $slug = $plugin['Slug'];
			    $plugin['vulnerable'] = false;
			    if( $plugin['type'] === 'theme' ) {
				    // we check if the theme exists as a directory
                    $plugin['folder_exists'] = file_exists(get_theme_root() . '/' . $slug );
			    }

			    if( $plugin['type'] === 'plugin' ) {
				    //also we check if the folder exists for the plugin we added this check for later purposes
                    $plugin['folder_exists'] = file_exists(WP_PLUGIN_DIR . '/' . dirname($slug) );
			    }

			    //if there are no components, we return
			    if ( !empty($components) ) {
				    foreach ($components as $component) {
					    if ($plugin['Slug'] === $component->slug) {
						    if (!empty($component->vulnerabilities) && $plugin['folder_exists'] === true) {
							    $plugin['vulnerable'] = true;
							    $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
							    $plugin['rss_identifier'] = $this->getLinkedUUID($component->vulnerabilities, $plugin['risk_level']);
							    $plugin['risk_name'] = $this->risk_naming[$plugin['risk_level']];
							    $plugin['date'] = $this->getLinkedDate($component->vulnerabilities, $plugin['risk_level']);
						    }
					    }
				    }
			    }

			    //we walk through the components array
			    $this->workable_plugins[$slug] = $plugin;
		    }


		    //now we get the core information
		    $core = $this->get_core();

		    //we create a plugin like entry for core to add to the workable_plugins array
		    $core_plugin = [
			    'Name' => 'WordPress',
			    'Slug' => 'wordpress',
			    'Version' => $core->version?? '',
			    'Author' => 'WordPress',
			    'AuthorURI' => 'https://wordpress.org/',
			    'PluginURI' => 'https://wordpress.org/',
			    'TextDomain' => 'wordpress',
			    'type' => 'core',
		    ];
		    $core_plugin['vulnerable'] = false;
		    //we check if there is an update available
		    $update = get_site_transient('update_core');
		    if (isset($update->updates[0]->response) && $update->updates[0]->response === 'upgrade') {
			    $core_plugin['update_available'] = true;
		    } else {
			    $core_plugin['update_available'] = false;
		    }
		    //if there are no components, we return
		    if ( !empty($core->vulnerabilities) ) {
			    $core_plugin['vulnerable'] = true;
			    $core_plugin['risk_level'] = $this->get_highest_vulnerability($core->vulnerabilities);
			    $core_plugin['rss_identifier'] = $this->getLinkedUUID($core->vulnerabilities, $core_plugin['risk_level']);
			    $core_plugin['risk_name'] = $this->risk_naming[$core_plugin['risk_level']];
			    $core_plugin['date'] = $this->getLinkedDate($core->vulnerabilities, $core_plugin['risk_level']);
		    }
		    //we add the core plugin to the workable_plugins array
		    $this->workable_plugins['wordpress'] = $core_plugin;
        }


        /* Public Section 3: The plugin page add-on */
        /**
         * Callback for the manage_plugins_columns hook to add the vulnerability column
         *
         * @param $columns
         */
        public function add_vulnerability_column($columns)
        {
            $columns['vulnerability'] = __('Vulnerabilities', 'really-simple-ssl');

            return $columns;
        }

        /**
         * Get the data for the risk vulnerabilities table
         * @param $data
         * @return array
         */
        public function measures_data(): array
        {
            $measures = [];
            $measures[] = [
                'id' => 'force_update',
                'name' => __('Force update', 'really-simple-ssl'),
                'value' => get_option('rsssl_force_update'),
                'description' => sprintf(__('Will run a frequent update process on vulnerable components.', 'really-simple-ssl'), $this->riskNaming('l')),
            ];
            $measures[] = [
                'id' => 'quarantine',
                'name' => __('Quarantine', 'really-simple-ssl'),
                'value' => get_option('rsssl_quarantine'),
                'description' => sprintf(__('Components will be quarantined if the update process fails.', 'really-simple-ssl'), $this->riskNaming('m')),
            ];

            return [
                "request_success" => true,
                'data' => $measures
            ];
        }

	    /**
         * Store the mesures from the api
	     * @param $measures
	     *
	     * @return array
	     */
        public function measures_set($measures): array {
            if (!rsssl_user_can_manage()) {
                return [];
            }

            $risk_data = $measures['riskData'] ?? [];
	        foreach ( $risk_data as $risk ) {
                if ( !isset($risk['value']) ) {
                    continue;
                }
                update_option('rsssl_'.sanitize_title($risk['id']), $this->sanitize_measure($risk['value']), false );
            }
            return [];
        }

	    /**
         * Sanitize a measure
         *
	     * @param string $measure
	     *
	     * @return mixed|string
	     */
        public function sanitize_measure($measure) {
            return isset($this->risk_levels[$measure]) ? $measure : '*';
        }

        /**
         * Callback for the manage_plugins_custom_column hook to add the vulnerability field
         *
         * @param string $column_name
         * @param string $plugin_file
         */
        public function add_vulnerability_field( string $column_name, string $plugin_file): void {
            if ( ( $column_name === 'vulnerability' ) ) {
	            $this->cache_installed_plugins();
                if ($this->check_vulnerability( $plugin_file ) ) {
	                switch ( $this->check_severity( $plugin_file ) ) {
		                case 'c':
			                echo sprintf( '<a class="rsssl-btn-vulnerable rsssl-critical" target="_blank" href="%s">%s</a>',
				                'https://really-simple-ssl.com/vulnerabilities/' . $this->getIdentifier( $plugin_file ), ucfirst( $this->risk_naming['c'] ) );
			                break;
		                case 'h':
			                echo sprintf( '<a class="rsssl-btn-vulnerable rsssl-high" target="_blank" href="%s">%s</a>',
				                'https://really-simple-ssl.com/vulnerabilities/' . $this->getIdentifier( $plugin_file ), ucfirst( $this->risk_naming['h'] ) );
			                break;
		                case 'm':
			                echo sprintf( '<a class="rsssl-btn-vulnerable rsssl-medium" target="_blank" href="%s">%s</a>',
				                'https://really-simple-ssl.com/vulnerabilities/' . $this->getIdentifier( $plugin_file ), ucfirst( $this->risk_naming['m'] ) );
			                break;
		                default:
			                echo sprintf( '<a class="rsssl-btn-vulnerable rsssl-low" target="_blank" href="%s">%s</a>',
				                'https://really-simple-ssl.com/vulnerabilities/' . $this->getIdentifier( $plugin_file ), ucfirst( $this->risk_naming['l'] ) );
			                break;
	                }
                }
                if ( $this->is_quarantined($plugin_file)) {
	                echo sprintf( '<a class="rsssl-btn-vulnerable rsssl-critical" target="_blank" href="%s">%s</a>',
		                'https://really-simple-ssl.com/instructions/about-vulnerabilities/#quarantine' , __("Quarantined","really-simple-ssl") );
                }
            }
        }

        /**
         * Callback for the admin_enqueue_scripts hook to add the vulnerability styles
         *
         * @param $hook
         *
         * @return void
         */
        public function add_vulnerability_styles($hook)
        {
            if ('plugins.php' !== $hook) {
                return;
            }
            //only on settings page
            $rtl = is_rtl() ? 'rtl/' : '';
            $url = trailingslashit(rsssl_url) . "assets/css/{$rtl}rsssl-plugin.min.css";
            $path = trailingslashit(rsssl_path) . "assets/css/{$rtl}rsssl-plugin.min.css";
            if (file_exists($path)) {
                wp_enqueue_style('rsssl-plugin', $url, array(), rsssl_version);
            }
        }

        /**
         * checks if the plugin is vulnerable
         *
         * @param $plugin_file
         *
         * @return mixed
         */
        private function check_vulnerability($plugin_file)
        {
            return $this->workable_plugins[ dirname($plugin_file) ]['vulnerable'] ?? false;
        }

	    /**
         * Check if a plugin is quarantined
	     *
	     * @param string $plugin_file
	     *
	     * @return bool
	     */
	    private function is_quarantined(string $plugin_file): bool {
            return strpos($plugin_file, '-rsssl-q-')!==false;
	    }

        /**
         * checks if the plugin's severity closed
         *
         * @param $plugin_file
         *
         * @return mixed
         */
        private function check_severity($plugin_file)
        {
            return $this->workable_plugins[dirname($plugin_file)]['risk_level'];
        }

        private function getIdentifier($plugin_file)
        {
            return $this->workable_plugins[dirname($plugin_file)]['rss_identifier'];
        }
        /* End of plug-in page add-on */


        /* Public and private functions | Files and storage */

        /**
         * Checks the files on age and downloads if needed.
         *
         * @return void
         */
        public function check_files(): void
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }

            //we download the manifest file if it doesn't exist or is older than 12 hours
            if ($this->validate_local_file(false, true)) {
                if ( $this->get_file_stored_info(false, true) < time() - $this->interval ) {
                    $this->download_manifest();
                }
            } else {
                $this->download_manifest();
            }
            //We check the core vulnerabilities and validate age and existence
            if ($this->validate_local_file(true, false)) {
                //if the file is younger than 12 hours, we don't download it again.
                if ($this->get_file_stored_info(true) < time() - $this->interval ) {
                    $this->download_core_vulnerabilities();
                }

            } else {
                $this->download_core_vulnerabilities();
            }

            //We check the plugin vulnerabilities and validate age and existence
            if ($this->validate_local_file()) {
                if ($this->get_file_stored_info() < time() - $this->interval ) {
                    $this->download_plugin_vulnerabilities();
                }
            } else {
                $this->download_plugin_vulnerabilities();
            }
        }


        /**
         * Checks if the file is valid and exists. It checks three files: the manifest, the core vulnerabilities and the plugin vulnerabilities.
         *
         * @param bool $isCore
         * @param bool $manifest
         *
         * @return bool
         */
        private function validate_local_file(bool $isCore = false, bool $manifest = false): bool
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return false;
	        }
            if (!$manifest) {
                //if we don't check for the manifest, we check the other files.
                $isCore ? $file = 'core.json' : $file = 'components.json';
            } else {
                $file = 'manifest.json';
            }

            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $file = $upload_dir . self::RSSSL_VULNERABILITIES_LOCATION . '/' . $file;
            if (file_exists($file)) {
                //now we check if the file is older than 3 days, if so, we download it again
                $file_time = filemtime($file);
                $now = time();
                $diff = $now - $file_time;
                $days = floor($diff / (60 * 60 * 24));
                if ($days < 1) {
                    return true;
                }
            }

            return false;
        }


        /**
         * Downloads bases on given url
         *
         * @param string $url
         *
         * @return mixed|null
         * @noinspection PhpComposerExtensionStubsInspection
         */
        private function download(string $url)
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return null;
	        }
            //now we check if the file remotely exists and then log an error if it does not.
            $headers = get_headers($url);
            if (strpos($headers[0], '200')) {
                //file exists, download it
                $json = file_get_contents($url);

                return json_decode($json);
            }
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log('Could not download file from ' . $url);
            }

            return null;
        }

        /**
         * Stores a full core or component file in the upload folder
         *
         * @param $data
         * @param bool $isCore
         * @param bool $manifest
         *
         * @return void
         */
        private function store_file($data, bool $isCore = false, bool $manifest = false): void
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }
	        //we get the upload directory
	        $upload_dir = wp_upload_dir();
	        $upload_dir = $upload_dir['basedir'];
	        $upload_dir = $upload_dir . self::RSSSL_VULNERABILITIES_LOCATION;

	        if ( !$manifest ) {
		        $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');
	        } else {
		        $file = $upload_dir . '/manifest.json';
	        }
	        //we delete the old file if it exists
	        if ( file_exists($file) ) {
		        wp_delete_file($file);
	        }

            //if the data is empty, we return null
            if ( empty($data) ) {
                return;
            }

            //we check if the directory exists, if not, we create it
            if ( !file_exists($upload_dir) ) {
                mkdir($upload_dir, 0755, true);
            }

            FileStorage::StoreFile($file, $data);
            $this->jsons_files_updated = true;
        }

        public function get_file_stored_info($isCore = false, $manifest = false)
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return false;
	        }
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir .= self::RSSSL_VULNERABILITIES_LOCATION;
            if ($manifest) {
                $file = $upload_dir . '/manifest.json';
                if (!file_exists($file)) {
                    return false;
                }

                return FileStorage::GetDate($file);
            }
            $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');
            if (!file_exists($file)) {
                return false;
            }

            return FileStorage::GetDate($file);
        }

        /* End of files and Storage */

        /* Section for the core files Note: No manifest is needed */

        /**
         * Downloads the vulnerabilities for the current core version.
         *
         * @return void
         */
        protected function download_core_vulnerabilities(): void
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }
            global $wp_version;
            $url = self::RSSSL_SECURITY_API . 'core/WordPress.json';
            $data = $this->download($url);
            if (!$data) {
                return;
            }

            $data->vulnerabilities = $this->filter_vulnerabilities($data->vulnerabilities, $wp_version, true);
            $data->version = $wp_version;
            //first we store this as a json file in the uploads folder
            $this->store_file($data, true);
        }

        /* End of core files section */


        /* Section for the plug-in files */
        /**
         * Downloads the vulnerabilities for the current plugins.
         *
         * @return void
         */
        protected function download_plugin_vulnerabilities(): void
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }
            //we get all the installed plugins
            $installed_plugins = get_plugins();
            //first we get the manifest file
            $manifest = $this->getManifest();
            $vulnerabilities = [];
            foreach ($installed_plugins as $file => $plugin) {
                $slug = dirname($file);
	            $installed_plugins[ $file ]['Slug'] = $slug;
                $url = self::RSSSL_SECURITY_API . 'plugin/' . $slug . '.json';
                //if the plugin is not in the manifest, we skip it
                if (!in_array($slug, (array)$manifest)) {
                    continue;
                }
                $data = $this->download($url);
                if ($data !== null) {
                    $vulnerabilities[] = $data;
                }
            }
            //we also do it for all the installed themes
            $installed_themes = wp_get_themes();
            foreach ($installed_themes as $theme) {
                $theme = $theme->get('TextDomain');
                $url = self::RSSSL_SECURITY_API . 'theme/' . $theme . '.json';

                //if the plugin is not in the manifest, we skip it
                if (!in_array($theme, (array)$manifest)) {
                    continue;
                }

                $data = $this->download($url);

                if ($data !== null) {
                    $vulnerabilities[] = $data;
                }
            }

            //we make the installed_themes look like the installed_plugins
            $installed_themes = array_map( static function ($theme) {
                return [
                    'Name' => $theme->get('Name'),
                    'Slug' => $theme->get('TextDomain'),
                    'description' => $theme->get('Description'),
                    'Version' => $theme->get('Version'),
                    'Author' => $theme->get('Author'),
                    'AuthorURI' => $theme->get('AuthorURI'),
                    'PluginURI' => $theme->get('ThemeURI'),
                    'TextDomain' => $theme->get('TextDomain'),
                    'RequiresWP' => $theme->get('RequiresWP'),
                    'RequiresPHP' => $theme->get('RequiresPHP'),
                ];
            }, $installed_themes);

            //we merge $installed_plugins and $installed_themes
            $installed_plugins = array_merge($installed_plugins, $installed_themes);
            //we filter the vulnerabilities
            $vulnerabilities = $this->filter_active_components($vulnerabilities, $installed_plugins);
            $this->store_file($vulnerabilities);
        }

        /**
         * Loads the info from the files Note this is also being used for the themes.
         *
         * @return mixed|null
         */
        private function get_components()
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return [];
	        }
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSSSL_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/components.json';
            if (!file_exists($file)) {
                return [];
            }

            $components =  FileStorage::GetFile($file);
            if (!is_array($components)) $components = [];
            return $components;
        }

        /* End of plug-in files section */

        /* Section for the core files Note: No manifest is needed */
        private function get_core()
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return null;
	        }
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSSSL_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/core.json';
            if (!file_exists($file)) {
                return false;
            }

            return FileStorage::GetFile($file);
        }

        /* Section for the theme files */

        public function enable_feedback_in_theme(): void {
            //Logic here for theme warning Create Callback and functions for these steps
            //we only display the warning for the theme page
            add_action('current_screen', [$this, 'show_theme_warning']);
        }

        public function show_theme_warning($hook)
        {
            $screen = get_current_screen();

            if ($screen && $screen->id !== 'themes') {
                return;
            }

            //we add warning scripts to themes
            add_action('admin_enqueue_scripts', [$this, 'enqueue_theme_warning_scripts']);

        }

        public function show_inline_code($hook): void {
            if ($hook !== 'themes.php') {
                return;
            }
            //we add warning scripts to themes
            add_action('admin_footer', [$this, 'enqueue_theme_warning_scripts']);
        }

        public function enqueue_theme_warning_scripts(): void {
            //we get all components with vulnerabilities
            $components = $this->get_components();
            ob_start();?>
                <script>
                    window.addEventListener("load", () => {
                    let style = document.createElement('style');
                    let vulnerable_components = [<?php echo implode(',', array_map(function ($component) {
                        return "{slug: '" . esc_attr($component->slug) . "', risk: '" . esc_attr($this->get_highest_vulnerability($component->vulnerabilities)) . "'}";
                    }, $components)) ?>];

                    //we create the style for warning
                    style.innerHTML = '.rsssl-theme-notice {box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); position:relative; z-index:50; margin-bottom: -35px; padding: 8px 12px;}';
                    style.innerHTML += '.rsssl-theme-notice-warning {background-color: #FFF6CE; border-left: 4px solid #ffb900;}';
                    //we create the style for danger
                    style.innerHTML += '.rsssl-theme-notice-danger {background-color: #FFCECE; border-left: 4px solid #dc3232;}';
                    style.innerHTML += '.rsssl-theme-notice-below-notice{margin-top: 41px;}';
                    style.innerHTML += '.rsssl-theme-notice-warning .dashicons, .rsssl-theme-notice-danger .dashicons{margin-right: 12px;}';
                    let levels = <?php echo json_encode($this->risk_naming)?>;

                    //we add the style to the head
                    document.head.appendChild(style);
                    //we loop through the components
                    vulnerable_components.forEach(function(component) {
                        //we get the theme element
                        let theme_element = document.querySelector(".theme[data-slug='"+component.slug+"']");
                        //if the theme exists
                        if (theme_element) {
                            //check if theme element contains notice. if so, push this notice down with class rsssl-theme-notice-below-notice
                            let hasNotice = theme_element.querySelector('.update-message.notice');
                            //we check the risk
                            let level = levels[component.risk];
                            let text = '<?php echo esc_attr(__('Vulnerability: %s', 'really-simple-ssl')) ?>';
                            text = text.replace('%s', level);
                            let divClass = ' rsssl-theme-notice ';
                            divClass += component.risk === 'h' || component.risk === 'c' ? 'rsssl-theme-notice-danger' : 'rsssl-theme-notice-warning';
                            if (hasNotice) divClass += ' rsssl-theme-notice-below-notice';
                            theme_element.insertAdjacentHTML('afterbegin', `
                              <div class="${divClass}">
                                <div><span class="dashicons dashicons-info"></span>${text}</div>
                              </div>
                            `);
                        }
                    });
                    //find quarantined themes, find all themes where the data-slug contains '-rsssl-q'
                    document.querySelectorAll(".theme[data-slug*='-rsssl-q']").forEach(function(theme_element) {
                        //if the theme exists
                        if ( theme_element ) {
                            //we check the risk
                            let text = '<?php echo esc_attr(__('Quarantined', 'really-simple-ssl')) ?>';
                            let divClass = 'rsssl-theme-notice rsssl-theme-notice-danger';
                            theme_element.insertAdjacentHTML('afterbegin', `
                              <div class="${divClass}">
                                <div><span class="dashicons dashicons-info"></span>
                                    <a href="https://really-simple-ssl.com/instructions/about-vulnerabilities/#quarantine" target="_blank">${text}</a>
                                </div>
                              </div>
                            `);
                        }
                    });
                });
            </script>
            <?php
            echo ob_get_clean();
        }

        /* End of theme files section */


        /* Private functions | Filtering and walks */

        /**
         * Filters the components based on the active plugins
         *
         * @param $components
         * @param array $active_plugins
         *
         * @return array
         */
        private function filter_active_components($components, array $active_plugins): array
        {
            $active_components = [];
            foreach ($components as $component) {
                foreach ($active_plugins as $active_plugin) {
                    if (isset($component->slug) && $component->slug === $active_plugin['Slug']) {
                        //now we filter out the relevant vulnerabilities
                        $component->vulnerabilities = $this->filter_vulnerabilities($component->vulnerabilities, $active_plugin['Version']);
                        //if we have vulnerabilities, we add the component to the active components or when the plugin is closed
                        if (count($component->vulnerabilities) > 0 || $component->status === 'closed') {
                            $active_components[] = $component;
                        }
                    }
                }
            }

            return $active_components;
        }

        /**
         * This function adds the vulnerability with the highest risk to the plugins page
         *
         * @param $vulnerabilities
         *
         * @return string
         */
        private function get_highest_vulnerability($vulnerabilities): string
        {
            //we loop through the vulnerabilities and get the highest risk level
            $highest_risk_level = 0;

            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability->severity === null) {
                    continue;
                }
                if (!isset($this->risk_levels[$vulnerability->severity])) {
                    continue;
                }
                if ($this->risk_levels[$vulnerability->severity] > $highest_risk_level) {
                    $highest_risk_level = $this->risk_levels[$vulnerability->severity];
                }
            }
            //we now loop through the risk levels and return the highest one
            foreach ($this->risk_levels as $key => $value) {
                if ($value === $highest_risk_level) {
                    return $key;
                }
            }

            return 'l';
        }

        /* End of private functions | Filtering and walks */


        /* Private functions | End of Filtering and walks */


        /* Private functions | Feedback, Styles and scripts */

        /**
         * This function shows the feedback in the plugin
         *
         * @return void
         */
        private function enable_feedback_in_plugin(): void {
            //we add some styling to this page
            add_action('admin_enqueue_scripts', array($this, 'add_vulnerability_styles'));
            //we add an extra column to the plugins page
            add_filter('manage_plugins_columns', array($this, 'add_vulnerability_column'));
            add_filter('manage_plugins-network_columns', array($this, 'add_vulnerability_column'));
            //now we add the field to the plugins page
            add_action('manage_plugins_custom_column', array($this, 'add_vulnerability_field'), 10, 3);
            add_action('manage_plugins-network_custom_column', array($this, 'add_vulnerability_field'), 10, 3);
        }

        /* End of private functions | Feedback, Styles and scripts */

        /**
         * This function downloads the manifest file from the api server
         *
         * @return void
         */
        private function download_manifest(): void {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }
            $url = self::RSSSL_SECURITY_API . 'manifest.json';
            $data = $this->download($url);

            //we convert the data to an array
            $data = json_decode(json_encode($data), true);

            //first we store this as a json file in the uploads folder
            $this->store_file($data, true, true);
        }

        /**
         * This function downloads the created file from the uploads
         *
         * @return false|void
         */
        private function getManifest()
        {
	        if ( ! rsssl_admin_logged_in() ) {
		        return false;
	        }
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir .= self::RSSSL_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/manifest.json';
            if (!file_exists($file)) {
                return false;
            }

            return FileStorage::GetFile($file);
        }

        private function filter_vulnerabilities($vulnerabilities, $Version, $core = false): array
        {
            $filtered_vulnerabilities = array();
            foreach ($vulnerabilities as $vulnerability) {
                //if fixed_in contains a version, and the current version is higher than the fixed_in version, we skip it as fixed.
                //This needs to be a positive check only, as the fixed_in value is less accurate than the version_from and version_to values
	            if ($vulnerability->fixed_in !== 'not fixed' && version_compare($Version, $vulnerability->fixed_in, '>=') ) {
		            continue;
	            }

                //we have the fields version_from and version_to and their needed operators
                $version_from = $vulnerability->version_from;
                $version_to = $vulnerability->version_to;
                $operator_from = $vulnerability->operator_from;
                $operator_to = $vulnerability->operator_to;
                //we now check if the version is between the two versions
                if (version_compare($Version, $version_from, $operator_from) && version_compare($Version, $version_to, $operator_to)) {
                    $filtered_vulnerabilities[] = $vulnerability;
                }
            }
            return $filtered_vulnerabilities;
        }

	    /**
         * Get count of risk occurrence for each risk level
	     * @return array
	     */
        public function count_risk_levels(): array {
            $plugins = $this->workable_plugins;
            $risk_levels = array();
            foreach ($plugins as $plugin) {
                if (isset($plugin['risk_level'])) {
                    if (isset($risk_levels[$plugin['risk_level']])) {
                        $risk_levels[$plugin['risk_level']]++;
                    } else {
                        $risk_levels[$plugin['risk_level']] = 1;
                    }
                }
            }

            return $risk_levels;
        }

	    /**
	     * check if a a dismissed notice should be reset
         *
	     * @param string $risk_level
	     *
	     * @return bool
	     */
	    private function should_reset_notification(string $risk_level): bool {
		    $plugins = $this->workable_plugins;
		    $vulnerable_plugins = array();
		    foreach ($plugins as $plugin) {
			    if (isset($plugin['risk_level']) && $plugin['risk_level'] === $risk_level) {
				    $vulnerable_plugins[] = $plugin['rss_identifier'];
			    }
		    }
		    $dismissed_for = get_option("rsssl_{$risk_level}_notification_dismissed_for",[]);
		    //cleanup. Check if plugins in mail_sent_for exist in the $plugins array
		    foreach ($dismissed_for as $key => $rss_identifier) {
			    if ( ! in_array($rss_identifier, $vulnerable_plugins) ) {
				    unset($dismissed_for[$key]);
			    }
		    }

		    $diff = array_diff($vulnerable_plugins, $dismissed_for);
		    foreach ($diff as $rss_identifier) {
			    if (!in_array($rss_identifier, $dismissed_for)){
				    $dismissed_for[] = $rss_identifier;
			    }
		    }
		    //add the new plugins to the $dismissed_for array
		    update_option("rsssl_{$risk_level}_notification_dismissed_for", $dismissed_for, false );
		    return !empty($diff);
	    }

	    /**
         * check if a new mail should be sent about vulnerabilities
	     * @return bool
	     */
        private function should_send_mail(): bool {
            $plugins = $this->workable_plugins;
            $vulnerable_plugins = array();
            foreach ($plugins as $plugin) {
                if (isset($plugin['risk_level'])) {
                    $vulnerable_plugins[] = $plugin['rss_identifier'];
                }
            }

            $mail_sent_for = get_option('rsssl_vulnerability_mail_sent_for',[]);
            //cleanup. Check if plugins in mail_sent_for exist in the $plugins array
            foreach ($mail_sent_for as $key => $rss_identifier) {
                if ( ! in_array($rss_identifier, $vulnerable_plugins) ) {
                    unset($mail_sent_for[$key]);
                }
            }

            $diff = array_diff($vulnerable_plugins, $mail_sent_for);
            foreach ($diff as $rss_identifier) {
                if (!in_array($rss_identifier, $mail_sent_for)){
	                $mail_sent_for[] = $rss_identifier;
                }
            }

            //add the new plugins to the mail_sent_for array
	        update_option('rsssl_vulnerability_mail_sent_for',$mail_sent_for, false );
            return !empty($diff);
        }

	    /**
         * Get id by risk level
	     * @param array  $vulnerabilities
	     * @param string $risk_level
	     *
	     * @return mixed|void
	     */
        private function getLinkedUUID( array $vulnerabilities, string $risk_level)
        {
            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability->severity === $risk_level) {
                    return $vulnerability->rss_identifier;
                }
            }
        }

        private function getLinkedDate($vulnerabilities, string $risk_level)
        {
            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability->severity === $risk_level) {
                    //we return the date in a readable format
                    return date(get_option('date_format'), strtotime($vulnerability->published_date));
                }
            }
        }

	    /**
         * Send email warning
	     * @return void
	     */
        public function send_vulnerability_mail(): void {
	        if ( ! rsssl_admin_logged_in() ) {
		        return;
	        }

            //first we check if the user wants to receive emails
            if ( !rsssl_get_option('send_notifications_email') ) {
                return;
            }

            $level_for_email = rsssl_get_option('vulnerability_notification_email_admin');
            if ( !$level_for_email || $level_for_email === '*' ) {
                return;
            }

            //now based on the risk level we send a different email
            $risk_levels = $this->count_risk_levels();
            $total = 0;
            $blocks = [];
            foreach ($risk_levels as $risk_level => $count) {
                if ( $this->risk_levels[$risk_level] >= $this->risk_levels[$level_for_email] ) {
                    $blocks[] = $this->createBlock($risk_level, $count);
                    $total    += $count;
                }
            }

            //date format is named month day year
            $mailer = new rsssl_mailer();
            $mailer->subject = sprintf(__("Vulnerability Alert: %s", "really-simple-ssl"), $this->site_url() );
            $mailer->title = sprintf(__("%s: %s vulnerabilities found", "really-simple-ssl"), $this->date(), $total);
            $message = sprintf(__("This is a vulnerability alert from Really Simple SSL for %s. ","really-simple-ssl"), $this->domain() );
            $mailer->message = $message;
            $mailer->warning_blocks = $blocks;
            if ($total > 0) {
                //if for some reason the total is 0, we don't send an email
                $mailer->send_mail();
            }
        }

	    /**
         * Create an email block by risk level
	     *
	     * @param string $risk_level
	     * @param int    $count
	     *
	     * @return array
	     */
        protected function createBlock(string $risk_level, int $count): array
        {
            $plugin_name = '';
            //if we have only one plugin with this risk level, we can show the plugin name
            //we search it in the list
            if ( $count===1 ){
                $plugins = $this->workable_plugins;
                foreach ($plugins as $plugin) {
                    if (isset($plugin['risk_level']) && $plugin['risk_level'] === $risk_level) {
                        $plugin_name = $plugin['Name'];
                    }
                }
            }
            $vulnerability = _n('vulnerability', 'vulnerabilities', $count, 'really-simple-ssl');
            $risk = $this->risk_naming[$risk_level];
	        $message = $count === 1 ? sprintf(__("A %s vulnerability is found in %s.", "really-simple-ssl"),$risk, $plugin_name) : sprintf(__("Multiple %s vulnerabilities have been found.", "really-simple-ssl"), $risk);

            return [
                'title' => sprintf(__("You have %s %s %s", "really-simple-ssl"), $count, $risk, $vulnerability),
                'message' => $message . ' ' .
                             __('Based on your settings, Really Simple SSL will take appropriate action, or you will need to solve it manually.','really-simple-ssl') .' '.
                             sprintf(__('Get more information from the Really Simple SSL dashboard on %s'), $this->domain() ),
                'url' => "https://really-simple-ssl.com/instructions/about-vulnerabilities/",
            ];
        }

	    /**
	     * Get a nicely formatted date for today's date
	     *
	     * @return string
	     */
	    public function date(): string {
		    return date(get_option('date_format'));
	    }

        /**
         * Get the domain name in a clickable format
         *
         * @return string
         */
	    public function domain(): string {
		    return '<a href="'.$this->site_url().'" target="_blank">'.$this->site_url().'</a>';
	    }

	    /**
         * Cron triggers may sometimes result in http URL's, even though SSL is enabled in Really Simple SSL.
         * We ensure that the URL is returned with https if SSL is enabled.
         *
	     * @return string
	     */
        public function site_url(): string {
            $ssl_enabled = rsssl_get_option('ssl_enabled') || is_ssl();
            $scheme = $ssl_enabled ? 'https' : 'http';
            return get_site_url(null, '', $scheme);
        }

    }

    //we initialize the class
    //add_action('init', array(rsssl_vulnerabilities::class, 'instance'));
    if ( !defined('rsssl_pro_version') ) {
	    $vulnerabilities = new rsssl_vulnerabilities();
    }
}

#########################################################################################
# Functions for the vulnerability scanner   									        #
# These functions are used in the vulnerability scanner like the notices and the api's  #
#########################################################################################
//we clear all the cache when the vulnerability scanner is enabled



function rsssl_vulnerabilities_api( array $response, string $action, $data ): array {
	if ( ! rsssl_user_can_manage() ) {
		return $response;
	}
	switch ($action) {
		case 'vulnerabilities_test_notification':
			//creating a random string based on time.
			$random_string = md5( time() );
			update_option( 'test_vulnerability_tester', $random_string, false );
            //clear admin notices cache
			delete_option('rsssl_admin_notices');
			$response = rsssl_vulnerabilities::testGenerator();
			break;
		case 'vulnerabilities_scan_files':
			$response = rsssl_vulnerabilities::firstRun();
			break;
		case 'vulnerabilities_measures_get':
			$response = ( new rsssl_vulnerabilities )->measures_data();
			break;
		case 'vulnerabilities_measures_set':
			$response = ( new rsssl_vulnerabilities )->measures_set($data);
			break;
	}

	return $response;
}
add_filter( 'rsssl_do_action', 'rsssl_vulnerabilities_api', 10, 3 );

/* End of Routing and API's */
