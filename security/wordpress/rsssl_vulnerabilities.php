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
        const RSS_VULNERABILITIES_LOCATION = '/really-simple-ssl';
        const RSS_SECURITY_API = 'https://api.really-simple-security.com/storage/downloads/';
        public $workable_plugins = [];

        /**
         * interval every 12 hours
         */
        public $interval = 43200;

        public $update_count = 0;

        private $admin_notices = [];
        protected $risk_naming = [];

        /**
         * @var array|int[]
         */
        private $risk_levels = [
            'l' => 1,
            'm' => 2,
            'h' => 3,
            'c' => 4,
        ];
        public $trigger = false;

        protected $boot = true;


        public function __construct()
        {
            $this->risk_naming = [
                'l' => __('low-risk', 'really-simple-ssl'),
                'm' => __('medium-risk', 'really-simple-ssl'),
                'h' => __('high-risk', 'really-simple-ssl'),
                'c' => __('critical', 'really-simple-ssl'),
            ];
        }

        public static function riskNaming($risk = null)
        {
            $instance = self::instance();
            if (is_null($risk)) {
                return $instance->risk_naming;
            }
            return $instance->risk_naming[$risk];
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
                //if the pro version is active, we use the pro version.
                    //if the file exists, we include it.
                    if (file_exists(WP_PLUGIN_DIR . 'really-simple-ssl-pro/security/wordpress/rsssl_vulnerabilities_pro.php')) {
                        require_once(WP_PLUGIN_DIR . 'really-simple-ssl-pro/security/wordpress/rsssl_vulnerabilities_pro.php');
                        $instance = new rsssl_vulnerabilities_pro();
                    }
            }
            $instance->init();
            return $instance;
        }

        /* Public Section 1: Class Build-up initialization and instancing */
        /**
         * Initiate the class
         *
         * @return void
         */
        public function init()
        {
            //we check if the vulnerability scanner is enabled and then the fun happens.
            if (rsssl_get_option('enable_vulnerability_scanner')) {
                $this->check_files();
                $this->cache_installed_plugins();
                if ($this->boot) {
                    $this->run();
                }
            }
        }

        public function run()
        {
            //we check if the vulnerability scanner is enabled and then the fun happens.
            //first we need to make sure we update the files every day. So we add a daily cron.
            add_filter('rsssl_daily_cron', array($this, 'daily_cron'));

            //we check the rsssl options if the enable_feedback_in_plugin is set to true
            if (rsssl_get_option('enable_feedback_in_plugin')) {
                // we enable the feedback in the plugin
                $this->enable_feedback_in_plugin();
                $this->enable_feedback_in_theme();
            }

            //we check if upgrader_process_complete is called, so we can reload the files.
            add_action('upgrader_process_complete', array($this, 'reload_files_on_update'), 10, 2);
            //After activation, we need to reload the files.
            add_action('activate_plugin', array($this, 'reload_files_on_update'), 10, 2);

            //same goes for themes.
            add_action('after_setup_theme', array($this, 'reload_files_on_update'), 10, 2);

            add_action('current_screen', array($this, 'show_inline_code'));

            if($this->trigger) {
                $this->send_vulnerability_mail();
            }
        }

        /**
         * Function used for first run of the plugin.
         *
         * @return array
         */
        public static function firstRun(): array
        {
            $instance = self::instance();

            update_option('rsssl_vulnerabilities_first_run', true);
            rsssl_update_option('enable_vulnerability_scanner', '1');
            return $instance->assemble_first_run();
        }

        public function show_help_notices($notices)
        {
            $this->cache_installed_plugins();
            $risks = $this->count_risk_levels();
            foreach ($this->risk_levels as $key => $value) {
                if (!isset($risks[$key])) {
                    continue;
                }
                //this is shown bases on the config of vulnerability_notification_dashboard
                $siteWide = true;
                if (rsssl_get_option('vulnerability_notification_dashboard')) {
                    if ($value < $this->risk_levels[rsssl_get_option('vulnerability_notification_dashboard')]) {
                        //we skip this one.
                        continue;
                    }
                }
                if (rsssl_get_option('vulnerability_notification_dashboard')) {
                    if ($value < $this->risk_levels[rsssl_get_option('vulnerability_notification_dashboard')]) {
                        //we skip this one.
                        $siteWide = false;
                    }
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
                            'msg' => sprintf(__('You have %s %s %s. Please take appropriate action. For more information about these vulnerabilities, please read more <a href="/wp-admin/options-general.php?page=really-simple-security#settings/vulnerabilities">here</a>', 'really-simple-ssl'), $count, $this->risk_naming[$key],$count_label ),
                            'url' => 'https://really-simple-ssl.com/knowledge-base/vulnerability-scanner/',
                            'icon' => ($key === 'c') ? 'warning' : 'open',
                            'type' => 'warning',
                            'dismissible' => true,
                            'admin_notice' => $siteWide,
                            'plusone' => true,
                        ]
                    ]
                ];
                $notices['risk_level_' . $key] = $notice;

            }
            update_option('rsssl_admin_notices', $notices);

            return $notices;
        }


        /**
         * Generate plugin files for testing purposes.
         *
         * @return array
         */
        public static function testGenerator(): array
        {
            $self = new self();
            return $self->send_warning_email();
        }


        /* Public Section 2: DataGathering */

        /**
         * @param $data
         *
         * @return array
         */
        public static function get_stats($data): array
        {
            $self = new self();
            $vulEnabled = false;


            $vulEnabled = rsssl_get_option('enable_vulnerability_scanner');
            $firstRun = get_option('rsssl_vulnerabilities_first_run');

            $updates = 0;
            $vulnerabilities = [];
            if ($vulEnabled) {
                $self->cache_installed_plugins();
                //now we only get the data we need.
                $vulnerabilities = array_filter($self->workable_plugins, function ($plugin) {
                    if (isset($plugin['vulnerable']) && $plugin['vulnerable']) {
                        return $plugin;
                    }
                });


                //now we fetch all plugins that have an update available.
                foreach ($self->workable_plugins as $plugin) {
                    if (isset($plugin['update_available']) && $plugin['update_available']) {
                        $updates++;
                    }
                }
            }


            $stats = [
                'vulnerabilities' => count($vulnerabilities),
                'vulList' => $vulnerabilities,
                'updates' => $self->getAllUpdatesCount(),
                'lastChecked' => date('d / m / Y @ H:i', $self->get_file_stored_info(true)),
                'riskNaming'   => $self->risk_naming,
                'vulEnabled' => $vulEnabled,
                'firstRun' => $firstRun
            ];

            return [
                "request_success" => true,
                'data' => $stats
            ];
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
                        'dismissible' => false,
                        'highlight_field_id' => 'vulnerability_notification_dashboard',
                        'admin_notice' => true,
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
        public static function measures_data(): array
        {
            $measures = [];
            $measures[] = [
                'id' => 'low_risk',
                'risk' => self::riskNaming('l'),
                'value' => get_option('rsssl_low_risk'),
                'description' => sprintf(__('%s vulnerabilities', 'really-simple-ssl'), self::riskNaming('l')),
            ];
            $measures[] = [
                'id' => 'medium_risk',
                'risk' => self::riskNaming('m'),
                'value' => get_option('rsssl_medium_risk'),
                'description' => sprintf(__('%s vulnerabilities', 'really-simple-ssl'), self::riskNaming('m')),
            ];
            $measures[] = [
                'id' => 'high_risk',
                'risk' => self::riskNaming('h'),
                'value' => get_option('rsssl_high_risk'),
                'description' => sprintf(__('%s vulnerabilities', 'really-simple-ssl'), self::riskNaming('h')),
            ];
            $measures[] = [
                'id' => 'critical_risk',
                'risk' => self::riskNaming('c'),
                'value' => get_option('rsssl_critical_risk'),
                'description' => sprintf(__('%s vulnerabilities', 'really-simple-ssl'), self::riskNaming('c')),
            ];

            return [
                "request_success" => true,
                'data' => $measures
            ];
        }

        /**
         * Sets Data for Risk_vulnerabilities_data
         */
        public static function risk_vulnerabilities_data(array $response, string $action, $data): array
        {
            return [
                "request_success" => false,
                'data' => []
            ];
            $self = new self();
            if (!rsssl_user_can_manage()) {
                return $response;
            }

            if ($action === 'risk_vulnerabilities_data_save') {
                // Saving options for rsssl
                switch ($data['field']) {
                    case 'low_risk':
                        //storing the update value
                        rsssl_update_option('low_risk_measure', $data['value']);
                        break;
                    case 'medium_risk':
                        rsssl_update_option('medium_risk_measure', $data['value']);
                        break;
                    case 'high_risk':
                        rsssl_update_option('high_risk_measure', $data['value']);
                        break;
                    case 'critical_risk':
                        rsssl_update_option('critical_risk_measure', $data['value']);
                        break;
                }
            }

            return self::measures_data();
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
                            echo sprintf('<a class="btn-vulnerable critical" target="_blank" href="%s">%s</a>', 'https://really-simple-ssl.com/vulnerabilities/'.$this->getIdentifier($plugin_file), $this->risk_naming['c']);
                            break;
                        case 'h':
                            echo sprintf('<a class="btn-vulnerable high" target="_blank" href="%s">%s</a>', 'https://really-simple-ssl.com/vulnerabilities/'.$this->getIdentifier($plugin_file), $this->risk_naming['h']);
                            break;
                        case 'm':
                            echo sprintf('<a class="btn-vulnerable medium-risk" target="_blank" href="%s">%s</a>', 'https://really-simple-ssl.com/vulnerabilities/'.$this->getIdentifier($plugin_file), $this->risk_naming['m']);
                            break;
                        default:
                            echo sprintf('<a class="btn-vulnerable low" target="_blank" href="%s">%s</a>', 'https://really-simple-ssl.com/vulnerabilities/'.$this->getIdentifier($plugin_file), $this->risk_naming['l']);
                            break;
                    }
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
            $min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
            $rtl = is_rtl() ? 'rtl/' : '';
            $url = trailingslashit(rsssl_url) . "assets/css/{$rtl}plugin.min.css";
            $path = trailingslashit(rsssl_path) . "assets/css/{$rtl}plugin.min.css";
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
            return $this->workable_plugins[$plugin_file]['vulnerable'];
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
            return $this->workable_plugins[$plugin_file]['risk_level'];
        }

        private function getIdentifier($plugin_file)
        {
            return $this->workable_plugins[$plugin_file]['rss_identifier'];
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
            $trigger = false;
            //we download the manifest file if it doesn't exist or is older than 12 hours
            if ($this->validate_local_file(false, true)) {
                if (!$this->get_file_stored_info(false, true) > time() - $this->interval) {
                    $this->download_manifest();
                }
            } else {
                $this->download_manifest();
            }
            //We check the core vulnerabilities and validate age and existence
            if ($this->validate_local_file(true, false)) {

                //if the file is younger than 12 hours, we don't download it again.
                if (!$this->get_file_stored_info(true) > time() - $this->interval) {
                    $this->download_core_vulnerabilities();
                }

            } else {
                $this->download_core_vulnerabilities();
            }

            //We check the plugin vulnerabilities and validate age and existence
            if ($this->validate_local_file()) {
                if (!$this->get_file_stored_info() > time() - $this->interval) {
                    $this->download_plugin_vulnerabilities();
                }
            } else {
                $this->download_plugin_vulnerabilities();
            }
        }

        public function reload_files_on_update()
        {
            //if the file is not older than 10 minutes, we don't download it again.
            if ($this->get_file_stored_info(false, false) > time() - 600) {
                return;
            }
            $this->download_plugin_vulnerabilities();
            $this->download_core_vulnerabilities();
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
            if (!$manifest) {
                //if we don't check for the manifest, we check the other files.
                $isCore ? $file = 'core.json' : $file = 'components.json';
            } else {
                $file = 'manifest.json';
            }

            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $file = $upload_dir . self::RSS_VULNERABILITIES_LOCATION . '/' . $file;
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
         * @param bool $manifest
         *
         * @return void
         */
        private function store_file($data, bool $isCore = false, bool $manifest = false): void
        {
            //if the data is empty, we return null
            if (empty($data)) {
                return;
            }
            //we get the upload directory
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;

            if (!$manifest) {
                $file = $upload_dir . '/' . ($isCore ? 'core.json' : 'components.json');
            } else {
                $file = $upload_dir . '/manifest.json';
            }


            //we check if the directory exists, if not, we create it
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            //we delete the old file if it exists
            if (file_exists($file)) {
                wp_delete_file($file);
            }

            FileStorage::StoreFile($file, $data);
            $this->trigger = true;
        }

        public function get_file_stored_info($isCore = false, $manifest = false)
        {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
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
            global $wp_version;
            $url = self::RSS_SECURITY_API . 'core/WordPress.json';
            $data = $this->download($url);


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

            //we get all the installed plugins
            $installed_plugins = get_plugins();
            //first we get the manifest file
            $manifest = $this->getManifest();
            $vulnerabilities = [];
            foreach ($installed_plugins as $plugin) {
                $plugin = $plugin['TextDomain'];
                $url = self::RSS_SECURITY_API . 'plugin/' . $plugin . '.json';
                //if the plugin is not in the manifest, we skip it
                if (!in_array($plugin, (array)$manifest)) {
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
                $url = self::RSS_SECURITY_API . 'theme/' . $theme . '.json';

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
            $installed_themes = array_map(function ($theme) {
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
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/components.json';
            if (!file_exists($file)) {
                return false;
            }

            return FileStorage::GetFile($file);
        }

        /* End of plug-in files section */

        /* Section for the core files Note: No manifest is needed */
        private function get_core()
        {
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
            $file = $upload_dir . '/core.json';
            if (!file_exists($file)) {
                return false;
            }

            return FileStorage::GetFile($file);
        }

        /* Section for the theme files */

        public function enable_feedback_in_theme()
        {
            //Logic here for theme warning Create Callback and functions for these steps
            //we only display the warning for the theme page
            add_action('current_screen', [$this, 'show_theme_warning']);
        }

        public function show_theme_warning($hook)
        {
            $screen = get_current_screen();

            if ($screen->id !== 'themes') {
                return;
            }

            //we add warning scripts to themes
            add_action('admin_enqueue_scripts', [$this, 'enqueue_theme_warning_scripts']);

        }

        public function show_inline_code($hook)
        {
            if ($hook !== 'themes.php') {
                return;
            }
            //we add warning scripts to themes
            add_action('admin_enqueue_scripts', [$this, 'enqueue_theme_warning_scripts']);
        }

        public function enqueue_theme_warning_scripts($hook)
        {
            //we get all components with vulnerabilities
            $components = $this->get_components();
            wp_enqueue_script('rsssl_vulnerabilities', plugins_url('../../scripts.js', __FILE__), array(), '1.0.0', true);
            $inline_script = "
                jQuery(document).ready(function($) {
                    let style = document.createElement('style');
                    let vulnerable_components = [" . implode(',', array_map(function ($component) {
                    //we return slug and risk
                    return "{slug: '" . esc_attr($component->slug) . "', risk: '" . esc_attr($this->get_highest_vulnerability($component->vulnerabilities)) . "'}";
                }, $components)) . "];
                 
                    //we create the style for warning
                    style.innerHTML = '.rss-theme-notice-warning {background-color: #FFF6CE; border-left: 4px solid #ffb900; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); position:relative; z-index:50; margin-bottom: -48px; padding: 1px 12px;}';
                    //we create the style for danger
                    style.innerHTML += '.rss-theme-notice-danger {background-color: #FFCECE; border-left: 4px solid #dc3232; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); position:relative; z-index:50; margin-bottom: -48px; padding: 1px 12px;}';
                    //we create the style for closed
                    style.innerHTML += '.rss-theme-notice-closed {background-color: #fff; border-left: 4px solid #dc3232; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); margin: 0; padding: 1px 12px;}';
                    let levels = " . json_encode($this->risk_naming) . ";
       
                    //we add the style to the head       
                    document.head.appendChild(style);
                    //we loop through the components
                   
                    vulnerable_components.forEach(function(component) {
                        //we get the theme element
                        let theme_element = $(\".theme[data-slug='\"+component.slug+\"']\");
                        //if the theme exists
                        if (theme_element.length > 0) {
                            //we check the risk
                            let level = levels[component.risk];
                            let text = '" . esc_attr(__('Security: <-level->', 'really-simple-ssl')) . "';
                            text = text.replace('<-level->', level);
        
                            if (component.risk === 'h' || component.risk === 'c') {
                                
                                //we add the danger class
                                theme_element.prepend('<div class=\"rss-theme-notice-danger\"><p><span class=\"dashicons dashicons-no\"></span>  '+text+'</p></div>');
                            } else {
                                theme_element.prepend('<div class=\"rss-theme-notice-warning\"><p></p><span class=\"dashicons dashicons-info\"></span>  '+text+'</p></div>');
                            }
                            }
                        });
                });
            ";

            wp_add_inline_script('rsssl_vulnerabilities', $inline_script);
        }

        public function show_theme_closed()
        {
            $screen = get_current_screen();
            $theme = wp_get_theme();
            if ($screen->id !== 'settings_page_really-simple-security') {
                add_action('admin_notices', function () use ($theme) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo sprintf(__(esc_attr('The theme %s is closed for security issues. Please update the theme as soon as possible.'), 'rss-security'), $theme->get('Name')); ?></p>
                    </div>
                    <?php
                });
            }

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
                    // new rework for logic
                    if (isset($component->slug) && $component->slug === $active_plugin['TextDomain']) {
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

        /* Caching functions */

        /**
         * This combines the vulnerabilities with the installed plugins
         *
         * And loads it into a memory cache on page load
         *
         */
        public function cache_installed_plugins(): void
        {
            //first we get all installed plugins
            $installed_plugins = get_plugins();
            $installed_themes = wp_get_themes();

            //we flatten the array

            //we make the installed_themes look like the installed_plugins
            $installed_themes = array_map(function ($theme) {
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

            //we add a column type to all values in the array
            $installed_themes = array_map(function ($theme) {
                $theme['type'] = 'theme';

                return $theme;
            }, $installed_themes);

            //we add a column type to all values in the array
            $installed_plugins = array_map(function ($plugin) {
                $plugin['type'] = 'plugin';

                return $plugin;
            }, $installed_plugins);

            //we merge the two arrays
            $installed_plugins = array_merge($installed_plugins, $installed_themes);

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

                if($plugin['type'] === 'theme') {
                    // we check if the theme exists as a directory
                    if (!file_exists(get_theme_root() . '/' . $plugin['TextDomain']) ) {
                        $plugin['folder_exists'] = false;
                    } else {
                        $plugin['folder_exists'] = true;
                    }
                }

                if($plugin['type'] === 'plugin') {
                    //also we check if the folder exists for the plugin we added this check for later purposes
                    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin['TextDomain']) ) {
                        $plugin['folder_exists'] = false;
                    } else {
                        $plugin['folder_exists'] = true;
                    }
                }

                //if there are no components, we return
                if (!empty($components)) {
                    foreach ($components as $component) {
                        if ($plugin['TextDomain'] === $component->slug) {
                            if (!empty($component->vulnerabilities) && $plugin['folder_exists'] === true) {
                                $plugin['vulnerable'] = true;
                                $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
                                $plugin['rss_identifier'] = $this->getLinkedUUID($component->vulnerabilities, $plugin['risk_level']);
                                $plugin['risk_name'] = $this->risk_naming[$plugin['risk_level']];
                                $plugin['date'] = $this->getLinkedDate($component->vulnerabilities, $plugin['risk_level']);
                                $plugin['file'] = $key;
                            }
                        }
                    }
                }
                //we walk through the components array

                $this->workable_plugins[$key] = $plugin;
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
            if (!empty($core->vulnerabilities)) {
                $core_plugin['vulnerable'] = true;
                $core_plugin['risk_level'] = $this->get_highest_vulnerability($core->vulnerabilities);
                $core_plugin['rss_identifier'] = $this->getLinkedUUID($core->vulnerabilities, $core_plugin['risk_level']);
                $core_plugin['risk_name'] = $this->risk_naming[$core_plugin['risk_level']];
                $core_plugin['date'] = $this->getLinkedDate($core->vulnerabilities, $core_plugin['risk_level']);
                $core_plugin['file'] = 'wordpress';
            }
            //we add the core plugin to the workable_plugins array
            $this->workable_plugins['wordpress'] = $core_plugin;
        }


        /* Private functions | End of Filtering and walks */


        /* Private functions | Feedback, Styles and scripts */

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

        /* End of private functions | Feedback, Styles and scripts */


        /* Private section for API's */

        public function getAllUpdatesCount(): int
        {
            $updates = wp_get_update_data();
            //we only want core, plugins and themes
            $updates = array_slice($updates, 0, 3);

            return array_sum($updates);
        }


        /* End of private section for API's */


        /**
         * This function downloads the manifest file from the api server
         *
         * @return void
         */
        private function download_manifest()
        {

            $url = self::RSS_SECURITY_API . 'manifest.json';
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
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir'];
            $upload_dir = $upload_dir . self::RSS_VULNERABILITIES_LOCATION;
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
                //if fixed_in value is Not fixed we
                if ($vulnerability->fixed_in !== 'not fixed') {
                    if (version_compare($Version, $vulnerability->fixed_in, '<')) {
                        $filtered_vulnerabilities[] = $vulnerability;
                    }
                } else {
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

            }

            return $filtered_vulnerabilities;
        }

        /**
         * This function sends a test email.
         * It is used to check if the email is working.
         * It is also used to check if the notification is working.
         *
         * @return array
         */
        private function send_warning_email()
        {
            $mailer = new rsssl_mailer();
            $mailer->subject = __("Feature enabled", "really-simple-ssl");
            $mailer->message = __("This is a test email to see if notifications about notifications can be send through email.", "really-simple-ssl");
            $mailer->to = get_option('admin_email');

            return $mailer->send_mail();
        }

        function make_test_notifications()
        {
            return true;
        }

        private function store_session(string $string, bool $true, int $int)
        {
            //we store a session for three minutes to display the notification
            $_SESSION[$string] = $true;
            $_SESSION['expire_time'] = time() + $int;
        }

        private function check_session(string $string): bool
        {
            if (isset($_SESSION[$string]) && $_SESSION['expire_time'] >= time()) {
                return true;
            } else {
                return false;
            }
        }

        private function clear_cache()
        {
            rsssl_cache::this()->flush();
        }

        public function count_risk_levels()
        {
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

        private function getLinkedUUID($vulnerabilities, string $risk_level)
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
                    return date('d-m-Y', strtotime($vulnerability->published_date));
                }
            }
        }

        public function send_vulnerability_mail()
        {
            //first we check if the user wants to receive emails
            if (!rsssl_get_option('send_notifications_email')) {
                return;
            }

            if (!rsssl_get_option('vulnerability_notification_email_admin')) {
                return;
            }


            //now based on the risk level we send a different email
            $risk_levels = $this->count_risk_levels();
            $total = 0;

            $blocks = [];
            foreach ($risk_levels as $key => $value) {

                if (!($this->risk_levels[$key] < $this->risk_levels[rsssl_get_option('vulnerability_notification_email_admin')])) {
                    $blocks[] = $this->createBlock($key, $value);
                    $total = $total + $value;
                }

            }
            //date format is named month day year
            $date = date('F j, Y');
            $domain = get_site_url();
            $mailer = new rsssl_mailer();
            $mailer->subject = sprintf(__("Vulnerability Alert: %s", "really-simple-ssl"), $domain);
            $mailer->title = sprintf(__("%s: %s vulnerabilities found", "really-simple-ssl"), $date, $total);
            $message = sprintf(__("This is a vulnerability alert from Really Simple SSL for %s. We encourage to take appropriate action. To know more about handling vulnerabilities with Really Simple SSL, please 
			<a href='https://really-simple-plugins.com/instructions/about-vulnerabilities/'>read this article</a>.", "really-simple-ssl"), $domain);
            $mailer->message = $message;
            $mailer->to = get_option('admin_email');
            $mailer->warning_blocks = $blocks;
            if ($total > 0) {
                //if for some reason the total is 0, we don't send an email
                $mailer->send_mail(true);
            }
        }

        protected function createBlock($severity, $count): array
        {
            $vulnerability = _n('vulnerability', 'vulnerabilities', $count, 'really-simple-ssl');
            $risk = $this->risk_naming[$severity];
            $domain = get_site_url();
            $messagePrefix = '';
            $messageSuffix = sprintf(__('Get more information from the Really Simple SSL dashboard on %s about all vulnerabilities'), $domain);
            switch ($severity) {
                case 'c':
                    $messagePrefix = __("Critical vulnerabilities require immediate action.", "really-simple-ssl");
                    break;
                case 'h':
                    $messagePrefix = __("High vulnerabilities require immediate action.", "really-simple-ssl");
                    break;
                case 'm':
                    $messagePrefix = __("Medium vulnerabilities require your action.", "really-simple-ssl");
                    break;
                case 'l':
                    $messagePrefix = __("Low vulnerabilities require your action.", "really-simple-ssl");
                    break;
            }

            return [
                'title' => sprintf(__("You have %s %s %s", "really-simple-ssl"), $count, $risk, $vulnerability),
                'message' => $messagePrefix . ' ' . $messageSuffix,
                'url' => 'https://really-simple-ssl.com/vulnerabilities/',
            ];

        }

        private function assemble_first_run()
        {
            $this->check_files();
            $this->cache_installed_plugins();

            return [
                    'request_success' => true,
                'data' =>   $this->workable_plugins
            ];
        }

    }

    //we initialize the class
    add_action('admin_init', array(rsssl_vulnerabilities::class, 'instance'));
}

#########################################################################################
# Functions for the vulnerability scanner   									        #
# These functions are used in the vulnerability scanner like the notices and the api's  #
#########################################################################################
//we clear all the cache when the vulnerability scanner is enabled

add_filter('rsssl_notices', [new rsssl_vulnerabilities(), 'show_help_notices'], 10, 1);


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


/* Routing and API's */

//registering a new Rest Api Route
add_action('rest_api_init', function () {
    //the get route
    register_rest_route('reallysimplessl/v1', '/vulnerabilities/', array(
        'methods' => 'GET',
        'callback' => array(rsssl_vulnerabilities::class, 'get_stats'),
    ));

    register_rest_route('reallysimplessl/v1', '/measures/', array(
        'methods' => 'GET',
        'callback' => array(rsssl_vulnerabilities::class, 'measures_data'),
    ));
    #---------------------------------------------#

    register_rest_route('reallysimplessl/v1', 'measures/set', array(
        'methods' => 'POST',
        'callback' => 'store_measures',
    ));
});


if (!function_exists('rsssl_vulnerabilities_get_stats')) {
    /**
     * This function is used to get the stats of the vulnerability scanner
     *
     * @param $data
     * @return array
     */
    function store_measures($data): array
    {
        update_option('rsssl_'.$data['field'], $data['value']);

        return rsssl_vulnerabilities::measures_data();
    }
}


/* End of Routing and API's */

if (function_exists('make_test_notifications')) {
    function make_test_notifications()
    {
        $notices = get_option('rsssl_admin_notices');
        $notice = [
            'callback' => '_true_',
            'score' => 1,
            'show_with_options' => ['enable_vulnerability_scanner'],
            'output' => [
                'true' => [
                    'title' => __('Test notification', 'really-simple-ssl'),
                    'msg' => __("This is a 'Dashboard' notification test by Really Simple SSL. You can safely ignore this message. (x)", "really-simple-ssl"),
                    'link' => 'https://really-simple-ssl.com/knowledge-base/vulnerability-scanner/',
                    'icon' => 'warning',
                    'type' => 'warning',
                    'dismissible' => true,
                    'admin_notice' => true,
                ]
            ]
        ];
        $notices['test_vulnerability_notice'] = $notice;
        //we store the notice in the notices array
        update_option('rsssl_admin_notices', $notices);
    }
}