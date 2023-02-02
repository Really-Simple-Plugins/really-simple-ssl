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
            add_action('admin_init', array($this, 'check_db_values'));
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

            //enables wp cron for incremental updates
            $this->enable_wp_cron();
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
         * Checks if the database table exists, if not, create it. If it does, check if the columns are still there.
         *
         * TODO: if only on method is called in this function rework this.
         * @return void
         */
        public function check_db_values()
        {
            $this->check_for_db_values();
        }

        /**
         * Checks for vulnerabilities in the core, plugins and themes.
         *
         * @return void
         */
        public function download_vulnerabilities()
        {
            //TODO: check if we download full or incremental based on last download
            //downloads wp core only based on current installed version
            $this->check_for_core_vulnerabilities();
            //downloads plugins and themes
            $this->check_for_plugin_vulnerabilities();
        }

        private function check_for_core_vulnerabilities()
        {
            $core_vulnerabilities = $this->get_core_vulnerabilities();
            $this->save_core_vulnerabilities($core_vulnerabilities);
        }

        private function check_for_db_values()
        {
            //first we check if the table vulnerability exists
            global $wpdb;
            $table_name = $wpdb->prefix . "rsssl_vulnerabilities";
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                //table does not exist, create it
                $this->create_table();
            } else {
                //the table exists, check if the columns are still there
                $this->check_columns($table_name);
            }


        }

        private function create_table()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "rsssl_vulnerabilities";
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                component varchar(255) NOT NULL,
                version varchar(255) NOT NULL,
                updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                severity_critical int(5) DEFAULT 0 NOT NULL,
                severity_high int(5) DEFAULT 0 NOT NULL,
                severity_medium int(5) DEFAULT 0 NOT NULL,
                severity_low int(5) DEFAULT 0 NOT NULL,
                severity_unknown int(5) DEFAULT 0 NOT NULL,
                last_checked datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        /**
         * Downloads the vulnerabilities for the current core version.
         *
         * TODO: check if we download full or incremental based on last download
         * @return mixed|null
         */
        private function get_core_vulnerabilities()
        {
            global $wp_version;
            //first we download the correct json from 'api.really-simple-security.com'
//            $url = 'https://api.really-simple-security.com/downloads/wp-core_' . $wp_version . '.json';
            $url = 'https://api.really-simple-security.com/storage/downloads/wp-core_6.0.1.json';
            return $this->download($url);

        }
        private function check_for_plugin_vulnerabilities()
        {
            //we get the active plugins
            $active_plugins = $this->get_active_plugins();

            //now we download the component json file and loop through the active plugins
            $components = $this->get_plugin_vulnerabilities();

            foreach ($active_plugins as $plugin) {
                //we get the slug of the plugin
                $slug = $plugin['TextDomain'];
                //we get the version of the plugin
                $version = $plugin['Version'];
                //we get the vulnerabilities for the plugin
                $vulnerabilities = $this->get_plugin_vulnerabilities($slug);
                //we check if there are any vulnerabilities for the plugin
                if ($vulnerabilities) {
                    //we check if the plugin is vulnerable
                    if ($this->is_plugin_vulnerable($vulnerabilities, $version)) {
                        //we log the error
                        $this->log_error('Plugin ' . $slug . ' is vulnerable');
                    }
                }
            }
        }
        private function get_plugin_vulnerabilities()
        {
            $url = 'https://wpvulndb.com/api/v3/plugins';
            return $this->download($url);

        }
        private function download($url)
        {
            //now we check if the file remotely exists and then log an error if it does not.
            $headers = get_headers($url);
            if (strpos($headers[0], '200')) {
                //file exists, download it
                $json = file_get_contents($url);
                return json_decode($json);
            } else {
                //file does not exist, log an error
                $this->log_error('Could not download vulnerabilities');

                //it could be that there are no vulnerabilities for this version, so we do nothing? TODO:Ask Team what to do with this.

                return null;
            }
        }



        /**
         * Saves the vulnerabilities for the current core version.
         * TODO: build a check if the version is already in the database, if so, update it.
         * @param $vulnerabilities
         * @return void
         */
        private function save_core_vulnerabilities($vulnerability)
        {
            global $wpdb;
            global $wp_version;
            $table_name = $wpdb->prefix . "rsssl_vulnerabilities";
            $wpdb->query("TRUNCATE TABLE " . $table_name); //TODO: check if this is the best way to do this (performance wise)
            $wpdb->insert(
                $table_name,
                array(
                    'component' => $vulnerability->name, //this can be the version of the wp core or slug for plugin or theme
                    'version' => $wp_version, //since we know which core version is running we store it here.
                    'updated' => $vulnerability->update, //date is fetched from the json file
                    'severity_critical' => $vulnerability->severity_critical, //severity is fetched from the json file
                    'severity_high' => $vulnerability->severity_high, //severity is fetched from the json file
                    'severity_medium' => $vulnerability->severity_medium, //severity is fetched from the json file
                    'severity_low' => $vulnerability->severity_low, //severity is fetched from the json file
                    'severity_unknown' => $vulnerability->severity_unknown, //severity is fetched from the json file
                    'last_checked' => current_time('mysql'),
                )
            );

        }

        /**
         * Checks if the columns are still there, if not, add them.
         *
         * @param string $table_name
         * @return void
         */
        private function check_columns(string $table_name): void
        {
            global $wpdb;

            //if the table exists we check if the columns are still there
            $columns = $wpdb->get_col("DESC " . $table_name, 0);
            if (!in_array("component", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD component varchar(255) NOT NULL");
            }
            if (!in_array("version", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD version varchar(255) NOT NULL");
            }
            if (!in_array("updated", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
            }
            if (!in_array("severity_critical", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD severity_critical int(5) DEFAULT 0 NOT NULL");
            }
            if (!in_array("severity_high", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD severity_high int(5) DEFAULT 0 NOT NULL");
            }
            if (!in_array("severity_medium", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD severity_medium int(5) DEFAULT 0 NOT NULL");
            }
            if (!in_array("severity_low", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD severity_low int(5) DEFAULT 0 NOT NULL");
            }
            if (!in_array("severity_unknown", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD severity_unknown int(5) DEFAULT 0 NOT NULL");
            }
            if (!in_array("last_checked", $columns)) {
                $wpdb->query("ALTER TABLE " . $table_name . " ADD last_checked datetime DEFAULT '0000-00-00 00:00:00' NOT NULL");
            }
        }

        private function log_error(string $string)
        {
            error_log($string);
        }

        private function get_active_plugins()
        {
            $active_plugins = get_option('active_plugins');
            $plugins = [];
            foreach ($active_plugins as $plugin) {
                //we return only the slug and version of the plugin
                $found = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);


                $plugins[] = ['slug' => $found['TextDomain'], 'version' => $found['Version']];
            }
            return $plugins;
        }

        private function enable_wp_cron()
        {
            if (!defined('DISABLE_WP_CRON') || DISABLE_WP_CRON) {
                define('DISABLE_WP_CRON', false);
            }
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
