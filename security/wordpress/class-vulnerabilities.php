<?php defined('ABSPATH') or die();

/**
 * @package Really Simple SSL
 * @subpackage RSSSL_VULNERABILITIES
 * @since 3.0
 */
if (!class_exists("rsssl_vulnerabilities")) {

    /**
     * Class rsssl_vulnerabilities
     * Checks for vulnerabilities in the core, plugins and themes.
     *
     * @author Really Simple SSL
     * this class handles database, import of vulnerabilities, and checking for vulnerabilities.
     *
     */
    class rsssl_vulnerabilities
    {

        function __construct()
        {
            add_action('admin_init', array($this, 'download_vulnerabilities'));
        }

        /**
         * Initiate the class
         *
         * @return void
         */
        public function init()
        {
            //downloads vulnerabilities
            $this->download_vulnerabilities();
        }

        public static function instance()
        {
            static $instance = false;
            if (!$instance) {
                $instance = new rsssl_vulnerabilities();
            }
            return $instance;
        }

        /**
         * Checks for vulnerabilities in the core, plugins and themes.
         *
         * @return void
         */
        public function download_vulnerabilities()
        {
            //first we download the core vulnerabilities
            $this->download_core_vulnerabilities();

            //then we download the plugin vulnerabilities
            $this->download_plugin_vulnerabilities();
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
                if ($days < 3) {
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
            $url = 'https://api.really-simple-security.com/storage/downloads/core/wp-core_' . $wp_version . '.json';
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
            foreach($installed_plugins as $plugin) {
                $plugin = $plugin['TextDomain'];
                $url = 'https://api.really-simple-security.com/storage/downloads/plugin/'.$plugin.'.json';
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
        }

        private function log_error(string $string)
        {
            error_log($string);
        }

        /**
         * Fetches the active plugins and returns them as an array.
         *
         * @return array
         */
        private function get_active_plugins(): array
        {
            $active_plugins = get_option('active_plugins');
            $plugins = [];
            foreach ($active_plugins as $plugin) {
                //we return only the slug and version of the plugin
                $found = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                $plugins[] = $found;
            }
            return $plugins;
        }


        private function filter_active_components($components, array $active_plugins): array
        {
            $active_components = [];
            foreach ($components as $component) foreach ($active_plugins as $active_plugin) if ($component->slug === $active_plugin['TextDomain']) {
                $component->version = $active_plugin['Version'];
                //now we loop through the vulnerabilities of the component
                foreach ($component->vulnerabilities as $index => $vulnerability) {
                    //if the max_version is not set, we remove the vulnerability from the array
                    if (!isset($vulnerability->max_version) && !isset($vulnerability->min_version)) {
                        unset($component->vulnerabilities[$index]);
                    }
                    //if the max_version is set, we check if it is higher than the version of the plugin
                    if (!isset($vulnerability->max_version)) {
                        continue;
                    }//if the max_version is higher or equal to the version of the plugin, we remove the vulnerability from the array
                    if (version_compare($active_plugin['Version'], $vulnerability->max_version, '>=')) {
                        unset($component->vulnerabilities[$index]);
                    }
                }
                //now we get all values from the rss-severity property from the vulnerabilities

                $component = $this->count_severities($component);
                $active_components[] = $component;
            }
            return $active_components;
        }

        private function count_severities($component)
        {
            $severities = wp_list_pluck($component->vulnerabilities, 'rss_severity');
            // we add all the properties to the component
            $component->severity_critical = 0;
            $component->severity_high = 0;
            $component->severity_medium = 0;
            $component->severity_low = 0;
            $component->severity_unknown = 0;
            //we loop through the severities and add them to the component
            foreach ($severities as $severity) {
                switch ($severity) {
                    case 'c':
                        $component->severity_critical++;
                        break;
                    case 'h':
                        $component->severity_high++;
                        break;
                    case 'm':
                        $component->severity_medium++;
                        break;
                    case 'l':
                        $component->severity_low++;
                        break;
                    default:
                        $component->severity_unknown++;
                        break;
                }
            }
            //we now no longer need the vulnerabilities property, so we remove it
            unset($component->vulnerabilities);
            return $component;
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
    }
}

add_action('admin_init', 'rsssl_vulnerabilities');

function rsssl_vulnerabilities()
{
    global $rsssl_vulnerabilities;
    if (!isset($rsssl_vulnerabilities)) {
        $rsssl_vulnerabilities = new RSSSL_Vulnerabilities();
        $rsssl_vulnerabilities->init();
    }
    return $rsssl_vulnerabilities;
}
