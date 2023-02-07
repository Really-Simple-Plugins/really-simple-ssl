<?php defined('ABSPATH') or die();

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

        /**
         * Initiate the class
         *
         * @return void
         */
        public function init()
        {
            if (rsssl_get_option('enable_vulnerability_scanner')) {
                //we also enable the wp cron to force download every day
                add_action('rsssl_vulnerability_check', array($this, 'check_files'));
                if (!wp_next_scheduled('rsssl_vulnerability_check')) {
                    wp_schedule_event(time(), 'daily', 'force_download_vulnerabilities');
                }

                //we check the files on age and download if needed TODO: if premium this will be 4 hours
                $this->check_files();

                //we cache the plugins in the class.
                $this->cache_installed_plugins();

                //we check the rsssl options if the enable_feedback_in_plugin is set to true
                if (rsssl_get_option('enable_feedback_in_plugin')) {
                    $this->enable_feedback_in_plugin();
                }
                //also when a plugin is installed or updated we check for vulnerabilities
                add_action('upgrader_process_complete', array($this, 'reload_files_on_update'), 10, 2);
                add_action('activate_plugin', array($this, 'reload_files_on_update'), 10, 2);
            }
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
                        case 'l':
                            echo '<a class="btn-vulnerable">' . __('Low-Risk', 'really-simple-ssl') . '</a>';
                            break;
                    }
                } else {
                   echo 'Coming soon some nice info';
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
            $wp_version = '6.0.1';
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
                        }
                    }
                    //if the plugin is vulnerable, we get the highest vulnerability
                    if ($plugin['vulnerable']) {
                        $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
                    }
                }
                $installed_plugins[$key] = $plugin;
            }
            //we now cache the installed plugins
            $this->workable_plugins = $installed_plugins;
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
            echo "<pre>";
            var_dump($this->workable_plugins[$plugin_file]);

            return $this->workable_plugins[$plugin_file]['vulnerable'];
        }

        private function check_severity($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['risk_level'];
        }

        private function get_highest_vulnerability($vulnerabilities)
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
                if ($risk_levels[$vulnerability->rss_severity] > $highest_risk_level) {
                    $highest_risk_level = $risk_levels[$vulnerability->rss_severity];
                }
            }

            return array_key_first(array_filter($risk_levels, function ($value) use ($highest_risk_level) {
                return $value === $highest_risk_level;
            }, ARRAY_FILTER_USE_BOTH));
        }
    }
}

add_action('admin_init', 'rsssl_vulnerabilities');

function rsssl_vulnerabilities()
{
    global $rsssl_vulnerabilities;
    if (!isset($rsssl_vulnerabilities)) {
        $rsssl_vulnerabilities = new RSSSL_Vulnerabilities();
    }
    $rsssl_vulnerabilities->init();
    return $rsssl_vulnerabilities;
}
