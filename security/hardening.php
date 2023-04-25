<?php
defined('ABSPATH') or die();
class rsssl_hardening {
	private static $_this;
	public $risk_naming;
	function __construct()
	{
		if (isset(self::$_this))
			wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));
		add_filter( 'rsssl_do_action', array($this, 'hardening_data'), 10, 3 );
		add_filter( 'rsssl_do_action', array($this, 'hardening_data_sample'), 10, 3 );

		$this->risk_naming = [
			'l' => __('low-risk', 'really-simple-ssl'),
			'm' => __('medium-risk', 'really-simple-ssl'),
			'h' => __('high-risk', 'really-simple-ssl'),
			'c' => __('critical', 'really-simple-ssl'),
		];
		self::$_this = $this;
	}

	function hardening_data( array $response, string $action, $data ): array {
		if ( ! rsssl_user_can_manage() ) {
			return $response;
		}
		if ( $action === 'hardening_data' ) {
			$response = $this->get_stats( $data );
		}

		return $response;
	}

	function hardening_data_sample (array $response, string $action, $data): array {
		if ( ! rsssl_user_can_manage() ) {
			return $response;
		}
		if ( $action === 'hardening_data_sample' ) {
			$response = $this->get_stats_sample( $data );
		}

		return $response;
	}

	static function this()
	{
		return self::$_this;
	}

	/* Public Section 2: DataGathering */

	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function get_stats($data): array
	{
		if ( ! rsssl_user_can_manage() ) {
			return [];
		}

		$vulEnabled = rsssl_get_option('enable_vulnerability_scanner');
		//now we fetch all plugins that have an update available.

		$stats = [
			'updates' => $this->getAllUpdatesCount(),
			'lastChecked' => time(),
			'riskNaming'   => $this->risk_naming,
			'vulEnabled' => $vulEnabled,
		];

		$repsonse = [
			"request_success" => true,
			'data' => apply_filters('rsssl_vulnerability_data', $stats),
		];
		error_log('hardening data: '.print_r($repsonse, true));
		return $repsonse;
	}

	/**
	 * @return int
	 */
	public function getAllUpdatesCount(): int
	{
		$updates = wp_get_update_data();
//		x_log($updates);
		//we only want core, plugins and themes
		$updates = array_slice($updates, 0, 3);

		return array_sum($updates);
	}

	private function get_stats_sample( $data ) {
		if ( ! rsssl_user_can_manage() ) {
			return [];
		}

		$vulEnabled = rsssl_get_option('enable_vulnerability_scanner');
		//now we fetch all plugins that have an update available.

		$stats = [
			'updates' => $this->getAllUpdatesCount(),
			'lastChecked' => time(),
			'riskNaming'   => $this->risk_naming,
			'vulEnabled' => $vulEnabled,
			'sampleList' => $this->sample_list(),
		];

		$response = [
			"request_success" => true,
			'data' => apply_filters('rsssl_vulnerability_data', $stats),
		];
		error_log('hardening sample data: '.print_r($response, true));
		return $response;
	}

	public static function sample_list() {
		//based on the workableplugin data we return a dummy array.
		return [
			[
				'Name' => __('WP Security Plugin', 'wp-security-plugin'),
				'risk_level' => 'low',
				'date' => date('Y-m-d'),
				'risk_name' => __('low-risk', 'really-simple-ssl'),
			],
			[
				'Name' => __('WP Backup Plugin', 'wp-backup-plugin'),
				'risk_level' => 'medium',
				'date' => date('Y-m-d'),
				'risk_name' => __('medium-risk', 'really-simple-ssl'),
			],
			[
				'Name' => __('WP Performance Plugin', 'wp-performance-plugin'),
				'risk_level' => 'high',
				'date' => date('Y-m-d'),
				'risk_name' => __('high-risk', 'really-simple-ssl'),
			],
		];
	}
}
