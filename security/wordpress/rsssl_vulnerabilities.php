<?php

defined('ABSPATH') or die();

//including the file storage class
require_once(rsssl_path . 'library/FileStorage.php');

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
        const RSS_VULNERABILITIES_LOCATION = '/really-simple-ssl';
        const RSS_SECURITY_API = 'https://api.really-simple-security.com/storage/downloads/';
        public $workable_plugins = [];

        /**
         * interval every 24 hours
         */
        public $interval = 86400;

        private $notices = [];

        private $admin_notices = [];
        private $risk_naming = [
            'l' => 'low',
            'm' => 'medium',
            'h' => 'high',
            'c' => 'critical',
        ];

        /**
         * @var array|int[]
         */
        private $risk_levels = [
            'l' => 1,
            'm' => 2,
            'h' => 3,
            'c' => 4,
        ];


        public function __construct()
        {
        }


        /* Public Section 1: Class Build-up initialization and instancing */
        /**
         * Initiate the class
         *
         * @return void
         */
        public static function init()
        {
            $self = new self();
            //we check if the vulnerability scanner is enabled and then the fun happens.
            if (rsssl_get_option('enable_vulnerability_scanner')) {
                $self->check_files();
                //first we need to make sure we update the files every day. So we add a daily cron.
                add_filter('rsssl_daily_cron', array($self, 'daily_cron'));

                //we cache the plugins in the class. Since we need quite some info from the plugins.
                $self->cache_installed_plugins();
                //we check the rsssl options if the enable_feedback_in_plugin is set to true
                if (rsssl_get_option('enable_feedback_in_plugin')) {
                    // we enable the feedback in the plugin
                    $self->enable_feedback_in_plugin();
                    //   $this->enable_feedback_in_theme();

                    //TODO: move the actions below to the pro version

                }
                //we add the notices to the notices array.
                $self->get_vulnerabilities();
                //we display the admin notices if available.
                foreach ($self->admin_notices as $notice) {
                    add_action('admin_notices', function () use ($notice) {
                        echo $notice;
                    });
                }
            }
        }

        public static function testDismiss()
        {
            $self = new self();
            //We remove the created plugin files
            foreach ($self->risk_naming as $risk => $name) {
                $self->remove_plugin_files($risk);
            }
        }

        /**
         * Generate plugin files for testing purposes.
         *
         * @return array
         */
        public static function testGenerator(): array
        {
            $vul = new rsssl_vulnerabilities();
            foreach ($vul->risk_naming as $risk => $name) {
                $vul->create_test_plugin($risk);
            }

            return [
                'success' => true,
                'message' => __('A set of test plugins were created.', "really-simple-ssl")
            ];
        }


        private function create_test_plugin($vul)
        {
            $plugin = [
                'Plugin Name' => 'Test Plugin for ' . $this->risk_naming[$vul] . ' vulnerability',
                'PluginURI' => 'https://test.com',
                'Version' => '1.0',
                'Description' => __('This is a test plugin for the vulnerability scanner. To validate the settings of your vulnerabilities configuration please uninstall after testing is done.', 'really-simple-ssl'),
                'Author' => 'Really Simple SSL',
                'AuthorURI' => 'https://test.com',
                'TextDomain' => 'test-plugin-' . $vul,
                'DomainPath' => '',
                'Network' => false,
                'Title' => 'Test Plugin for ' . $this->risk_naming[$vul] . ' vulnerability',
                'AuthorName' => 'Test',
            ];
            //now we create a plugin directory for the plugin.
            $plugin_dir = WP_PLUGIN_DIR . '/test-plugin-' . $vul;
            if (!file_exists($plugin_dir)) {
                mkdir($plugin_dir);
            }
            //we create a plugin file for the plugin.
            $plugin_file = $plugin_dir . '/test-plugin-' . $vul . '.php';
            if (!file_exists($plugin_file)) {
                file_put_contents($plugin_file, '<?php');
            }
            // now we add the name and version in the plugin file.
            $plugin_file_content = file_get_contents($plugin_file);
            $plugin_file_content .= "\n" . '/*' . "\n";
            foreach ($plugin as $key => $value) {
                $plugin_file_content .= ' * ' . $key . ': ' . $value . "\n";
            }
            $plugin_file_content .= ' */';

            file_put_contents($plugin_file, $plugin_file_content);
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


        /* Public Section 2: DataGathering */

        /**
         * Fetches the vulnerabilities from local sources available.
         * then creates notices for the user.
         *
         * @return void
         */
        public function get_vulnerabilities()
        {
            //we loop through the plugins and check if there are any vulnerabilities. and place a notice
            foreach ($this->workable_plugins as $plugin) {
                if (isset($plugin['vulnerable']) && $plugin['vulnerable']) {
                    //first we get our setting
                    $warnAt = rsssl_get_option('vulnerability_notification_dashboard')?? false;

                    //If the setting is not set, we set it to low.
                    if (!$warnAt) {
                        $warnAt = 'l';
                    }

                    if ($plugin['risk_level'] === '') {
                        $plugin['risk_level'] = 'l';
                    }


                    // we do the same for the admin notices.
                    $warnAt = rsssl_get_option('vulnerability_notification_sitewide');
                    if (!$warnAt) {
                        $warnAt = 'l';
                    }
                    if ($this->risk_levels[$plugin['risk_level']] >= $this->risk_levels[$warnAt]) {
                        //we add the notice to the notices array.

                        $message = $this->add_admin_notice($plugin);
                        $this->admin_notices[] = $message;
                    }
                }
            }
        }


        public static function get_stats($data): array
        {
            $self = new self();

            $vulEnabled = rsssl_get_option('enable_vulnerability_scanner');
            $self->cache_installed_plugins();

            //now we only get the data we need.
            $vulnerabilities = array_filter($self->workable_plugins, function ($plugin) {
                if(isset($plugin['vulnerable']) && $plugin['vulnerable'])
                return $plugin;
            });

            $updates = 0;
            //now we fetch all plugins that have an update available.
            foreach ($self->workable_plugins as $plugin) {
                if (isset($plugin['update_available']) && $plugin['update_available']) {
                    $updates++;
                }
            }


            $stats = [
                'vulnerabilities' => count($vulnerabilities),
                'updates' => $updates,
                'lastChecked' => date('d / m / Y @ H:i',$self->get_file_stored_info()),
                'vulEnabled' => $vulEnabled,
            ];
            return [
                "request_success" =>true,
                'data' => $stats
            ];
        }

        /**
         * Merges our feature notices with the notices array.
         *
         * @param $notices
         * @return array
         */
        public static function add_plugin_notices($notices): array
        {
            $object = new self();
            $object->cache_installed_plugins();
            $object->get_vulnerabilities();
            return array_merge($notices, $object->notices);
        }

        /**
         * Adds a notice to the notices array.
         *
         * @param $notices
         */
        public static function add_startup_notices($notices)
        {
            //we add a notice to the dashboard if the vulnerability scanner is enabled.
            $notices['rsssl_vulnerabilities'] = array(
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

        /**
         * Callback for the daily cron to check the files.
         */
        public function daily_cron()
        {
            //we check the files on age and download if needed.
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
        public function add_vulnerability_styles($hook)
        {
            if ('plugins.php' !== $hook) {
                return;
            }
            //only on settings page
            $min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
            $rtl = is_rtl() ? 'rtl/' : '';
            $url = trailingslashit(rsssl_url) . "assets/css/{$rtl}plugin$min.css";
            $path = trailingslashit(rsssl_path) . "assets/css/{$rtl}plugin$min.css";
            if (file_exists($path)) {
                wp_enqueue_style('rsssl-plugin', $url, array(), rsssl_version);
            }
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
                //if the file is younger than 24 hours, we don't download it again.
                if($this->get_file_stored_info(true) > time() - 86400){
                    return;
                }
                $this->download_core_vulnerabilities();
            }

            //We check the plugin vulnerabilities and validate age and existence
            if (!$this->validate_local_file()) {
                if($this->get_file_stored_info() > time() - 86400){
                    return;
                }
                $this->download_plugin_vulnerabilities();
            }
            $this->cache_installed_plugins();
        }

        /* Private functions | Files and storage */

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
            $file = $upload_dir . self::RSS_VULNERABILITIES_LOCATION . $file;

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
        protected function download_core_vulnerabilities(): void
        {
            global $wp_version;
            $wp_version = '6.0.1'; //TODO: remove this line before release
            $url = self::RSS_SECURITY_API . 'core/wp-core_' . $wp_version . '.json';
            $data = $this->download($url);

            //we convert the data to an array
            $data = json_decode(json_encode($data), true);

            //first we store this as a json file in the uploads folder
            $this->store_file($data, true);
        }

        /**
         * Downloads the vulnerabilities for the current plugins.
         *
         * @return void
         */
        protected function download_plugin_vulnerabilities(): void
        {
            //we get all the installed plugins
            $installed_plugins = get_plugins();
            $vulnerabilities = [];
            foreach ($installed_plugins as $plugin) {
                $plugin = $plugin['TextDomain'];
                $url = self::RSS_SECURITY_API . 'plugin/' . $plugin . '.json';
                $data = $this->download($url);
                if ($data !== null)
                    $vulnerabilities[] = $data;
            }

            $vulnerabilities = $this->filter_active_components($vulnerabilities, $installed_plugins);
            $this->store_file($vulnerabilities);
        }

        /**
         * Downloads bases on given url
         *
         * @param string $url
         * @return mixed|null
         */
        private function download(string $url)
        {
            //now we check if the file remotely exists and then log an error if it does not.
            $headers = get_headers($url);
            if (strpos($headers[0], '200')) {
                //file exists, download it
                $json = file_get_contents($url);
                return json_decode($json);
            }
            error_log('Could not download file from ' . $url);
            return null;
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
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;

            $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');

            //we check if the directory exists, if not, we create it
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            //we delete the old file if it exists
            if (file_exists($file)) {
                wp_delete_file($file);
            }

            \library\FileStorage::StoreFile($file, $data);
        }

        public function get_file_stored_info($isCore = false)
        {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');
            if (!file_exists($file)) {
                return false;
            }
            return \library\FileStorage::GetDate($file);
            //now we return the unix timestamp as a normal date
            return date('d / m / Y @ H:i', $date);
        }

        /**
         * Loads the info from the files
         *
         * @return mixed|null
         */
        private function get_components()
        {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/components.json';
            if (!file_exists($file)) {
                return false;
            }
            return \library\FileStorage::GetFile($file);
        }

        /* Private functions | End of files and storage */

        /* Private functions | Filtering and walks */

        /**
         * Filters the components based on the active plugins
         *
         * @param $components
         * @param array $active_plugins
         * @return array
         */
        private function filter_active_components($components, array $active_plugins): array
        {
            $active_components = [];
            foreach ($components as $component) foreach ($active_plugins as $active_plugin) if ($component->slug === $active_plugin['TextDomain']) {
                //if the vulnerabilities are empty, we skip this component
                if (count($component->vulnerabilities) === 0) {
                    //first we check if the component is closed.
                    if ($component->closed !== true) {
                        //nothing is closed, we skip this component
                        continue;
                    }
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
         * This function adds the vulnerability with the highest risk to the plugins page
         *
         * @param $vulnerabilities
         * @return string
         */
        private function get_highest_vulnerability($vulnerabilities): string
        {
            //we loop through the vulnerabilities and get the highest risk level
            $highest_risk_level = 0;
            foreach ($vulnerabilities as $vulnerability) {
                if ($vulnerability->rss_severity === null) {
                    continue;
                }

                if (!isset($this->risk_levels[$vulnerability->rss_severity])) {
                    continue;
                }
                if ($this->risk_levels[$vulnerability->rss_severity] > $highest_risk_level) {
                    $highest_risk_level = $this->risk_levels[$vulnerability->rss_severity];
                }
            }
            //we now loop through the risk levels and return the highest one
            foreach ($this->risk_levels as $key => $value) {
                if ($value === $highest_risk_level) {
                    return $key;
                }
            }
            return '';
        }

        /**
         * This combines the vulnerabilities with the installed plugins
         */
        public function cache_installed_plugins(): void
        {
            //first we get all installed plugins
            $installed_plugins = get_plugins();
            //now we get the components from the file
            $components = $this->get_components();


            //We loop through plugins and check if they are in the components array
            foreach ($installed_plugins as $key => $plugin) {
                $plugin['vulnerable'] = false;
                $update = get_site_transient('update_plugins');
                if (isset($update->response[$key])) {
                    $plugin['update_available'] = true;
                } else {
                    $plugin['update_available'] = false;
                }
                //if there are no components, we return
                if (!empty($components)) {
                    foreach ($components as $component) {
                        if ($plugin['TextDomain'] === $component->slug) {
                            if (!empty($component->vulnerabilities)) {
                                $plugin['vulnerable'] = true;
                                $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
                                $plugin['closed'] = $component->closed;
                                $plugin['quarantine'] = $component->quarantine;
                                $plugin['force_update'] = $component->force_update;
                                $plugin['file'] = $key;
                            }
                        }
                    }
                }
                //we walk through the components array

                $this->workable_plugins[$key] = $plugin;
            }

        }

        public function add_vulnerability_warning_theme()
        {
            $theme = wp_get_theme();
            $components = $this->get_components();
            if (empty($components)) {
                return;
            }
            die($theme->get('TextDomain'));
            //for testing purposes we add a warning to all themes
            $this->add_vulnerability_warning('high', false, false, false);
//
//            foreach ($components as $component) {
//                if ($theme->get('TextDomain') === $component->slug) {
//                    if (!empty($component->vulnerabilities)) {
//                        $risk_level = $this->get_highest_vulnerability($component->vulnerabilities);
//                        $this->add_vulnerability_warning($risk_level, $component->closed, $component->quarantine, $component->force_update);
//                    }
//                }
//            }

        }


        /* Private functions | End of Filtering and walks */

        /**
         * This function shows the feedback in the plugin
         *
         * @return void
         */
        private function enable_feedback_in_plugin()
        {
            //we add some styling to this page
            add_action('admin_enqueue_scripts', array($this, 'add_vulnerability_styles'));
            //we add an extra column to the plugins page
            add_filter('manage_plugins_columns', array($this, 'add_vulnerability_column'));
            //now we add the field to the plugins page
            add_action('manage_plugins_custom_column', array($this, 'add_vulnerability_field'), 10, 3);
        }

        /**
         * checks if the plugin is vulnerable
         *
         * @param $plugin_file
         * @return mixed
         */
        private function check_vulnerability($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['vulnerable'];
        }

        /**
         * checks if the plugin's severity closed
         *
         * @param $plugin_file
         * @return mixed
         */
        private function check_severity($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['risk_level'];
        }


        /**
         * This function adds a notice for the dashboard
         *
         * @param $plugin
         */
        protected function add_notice($plugin): void
        {
            $riskSetting = rsssl_get_option('vulnerability_notification_dashboard');
            if (!$riskSetting) {
                $risk = 'low';
            } else {
                if ($plugin['risk_level'] === '') {
                    $risk = 'medium';
                } else {
                    $risk = $this->risk_naming[$plugin['risk_level']];
                }

            }

            //we then build the notice
            $this->notices[$plugin['TextDomain'] . '-' . $plugin['Version']] = array(
                'callback' => 'rsssl_vulnerabilities_enabled',
                'score' => 1,
                'output' => array(
                    'true' => array(
                        'msg' => '<span class="rsssl-badge rsp-' . $risk . '">' . __($risk, "really-simple-ssl") .
                            '</span><span class="rsssl-badge rsp-dark">' . $plugin['Name'] . '</span>' . __("has vulnerabilities.", "really-simple-ssl"),
                        'icon' => 'open',
                        'url' => 'https://really-simple-ssl.com/vulnerabilities/here_someUniqueKeyForPost', //TODO: add link to vulnerability page
                        'dismissible' => true,
                    ),
                ),
            );
        }


        /**
         * This functions adds a notice for the admin page
         */
        protected function add_admin_notice($plugin): string
        {
            //first we get the setting from options
            $riskSetting = rsssl_get_option('vulnerability_notification_sitewide');
            if (!$riskSetting) {
                $risk = 'high';
            } else {
                if ($plugin['risk_level'] === '') {
                    $risk = 'low';
                } else {
                    $risk = $this->risk_naming[$plugin['risk_level']];
                }

            }
            //we then build the notice
            return '<div data-dismissible="disable-done-notice-forever" class="notice notice-error is-dismissible"><p>' . '<strong>' . $plugin['Name'] . '</strong> ' . __("has vulnerabilities.", "really-simple-ssl") . '</p></div>';
        }

        public function enable_feedback_in_theme()
        {
            //if we are on the themes.php site we do this
            if (strpos($_SERVER['REQUEST_URI'], 'themes.php') !== false) {
                //we add the action to the theme row
                add_action('after_theme_row_', array($this, 'my_custom_theme_row_warning'), 10, 2);
            }
        }

        public function add_vulnerability_theme_column($columns)
        {
            $columns['vulnerability'] = __('Vulnerability', 'really-simple-ssl');
            return $columns;
        }

        public function my_custom_theme_row_warning()
        {
            // Replace "your_theme_slug" with your theme's slug
            echo '<div class="theme-warning"><span class="warning-icon"></span> <p>Here is your custom warning message for this theme.</p></div>';
        }

        public function getAllUpdatesCount(): int
        {
            $updates = get_plugin_updates();
            $updates = array_merge($updates, get_theme_updates());
            $updates = array_merge($updates, get_core_updates());
            return count($updates);
        }
    }

    //we initialize the class
    add_action('admin_init', array(rsssl_vulnerabilities::class, 'init'));
}

//we now check add notifications onboarding and vulnerability TODO: check if this is the best place for this please convey with Mark, Rogier.
add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_startup_notices'));
add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_plugin_notices'));

if (!function_exists('rsssl_vulnerabilities_enabled')) {
    /**
     * This function checks if the vulnerability scanner is enabled is being used as callback for the notices
     *
     * @return bool
     */
    function rsssl_vulnerabilities_enabled(): bool
    {
        return rsssl_get_option('enable_vulnerability_scanner');
    }
}


//registering an new Rest Api Route
add_action('rest_api_init', function () {
    //the get route
    register_rest_route('reallysimplessl/v1', '/vulnerabilities/', array(
        'methods' => 'GET',
        'callback' => array(rsssl_vulnerabilities::class, 'get_stats'),
    ));

    //the post route
    register_rest_route('reallysimplessl/v1', '/vulnerabilities/', array(
        'methods' => 'POST',
        'callback' => array(rsssl_vulnerabilities::class, 'post_vulnerabilities'),
    ));

});

/**
 * function die and dump
 *
 * @param $data
 */
function dd(...$data)
{
    //if only one variable is passed, we do not need to use the array
    if (count($data) === 1) {
        $data = $data[0];
    }
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}