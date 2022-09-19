<?php
defined('ABSPATH') or die();
class rsssl_progress {
	private static $_this;

	function __construct() {
		if ( isset( self::$_this ) )
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
		self::$_this = $this;
	}

	static function this() {
		return self::$_this;
	}

	public function get() {
		return [
			'text' => $this->get_text(),
			'percentage' => $this->percentage(),
			'notices' => $this->notices(),
		];
	}

	public function notices(){
		$notices = RSSSL()->really_simple_ssl->get_notices_list(array( 'status' => 'all' ));
		$out = [];
		foreach ($notices as $id => $notice ) {
			$notice['id'] = $id;
			$out[] =  $notice;
		}
		return $out;
	}

	/**
	 * Calculate the percentage completed in the dashboard progress section
	 * Determine max score by adding $notice['score'] to the $max_score variable
	 * Determine actual score by adding $notice['score'] of each item with a 'success' output to $actual_score
	 * @return int
	 *
	 * @since 4.0
	 *
	 */

	private function percentage() {
		if ( ! rsssl_user_can_manage() ) {
			return 0;
		}

		$max_score    = 0;
		$actual_score = 0;
		$notices = RSSSL()->really_simple_ssl->get_notices_list(array(
			'status' => 'all',
		));
		foreach ( $notices as $id => $notice ) {
			if (isset( $notice['score'] )) {
				// Only items matching condition will show in the dashboard. Only use these to determine max count.
				$max_score = $max_score + intval( $notice['score'] );
				$success = ( isset( $notice['output']['icon'] )
				             && ( $notice['output']['icon']
				                  === 'success' ) ) ? true : false;
				if ( $success ) {
					// If the output is success, task is completed. Add to actual count.
					$actual_score = $actual_score + intval( $notice['score'] );
				}
			}
		}
		$score = $max_score>0 ? $actual_score / $max_score :0;
		return intval( round( $score * 100 ) );
	}

	/**
	 * Get text for progress block
	 *
	 * @return string
	 */
	private function get_text(){
		if (!rsssl_user_can_manage()) return '';
		ob_start();

		$lowest_possible_task_count = $this->get_lowest_possible_task_count();
		$open_task_count = count( RSSSL()->really_simple_ssl->get_notices_list( array( 'status' => 'open' ) ));
		if (RSSSL()->really_simple_ssl->ssl_enabled) {
			$doing_well = __( "SSL is activated on your site.",  'really-simple-ssl' ) . ' ' . sprintf( _n( "You still have %s task open.", "You still have %s tasks open.", $open_task_count, 'really-simple-ssl' ), $open_task_count );
			if ( $open_task_count === 0 ) {
				_e("SSL configuration finished!", "really-simple-ssl");
			} elseif ( !defined('rsssl_pro_version') ){
				if ( $open_task_count >= $lowest_possible_task_count) {
					echo $doing_well;
				} else {
					printf(__("Basic SSL configuration finished! Improve your score with %sReally Simple SSL Pro%s.", "really-simple-ssl"), '<a target="_blank" href="' . RSSSL()->really_simple_ssl->pro_url . '">', '</a>');
				}
			} else {
				echo $doing_well;
			}
		} else {
			if ( !is_network_admin() ) _e("SSL is not yet enabled on this site." , "really-simple-ssl");
		}
		do_action('rsssl_progress_feedback');
		return ob_get_clean();
	}

	/**
	 * Count number of premium notices we have in the list.
	 * @return int
	 */
	public function get_lowest_possible_task_count() {
		$premium_notices = RSSSL()->really_simple_ssl->get_notices_list(array('premium_only'=>true));
		return count($premium_notices) ;
	}

	/**
	 * Process the react dismissal of a task
	 *
	 * Since 3.1
	 *
	 * @access public
	 *
	 */

	public function dismiss_task($id)
	{
		if ( !empty($id) ) {
			$id = sanitize_title( $id );
			update_option( "rsssl_".$id."_dismissed", true, false );
			delete_transient( 'rsssl_plusone_count' );

			// count should be updated, therefore clear cache
			$this->clear_transients();
		}

		return [
			'percentage' => $this->percentage(),
		];
	}

	/**
	 * Clear some transients
	 */

	public function clear_transients(){
		delete_transient('rsssl_mixed_content_fixer_detected');
		delete_transient('rsssl_plusone_count');
		delete_transient( 'rsssl_can_use_curl_headers_check' );
		delete_transient( 'rsssl_admin_notices' );
	}
}