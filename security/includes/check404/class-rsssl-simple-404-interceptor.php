<?php
namespace RSSSL\Security\Includes\Check404;

class Rsssl_Simple_404_Interceptor {

    private $warning_threshold = 10; // Warn when more than this many 404s happen within the window
    private $window_seconds = 5;
    private $max_addresses = 20; // Hard cap on tracked addresses, so the option stays a few KB at most
    private $option_name = 'rsssl_404_cache';
    private $notice_option = 'rsssl_404_notice_shown';

	public function __construct() {
		// Load the 404 test class only if the firewall has been enabled
		if ( rsssl_get_option('enable_firewall') == '1' ) {
			add_action( 'admin_init', array( $this, 'maybe_load_class_404_test' ), 20, 4 );
		}

		add_filter( 'rsssl_notices', array( $this, 'show_help_notices' ) );

		if ( defined( 'rsssl_pro' ) ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'detect_404' ) );
	}
    /**
     * Records a 404 for the client address and sets the notice when the threshold is exceeded
     */
    public function detect_404(): void {
        if (!is_404()) {
            return;
        }

        if ( get_option( $this->notice_option ) ) {
            return;
        }

        $ip_address = $this->get_ip_address();
        if ($ip_address === '') {
            return;
        }

        $now = time();
        $cache = get_option($this->option_name, []);
        if (!is_array($cache)) {
            $cache = [];
        }

        $cache = $this->clean_stale_timestamps($cache, $now);

        $timestamps = $cache[$ip_address] ?? [];
        unset($cache[$ip_address]);

        $timestamps[] = $now;
        $cache[$ip_address] = $timestamps;
        $cache = array_slice($cache, -$this->max_addresses, null, true);

        if (count($timestamps) > $this->warning_threshold) {
            update_option($this->notice_option, true, false);
            delete_option($this->option_name);

            return;
        }

        update_option($this->option_name, $cache, false);
    }

    /**
     * Returns valid addresses with recent timestamps, capped at the warning
     * threshold per address.
     */
    private function clean_stale_timestamps(array $cache, int $now): array {
        $cleaned = [];
        foreach ($cache as $ip_address => $timestamps) {
            if (!is_string($ip_address) || filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
                continue;
            }
            if (!is_array($timestamps)) {
                continue;
            }

            $recent_timestamps = [];
            foreach ($timestamps as $timestamp) {
                if (is_int($timestamp) && ($now - $timestamp) < $this->window_seconds) {
                    $recent_timestamps[] = $timestamp;
                }
            }

            if (empty($recent_timestamps)) {
                continue;
            }

            $cleaned[$ip_address] = array_slice($recent_timestamps, -$this->warning_threshold);
        }

        return $cleaned;
    }

    /**
     * Retrieves the validated IP address of the client.
     *
     * Only REMOTE_ADDR is used. Forwarding headers such as Client-IP and X-Forwarded-For are chosen by the
     * client and would let it pick the cache key, so they are ignored here.
     *
     * @return string The IP address, or an empty string when it is missing or invalid.
     */
    private function get_ip_address(): string {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!is_string($ip_address) || filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
            return '';
        }

        return $ip_address;
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
            $message = __('We detected suspected bots triggering large numbers of 404 errors on your site', 'really-simple-ssl');
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

	/**
	 * @param $field
	 * @param $value
	 * @param $old_value
	 * @param $option_name
	 *
	 * @return void
	 */
	public function maybe_load_class_404_test() {
		if ( ! get_option( 'rsssl_homepage_contains_404_resources' ) ) {
			Rsssl_Test_404::get_instance();
		}
	}
}

new Rsssl_Simple_404_Interceptor();
