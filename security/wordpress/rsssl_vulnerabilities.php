<?php

defined('ABSPATH') or die();

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
     * @author Really Simple SSL
     * this class handles database, import of vulnerabilities, and checking for vulnerabilities.
     *
     */
    class rsssl_vulnerabilities
    {
        const RSS_SECURITY_API = 'https://api.really-simple-security.com/storage/downloads/';
        public $workable_plugins;

        private array $notices = [];
        private array $risk_naming = [
            'l' => 'low',
            'm' => 'medium',
            'h' => 'high',
            'c' => 'critical',
        ];

        public function get_vulnerabilities()
        {
            //we loop through the plugins and check if there are any vulnerabilities. and place a notice
            foreach ($this->workable_plugins as $plugin) {
                if (isset($plugin['vulnerable']) && $plugin['vulnerable']) {
                    $this->add_notice($plugin);
                }

            }
        }

        /**
         * Initiate the class
         *
         * @return void
         */
        public function init()
        {
            //we check if the vulnerability scanner is enabled and then the fun happens.
            if ( rsssl_get_option('enable_vulnerability_scanner') ) {
                $this->check_files();
                //first we need to make sure we update the files every day. So we add a daily cron.
	            add_filter( 'rsssl_daily_cron', array($this, 'daily_cron'));

                //we cache the plugins in the class. Since we need quite some info from the plugins.
                $this->cache_installed_plugins();
                $this->get_vulnerabilities();
                //we check the rsssl options if the enable_feedback_in_plugin is set to true
                if (rsssl_get_option('enable_feedback_in_plugin')) {
                    // we enable the feedback in the plugin
                    $this->enable_feedback_in_plugin();

                    //Since we have enabled the feedback in the plugin, we need to check if the user has dismissed the notice.
                    add_action('upgrader_process_complete', array($this, 'reload_files_on_update'), 10, 2);
                    add_action('activate_plugin', array($this, 'reload_files_on_update'), 10, 2);
                }
            }
        }

        public static function add_plugin_notices($notices) {
            $object = new self();
            $object->cache_installed_plugins();
            $object->get_vulnerabilities();
            return array_merge($notices, $object->notices);
        }

        public static function add_startup_notices($notices)
        {
            //we add a notice to the dashboard if the vulnerability scanner is enabled.
            $notices['rsssl_vulnerabilies'] = array(
                'callback' => 'rsssl_vulnerabilities_enabled',
                'score' => 3,
                'output' => array(
                    'true' => array(
                        'msg' => __("Vulnerability check is on, configure notifications.", "really-simple-ssl"),
                        'icon' => 'success',
                        'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities',
                        'dismissible' => true,
                        'highlight_field_id' => 'vulnerability_notification_dashboard',
                    ),
                    'false' => array(
                        'msg' => __("Plugin, core and theme vulnerabilities are not checked.", "really-simple-ssl"),
                        'icon' => 'open',
                        'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities',
                        'dismissible' => true,
                        'highlight_field_id' => 'enable_vulnerability_scanner',
                    ),
                ),
            );
            //we now check for vulnerabilities in the core, plugins and themes. and add a notice if there are any.
            return $notices;
        }

		public function daily_cron() {
			//we check the files on age and download if needed TODO: if premium this will be 4 hours
			$this->check_files();
		}

        /**
         * Callback for the manage_plugins_columns hook to add the vulnerability column
         *
         * @param $columns
         */
        public function add_vulnerability_column($columns)
        {
            $columns['vulnerability'] = __('Notifications', 'really-simple-ssl');
            return $columns;
        }

        /**
         * Callback for the manage_plugins_custom_column hook to add the vulnerability field
         *
         * @param $column_name
         * @param $plugin_file
         */
        public function add_vulnerability_field($column_name, $plugin_file)
        {

            if ($column_name === 'vulnerability') {
                if ($this->check_vulnerability($plugin_file)) {
                    switch ($this->check_severity($plugin_file)) {
                        case 'c':
                            echo '<a class="btn-vulnerable critical">' . __('Critical-Risk', 'really-simple-ssl') . '</a>';
                            break;
                        case 'h':
                            echo '<a class="btn-vulnerable high">' . __('High-Risk', 'really-simple-ssl') . '</a>';
                            break;
                        case 'm':
                            echo '<a class="btn-vulnerable medium-risk">' . __('Medium-Risk', 'really-simple-ssl') . '</a>';
                            break;
                        default:
                            echo '<a class="btn-vulnerable">' . __('Low-Risk', 'really-simple-ssl') . '</a>';
                            break;
                    }
                } else {
                    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                    //now we get the correct slug for the plugin
                    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
                    $plugin_slug = $plugin_data['TextDomain'];

                   //we fetch the data from plugins api
                    $plugin_data = plugins_api('plugin_information', array('slug' => $plugin_slug)); //TODO: replace with security_api last_updated
                    if (!is_wp_error($plugin_data)) {
                        if (property_exists($plugin_data, 'last_updated') && $plugin_data->last_updated !== '') {
                            //we calculate the time difference between now and the last update
                            $time_diff = time() - strtotime($plugin_data->last_updated);
                            echo '<a>' . sprintf(__('Last update: %s days ago', 'really-simple-ssl'), round($time_diff / 86400)) . '</a>';
                        } else {
                            //we show how long the plugin has been installed
                            $time_diff = time() - filemtime(WP_PLUGIN_DIR . '/' . $plugin_file);
                            echo '<a>' . sprintf(__('installed %s days ago', 'really-simple-ssl'), round($time_diff / 86400)) . '</a>';
                        }
                    } else {
                        //we show how long the plugin has been installed
                        $time_diff = time() - filemtime(WP_PLUGIN_DIR . '/' . $plugin_file);
                        echo '<a>' . sprintf(__('installed %s days ago', 'really-simple-ssl'), round($time_diff / 86400)) . '</a>';
                    }
                }
            }
        }

        /**
         * Callback for the admin_enqueue_scripts hook to add the vulnerability styles
         *
         * @param $hook
         * @return void
         */
        public function add_vulnerability_styles( $hook )
        {
            if ( 'plugins.php' !== $hook ) {
                return;
            }
            //only on settings page
            $min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
            $rtl = is_rtl() ? 'rtl/' : '';
            $url = trailingslashit(rsssl_url) . "assets/css/{$rtl}plugin$min.css";
            $path = trailingslashit(rsssl_path) . "assets/css/{$rtl}plugin$min.css";
            if ( file_exists( $path ) ) {
                wp_enqueue_style( 'rsssl-plugin', $url, array(), rsssl_version );
            }
        }

        public function reload_files_on_update()
        {
            $this->download_core_vulnerabilities();
            $this->download_plugin_vulnerabilities();
            $this->cache_installed_plugins();
        }

        /**
         * Instantiates the class
         *
         * @return self
         */
        public static function instance(): self
        {
            static $instance = false;
            if (!$instance) {
                $instance = new rsssl_vulnerabilities();
            }
            return $instance;
        }

        /**
         * Checks the files on age and downloads if needed.
         *
         * @return void
         */
        public function check_files()
        {
            //We check the core vulnerabilities and validate age and existence
            if (!$this->validate_local_file(true)) {
                $this->download_core_vulnerabilities();
            }

            //We check the plugin vulnerabilities and validate age and existence
            if (!$this->validate_local_file()) {
                $this->download_plugin_vulnerabilities();
            }
            $this->cache_installed_plugins();
        }

        public function add_dashboard_notification(array $notices)
        {
            $notices['rsssl_vulnerabilities_risk'] = array(
                'callback' => 'some_callback_function',
                'score' => 4,
                'output' => array(
                    'true' => array(
                        'msg' => '<span class="rsssl-badge rsp-high">'.__("critical", "really-simple-ssl").
                            '</span><span class="rsssl-badge rsp-dark">Askimet</span>' . __("has vulnerabilities.", "really-simple-ssl"),
                        'icon' => 'warning',
                        'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#notifications',
                        'dismissible' => true,
                    ),
                ),
            );
            return $notices;
            //first we get the option for the vulnerability notification
            $vulnerability_notification = get_option('rsssl_vulnerability_notification_dashboard');
            //if the option is not set, we return
            if ($vulnerability_notification === false) {
                return $notices;
            }
            //if the option is set, we add the vulnerability notification
            switch ($vulnerability_notification) {
                case 'low_risk':
                    $notices['rsssl_vulnerabilities_risk'] = array(
                        'score' => 2,
                        'output' => array(
                            'true' => array(
                                'msg' => __("Example of a low risk warning.", "really-simple-ssl"),
                                'icon' => 'warning',
                                'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#notifications',
                                'dismissible' => true,
                            ),
                        ),
                    );
                    break;
                case 'medium_risk':
                    $notices['rsssl_vulnerabilities_risk'] = array(
                        'score' => 3,
                        'output' => array(
                            'true' => array(
                                'msg' => __("Example of medium warning.", "really-simple-ssl"),
                                'icon' => 'warning',
                                'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities#notifications',
                                'dismissible' => true,
                            ),
                        ),
                    );
                    break;
                case 'high_risk':
                    $notices['rsssl_vulnerabilities_risk'] = array(
                        'score' => 4,
                        'msg' => __("Example of high warning.", "really-simple-ssl"),
                        'icon' => 'error',
                        'url' => 'https://really-simple-ssl.com/definition/what-are-application-passwords/',
                        'dismissible' => true,
                    );
                    break;
            }
            return $notices;
        }

        public function test_vulnerability_notification()
        {
            //we add the option vulnerability test notification to true
            add_option('rsssl_test_vulnerability_notification', 'true');

            //Everything went according to plan
            return [
                'success' => true,
                'message' => __('Please validate your settings.', "really-simple-ssl")
            ];
        }

        /**
         * Checks if the file is valid
         *
         * @param bool $isCore
         * @return bool
         */
        private function validate_local_file(bool $isCore = false): bool
        {
            $isCore ? $file = 'core.json' : $file = 'components.json';
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . '/rsssl';
            $file = $upload_dir . '/' . $file;

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
         * Downloads the vulnerabilities for the current core version.
         *
         * @return void
         */
        private function download_core_vulnerabilities(): void
        {
            global $wp_version;
            $wp_version = '6.0.1'; //TODO: remove this line
            $url = self::RSS_SECURITY_API . 'core/wp-core_' . $wp_version . '.json';
            $data = $this->download($url);

            //we convert the data to an array
            $data = json_decode(json_encode($data), true);

            //first we store this as a json file in the uploads folder
            $this->store_file($data, true);
        }

        private function download_plugin_vulnerabilities()
        {
            //we get all the installed plugins
            $installed_plugins = get_plugins();
            $vulnerabilities = [];
            foreach ($installed_plugins as $plugin) {
                $plugin = $plugin['TextDomain'];
                $url = self::RSS_SECURITY_API .'plugin/' . $plugin . '.json';
                $data = $this->download($url);
                if ($data !== null)
                    $vulnerabilities[] = $data;
            }

            $vulnerabilities = $this->filter_active_components($vulnerabilities, $installed_plugins);

            $this->store_file($vulnerabilities);
        }

        private function download($url)
        {
            //now we check if the file remotely exists and then log an error if it does not.
            $headers = get_headers($url);
            if (strpos($headers[0], '200')) {
                //file exists, download it
                $json = file_get_contents($url);
                return json_decode($json);
            }
            $this->log_error('Could not download file from ' . $url);
            return null;
        }

        private function log_error(string $string)
        {
            error_log($string);
        }

        private function filter_active_components($components, array $active_plugins): array
        {
            $active_components = [];
            foreach ($components as $component) foreach ($active_plugins as $active_plugin) if ($component->slug === $active_plugin['TextDomain']) {
                //if the vulnerabilities are empty, we skip this component
                if (count($component->vulnerabilities) === 0) {
                    continue;
                }
                //now we loop through the vulnerabilities of the component
                foreach ($component->vulnerabilities as $index => $vulnerability) {
                    //if the max_version is null, we skip this vulnerability
                    if ($vulnerability->max_version === null) {
                        unset($component->vulnerabilities[$index]);
                    }
                    //if the max_version is lower than the current version, we skip this vulnerability
                    if (version_compare($vulnerability->max_version, $active_plugin['Version'], '<')) {
                        unset($component->vulnerabilities[$index]);
                    }
                    //if the min_version is not null we check the following
                    if ($vulnerability->min_version !== null) {
                        //if the min_version is higher than the current version, we skip this vulnerability
                        if (version_compare($vulnerability->min_version, $active_plugin['Version'], '>')) {
                            unset($component->vulnerabilities[$index]);
                        }
                    }
                }
                $active_components[] = $component;
            }
            return $active_components;
        }

        /**
         * Stores a full core or component file in the upload folder
         *
         * @param $data
         * @param bool $isCore
         * @return void
         */
        private function store_file($data, bool $isCore = false): void
        {
            //if the data is empty, we return null
            if (empty($data)) {
                return;
            }
            //we get the upload directory
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . '/rsssl';

            $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');

            //we check if the directory exists, if not, we create it
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            //we delete the old file if it exists
            if (file_exists($file)) {
                wp_delete_file($file);
            }

            file_put_contents($file, json_encode($data));
        }

        /**
         * This function shows the feedback in the plugin
         *
         * @return void
         */
        private function cache_installed_plugins()
        {
            //first we get all installed plugins
            $installed_plugins = get_plugins();
            //now we get the components from the file
            $components = $this->get_components();
            //if there are no components, we return
            if (empty($components)) {
                return;
            }
            //We loop through plugins and check if they are in the components array
            foreach ($installed_plugins as $key => $plugin) {
                $plugin['vulnerable'] = false;
                //we walk through the components array
                foreach ($components as $component) {
                    if ($plugin['TextDomain'] === $component->slug) {
                        if (!empty($component->vulnerabilities)) {
                            $plugin['vulnerable'] = true;
                            $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
                            $plugin['closed'] = $component->closed;
                        }
                    }
                }
                $this->workable_plugins[$key] = $plugin;
            }
        }

        private function get_components()
        {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . '/rsssl';
            $file = $upload_dir . '/components.json';
            if (!file_exists($file)) {
                return null;
            }
            $json = file_get_contents($file);
            return json_decode($json);
        }

        private function enable_feedback_in_plugin()
        {
            //we add some styling to this page
            add_action('admin_enqueue_scripts', array($this, 'add_vulnerability_styles'));
            //we add an extra column to the plugins page
            add_filter('manage_plugins_columns', array($this, 'add_vulnerability_column'));
            //now we add the field to the plugins page
            add_action('manage_plugins_custom_column', array($this, 'add_vulnerability_field'), 10, 3);
        }

        private function check_vulnerability($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['vulnerable'];
        }

        private function check_severity($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['risk_level'];
        }

        /**
         * This function adds the vulnerability with the highest risk to the plugins page
         *
         * @param $vulnerabilities
         * @return string
         */
        private function get_highest_vulnerability($vulnerabilities):string
        {
            //first we scale our risk levels
            $risk_levels = array(
                'l' => 1,
                'm' => 2,
                'h' => 3,
                'c' => 4
            );
            //we loop through the vulnerabilities and get the highest risk level
            $highest_risk_level = 0;
            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability->rss_severity === null) {
                    continue;
                }

                if(!isset($risk_levels[$vulnerability->rss_severity])){
                    continue;
                }
                if ($risk_levels[$vulnerability->rss_severity] > $highest_risk_level) {
                    $highest_risk_level = $risk_levels[$vulnerability->rss_severity];
                }
            }
            //we now loop through the risk levels and return the highest one
            foreach ($risk_levels as $key => $value) {
                if ($value === $highest_risk_level) {
                    return $key;
                }
            }
            return '';
        }

        private function add_notice($plugin)
        {
            $riskSetting = rsssl_get_option('vulnerability_notification_dashboard');
            if (!$riskSetting) {
                $risk = 'low';
            } else {
                if($plugin['risk_level'] === ''){
                    $risk = 'medium';
                } else {
                    $risk = $this->risk_naming[$plugin['risk_level']];
                }

            }

            //we then build the notice
            $this->notices[$plugin['TextDomain'].'-'.$plugin['Version']] = array(
                'callback' => 'some_callback_function',
                'score' => 1,
                'output' => array(
                    'true' => array(
                        'msg' => '<span class="rsssl-badge rsp-'.$risk.'">'.__($risk, "really-simple-ssl").
                            '</span><span class="rsssl-badge rsp-dark">'.$plugin['Name'].'</span>' . __("has vulnerabilities.", "really-simple-ssl"),
                        'icon' => 'open',
                        'url' => 'https://really-simple-ssl.com/instructions/about-vulnerabilities', //TODO: add link to vulnerability page
                        'dismissible' => true,
                    ),
                ),
            );
        }
    }
}
//add_filter('rsssl_notices', ['rsssl_vulnerabilities', 'add_startup_notices']);

//if the function rsssl_vulnerabilities does not exist, we create it
if (!function_exists('rsssl_vulnerabilities')) {
    /**
     * Returns the RSSSL_Vulnerabilities instance
     *
     * @return RSSSL_Vulnerabilities
     */
    function rsssl_vulnerabilities()
    {
        global $rsssl_vulnerabilities;
        if (!isset($rsssl_vulnerabilities)) {
            $rsssl_vulnerabilities = new rsssl_vulnerabilities();
        }

        $rsssl_vulnerabilities->init();


    }
    add_action('admin_init', 'rsssl_vulnerabilities');
}
//we now check add notifications onboarding and vulnerability
add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_startup_notices'));
add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_plugin_notices'));
function rsssl_vulnerabilities_enabled()
{
    return rsssl_get_option('enable_vulnerability_scanner');
}


function rsssl_vulnerabilities_enabled_dashboard()
{
    return false;
}

function some_callback_function() {
    return true;
}