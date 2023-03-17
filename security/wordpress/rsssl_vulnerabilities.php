<?php /** @noinspection PhpComposerExtensionStubsInspection */

use library\FileStorage;

defined('ABSPATH') or die();

//including the file storage class
require_once(rsssl_path . 'library/FileStorage.php');
require_once(rsssl_path . 'library/VulnerabilityDataHandler.php');

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

        public $update_count = 0;

        private $admin_notices = [];
        private $risk_naming = [
            'l' => 'Low-Risk',
            'm' => 'Medium-Risk',
            'h' => 'High-Risk',
            'c' => 'Critical',
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

                //we check if the files are up to date. if we make them up to date.
                $self->check_files();


                //first we need to make sure we update the files every day. So we add a daily cron.
                add_filter('rsssl_daily_cron', array($self, 'daily_cron'));


                //we cache the plugins in the class. Since we need quite some info from the plugins.
                $self->cache_installed_plugins();


                //we check the rsssl options if the enable_feedback_in_plugin is set to true
                if (rsssl_get_option('enable_feedback_in_plugin')) {
                    // we enable the feedback in the plugin
                    $self->enable_feedback_in_plugin();
                    $self->enable_feedback_in_theme();
                }
                //we add the notices to the notices array.
                $self->get_vulnerabilities();

                //if we are not in the SSL admin page, we add the admin notices.
                add_action('current_screen', array($self, 'show_admin_notices'));

                //if a theme is installed, we force the components to be updated.
                //we check if upgrader_process_complete is called, so we can reload the files.
                add_action('upgrader_process_complete', array($self, 'reload_files_on_update'), 10, 2);
                //After activation, we need to reload the files.
                add_action('activate_plugin', array($self, 'reload_files_on_update'), 10, 2);

                //same goes for themes.
                add_action('after_setup_theme', array($self, 'reload_files_on_update'), 10, 2);

                add_action('current_screen', array($self, 'show_inline_code'));


            }
        }

        /**
         * Callback to display admin notices.
         * TODO: mimplement logic suggested by rogier
         * @return void
         */
        public function show_admin_notices()
        {
            $screen = get_current_screen();

            if ($screen->id !== 'settings_page_really-simple-security') {
                foreach ($this->admin_notices as $notice) {
                    add_action('admin_notices', function () use ($notice) {
                        echo $notice;
                    });
                }
            }
        }


        /**
         * Generate plugin files for testing purposes.
         *
         * @return array
         */
        public static function testGenerator(): array
        {
            $self = new self();
            //Step one we add the test messages

            //step two we remove the messages after a certain time.


            return [
                'success' => true,
                'message' => __('A set of test plugins were created.', "really-simple-ssl")
            ];
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
                    $warnAt = rsssl_get_option('vulnerability_notification_dashboard') ?? false;

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
                'lastChecked' => date('d / m / Y @ H:i', $self->get_file_stored_info()),
                'vulEnabled' => $vulEnabled,
            ];

            return [
                "request_success" => true,
                'data' => $stats
            ];
        }

        /**
         * Merges our feature notices with the notices array.
         *
         * @param $notices
         *
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
            $url = trailingslashit(rsssl_url) . "assets/css/{$rtl}plugin$min.css";
            $path = trailingslashit(rsssl_path) . "assets/css/{$rtl}plugin$min.css";
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

        /* End of plug-in page add-on */


        /* Public and private functions | Files and storage */

        /**
         * Checks the files on age and downloads if needed.
         *
         * @return void
         */
        public function check_files()
        {

            //we download the manifest file if it doesn't exist or is older than 24 hours
            if (!$this->validate_local_file(true, true)) {
                if ($this->get_file_stored_info(true, true) > time() - 86400) {
                    return;
                }
                $this->download_manifest();
            };
            //We check the core vulnerabilities and validate age and existence
            if (!$this->validate_local_file(true)) {
                //if the file is younger than 24 hours, we don't download it again.
                if ($this->get_file_stored_info(true) > time() - 86400) {
                    return;
                }
               // $this->download_core_vulnerabilities();
            }

            //We check the plugin vulnerabilities and validate age and existence
            if (!$this->validate_local_file()) {
                if ($this->get_file_stored_info() > time() - 86400) {
                    return;
                }
                $this->download_plugin_vulnerabilities();
            }
            $this->cache_installed_plugins();
        }

        public function reload_files_on_update()
        {
            //if the file is not older than 10 minutes, we don't download it again.
            if ($this->get_file_stored_info(false, false) > time() - 600) {
                return;
            }
            $this->download_plugin_vulnerabilities();
            $this->cache_installed_plugins();
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
            $wp_version = '6.0.1'; //TODO: remove this line before release
            $url = self::RSS_SECURITY_API . 'core/wp-core_' . $wp_version . '.json';
            $data = $this->download($url);

            //we convert the data to an array
            $data = json_decode(json_encode($data), true);

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
            wp_enqueue_script('my-script', plugins_url('../../scripts.js', __FILE__), array(), '1.0.0', true);
            $inline_script = "
                jQuery(document).ready(function($) {
                    let style = document.createElement('style');
                    let vulnerable_components = [" . implode(',', array_map(function ($component) {
                    //we return slug and risk
                    return "{slug: '" . $component->slug . "', risk: '" . $this->get_highest_vulnerability($component->vulnerabilities) . "'}";
                }, $components)) . "];
                    console.log(vulnerable_components);
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
                            let text = '" . __('Security: <-level->', 'really-simple-ssl') . "';
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
            wp_add_inline_script('my-script', $inline_script);
        }

        public function show_theme_closed()
        {
            $screen = get_current_screen();
            $theme = wp_get_theme();
            if ($screen->id !== 'settings_page_really-simple-security') {
                add_action('admin_notices', function () use ($theme) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo sprintf(__('The theme %s is closed for security issues. Please update the theme as soon as possible.', 'rss-security'), $theme->get('Name')); ?></p>
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
                //if there are no components, we return
                if (!empty($components)) {
                    foreach ($components as $component) {
                        if ($plugin['TextDomain'] === $component->slug) {
                            if (!empty($component->vulnerabilities)) {
                                $plugin['vulnerable'] = true;
                                $plugin['risk_level'] = $this->get_highest_vulnerability($component->vulnerabilities);
                                $plugin['file'] = $key;
                            }
                        }
                    }
                }
                //we walk through the components array

                $this->workable_plugins[$key] = $plugin;
            }
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
                        'url' => 'https://really-simple-ssl.com/vulnerabilities/here_someUniqueKeyForPost',
                        //TODO: add link to vulnerability page
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

        private function filter_vulnerabilities($vulnerabilities, $Version): array
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
    }

    //we initialize the class
    add_action('admin_init', array(rsssl_vulnerabilities::class, 'init'));
}

#########################################################################################
# Functions for the vulnerability scanner   									        #
# These functions are used in the vulnerability scanner like the notices and the api's  #
#########################################################################################


//we now check add notifications onboarding and vulnerability TODO: check if this is the best place for this please convey with Mark, Rogier.
//add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_startup_notices'));
//add_filter('rsssl_notices', array(rsssl_vulnerabilities::class, 'add_plugin_notices'));

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

    //the post route
    register_rest_route('reallysimplessl/v1', '/vulnerabilities/', array(
        'methods' => 'POST',
        'callback' => array(rsssl_vulnerabilities::class, 'post_vulnerabilities'),
    ));

});

/* End of Routing and API's */


/* Helper functions */

/**
 * function die and dump
 * TODO: DELETE THIS FUNCTION AND USE THE ONE FROM THE CORE
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