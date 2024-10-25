<?php
namespace RSSSL\Pro\Security\WordPress;

class Rsssl_Simple_404_Interceptor {
    private $attempts = 10; // Default attempts threshold
    private $time_span = 5; // Time span in seconds (5 seconds)
    private $option_name = 'rsssl_404_cache';
    private $notice_option = 'rsssl_404_notice_shown';

    public function __construct() {

        add_filter( 'rsssl_notices', array($this, 'show_help_notices') );
	    if ( defined( 'rsssl_pro' ) ) {
		    return;
	    }
        add_action( 'template_redirect', array($this, 'detect_404') );
    }

    /**
     * Detect and handle 404 errors.
     */
    public function detect_404(): void {
        if (is_404()) {
            if ( get_option( $this->notice_option ) ) {
                return;
            }
            $ip_address = $this->get_ip_address();
            $current_time = time();

            // Prevent the option from becoming too large
            $cache = get_option($this->option_name, []);

            if (!isset($cache[$ip_address])) {
                $cache[$ip_address] = [];
            }

            $cache[$ip_address][] = $current_time;
            $cache[$ip_address] = $this->clean_up_old_entries($cache[$ip_address]);

            if (count($cache[$ip_address]) > $this->attempts && !get_option($this->notice_option)) {
                update_option($this->notice_option, true, false);

                return;
            }

            update_option($this->option_name, $cache, false);
        }
    }


    /**
     * Cleans up old entries based on the given timestamps.
     *
     * This method filters the given timestamps array and only keeps the entries where the difference between the current time
     * and the timestamp is less than the specified time span.
     *
     * @param array $timestamps An array of timestamps.
     *
     * @return array The cleaned up timestamps array.
     */
    private function clean_up_old_entries($timestamps): array {
        $current_time = time();
        return array_filter($timestamps, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < $this->time_span;
        });
    }

    /**
     * Retrieves the IP address of the client.
     *
     * This method checks for the IP address in the following order:
     * 1. HTTP_CLIENT_IP: Represents the IP address of the client if the client is a shared internet device.
     * 2. HTTP_X_FORWARDED_FOR: Represents the IP address of the client if the client is accessing the server through a proxy server.
     * 3. REMOTE_ADDR: Represents the IP address of the client if the client is accessing the server directly.
     *
     * @return string The IP address of the client.
     */
    private function get_ip_address(): string {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'UNKNOWN';
    }

    /**
     * Add a help notice for 404 detection warning.
     *
     * @param array $notices The existing notices array.
     *
     * @return array Updated notices array with 404 detection warning notice.
     */
    public function show_help_notices(array $notices): array {
        if (get_option($this->notice_option)) {
            $message = __('We detected suspected bots triggering large numbers of 404 errors on your site.', 'really-simple-ssl');
            $notice = [
                'callback' => '_true_',
                'score' => 1,
                'show_with_options' => ['enable_404_detection'],
                'output' => [
                    'true' => [
                        'msg' => $message,
                        'icon' => 'warning',
                        'type' => 'warning',
                        'dismissible' => true,
                        'admin_notice' => false,
                        'highlight_field_id' => 'enable_firewall',
                        'plusone' => true,
                        'url' => 'https://really-simple-ssl.com/suspected-bots-causing-404-errors/',
                    ]
                ]
            ];

            $notices['404_detection_warning'] = $notice;
        }
        return $notices;
    }
}

new Rsssl_Simple_404_Interceptor();