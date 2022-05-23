<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
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
		error_log(print_r($out, true));

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
		if ( ! current_user_can( 'manage_options' ) ) {
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
		if (!current_user_can('manage_options')) return '';
		ob_start();

		$lowest_possible_task_count = RSSSL()->really_simple_ssl->get_lowest_possible_task_count();
		$open_task_count = count( RSSSL()->really_simple_ssl->get_notices_list( array( 'status' => 'open' ) ));
		if (RSSSL()->really_simple_ssl->ssl_enabled) {
			$doing_well = __( "SSL is activated on your site.",  'really-simple-ssl' ) . ' ' . sprintf( _n( "You still have %s task open.", "You still have %s tasks open.", $open_task_count, 'really-simple-ssl' ), '<span class="rsssl-progress-count">'.$open_task_count.'</span>' );
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
	 * Process the ajax dismissal of a task
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
			update_option( "rsssl_".$id."_dismissed", true );
			delete_transient( 'rsssl_plusone_count' );

			// count should be updated, therefore clear cache
			RSSSL()->really_simple_ssl->clear_transients();
		}

		return [
			'percentage' => $this->percentage(),
		];
	}

	public function progress_footer_button(){
		$button_text = __("Go PRO!", "really-simple-ssl");
		$button_link = RSSSL()->really_simple_ssl->pro_url;
		$go_pro = "<a href='$button_link' target='_blank' class='button button-default upsell'>$button_text</a>";
		$activate_btn = "";
		if (!RSSSL()->really_simple_ssl->ssl_enabled) {
			if ( RSSSL()->really_simple_ssl->site_has_ssl || ( defined( 'RSSSL_FORCE_ACTIVATE' ) && RSSSL_FORCE_ACTIVATE ) ) {
				$button_text = __( "Activate SSL", "really-simple-ssl" );
				$activate_btn = '<form action="" method="post" ><input type="submit" class="button button-primary" value="' . $button_text . '" id="rsssl_do_activate_ssl" name="rsssl_do_activate_ssl"></form>';
			}
		}
		return '<span class="rsssl-footer-item footer-left">'.apply_filters("rsssl_progress_footer_left", '').$activate_btn.apply_filters("rsssl_progress_footer_right", $go_pro ).'</span>';
	}
}