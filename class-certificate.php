<?php

defined('ABSPATH') or die("you do not have access to this page!");

if ( ! class_exists( 'rsssl_certificate' ) ) {
    class rsssl_certificate
    {
        private static $_this;

        function __construct()
        {
            if (isset(self::$_this))
                wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

            self::$_this = $this;

            add_action('rsssl_configuration_page', array($this, 'show_www_non_www_status'));

            add_action('admin_init', array($this, 'is_valid'));



        }

        static function this()
        {
            return self::$_this;
        }

        public function is_valid()
        {


            //$domain = site_url();
            $domain = "http://cnet.com";

            if (!function_exists('stream_context_get_params')) {
                //load test page
            } else {

                //get certinfo
                $certinfo = $this->get_certinfo($domain);

                if (!$certinfo) return false;

                //Check if domain is valid
                $domain_valid = $this->is_domain_valid($certinfo, $domain);
                //Check if date is valid
                $date_valid = $this->is_date_valid($certinfo);
                //Domain and date valid? Return true
                if ($domain_valid && $date_valid) return true;

                //check op trailingslashit enzo



                $is_wildcard = $this->is_wildcard($certinfo);

                if ($is_wildcard && is_multisite()) {

                    $this->admin_notice_wildcard;

                    }
            }

            return false;
        }


        /*
         * Check common name(s) and alternative name(s) on certificate
         */

        public function is_domain_valid($certinfo, $domain)
        {
            error_log(print_r($certinfo, true));

            //Get both the common name(s) and the alternative names from the certificate
            $certificate_common_names = isset($certinfo['subject']['CN']) ? $certinfo['subject']['CN'] : false;
            $certificate_alternative_names = isset($certinfo['extensions']['subjectAltName']) ? $certinfo['extensions']['subjectAltName'] : false;

            //Check if the domain is found in either the certificate common name(s) (CN) or alternative name(s) (AN)
            $pos_cn = strpos($domain, $certificate_common_names);
            $pos_an = strpos($domain, $certificate_alternative_names);

            //If the domain is found, return true
            if (($pos_cn !== false) || ($pos_an !== false)) return true;

        }

        public function is_date_valid($certinfo)
        {

            //Get the start date and end date from the certificate
            $start_date = isset($certinfo['validFrom_time_t']) ? $certinfo['validFrom_time_t'] : false;
            $end_date = isset($certinfo['validTo_time_t']) ? $certinfo['validTo_time_t'] : false;

            //Determine current date
            $current_date = time();

            //Check if the current date is between the start date and end date
            if ($current_date > $start_date && ($current_date < $end_date)) return true;

        }


        /*
         * When multisite is activated with subdomains, and the cert is not wildcard, show a warning.
         *
         *
         * */


        public function admin_notice_wildcard()
        {

            if (!really_simple_ssl::ssl_enabled && !rsssl_multisite::is_multisite_subfolder_install() && !$this->is_wildcard()) {
                //show notice
            }

        }

        /*
         *
         *
         * */

        public function show_www_non_www_status()
        {
            ?>

            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php
        }


        /*
         *     multisite: check for subdomains?
         *
         * */

        public function is_wildcard($certinfo)
        {
            //Get the certificate common name
            $certificate_common_name = isset($certinfo['subject']['CN']) ? $certinfo['subject']['CN'] : false;

            //A wildcard certificate is indicated by *, using this as our wildcard indicator
            $wildcard_indicator = "*";

            //Check if the common name(s) contain an *
            $pos = strpos($certificate_common_name, $wildcard_indicator);

            //If so, return true
            if ($pos !== false) return true;

        }


        public function get_certinfo($domain)
        {

            //check if the certificate is still valid, and send an email to the administrator if this is not the case.
            $url = $domain;
            $original_parse = parse_url($url, PHP_URL_HOST);

            if ($original_parse) {

                $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
                if ($get) {
                    set_error_handler('rsssl_pro_custom_error_handling');
                    $read = stream_socket_client("ssl://" . $original_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
                    restore_error_handler();

                    if ($errno == 0 && $read) {

                        $cert = stream_context_get_params($read);
                        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
                    }
                }
            }
            return $certinfo;
        }

    //class closure
    }
}