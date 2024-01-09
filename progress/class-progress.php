<?php
defined('ABSPATH') or die();
class rsssl_progress {
	private static $_this;

	function __construct() {
		if ( isset( self::$_this ) )
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
		self::$_this = $this;

		add_action( 'admin_init', array( $this, 'dismiss_from_admin_notice') );
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
		$notices = RSSSL()->admin->get_notices_list(array( 'status' => ['open','warning','completed','premium'] ));
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
		$notices = RSSSL()->admin->get_notices_list(array(
			'status' => ['open','warning','completed','premium'],
		));
		foreach ( $notices as $id => $notice ) {
			if (isset( $notice['score'] )) {
				// Only items matching condition will show in the dashboard. Only use these to determine max count.
				$max_score += (int) $notice['score'];
				$success   = isset( $notice['output']['icon'] ) && ( $notice['output']['icon'] === 'success' );
				if ( $success ) {
					// If the output is success, task is completed. Add to actual count.
					$actual_score += (int) $notice['score'];
				}
			}
		}
		$score = $max_score>0 ? $actual_score / $max_score :0;
		return (int) round( $score * 100 );
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
		$open_task_count = count( RSSSL()->admin->get_notices_list( array( 'status' => ['open','warning'] ) ));
		if ( rsssl_get_option('ssl_enabled') ) {
			$doing_well = __( "SSL is activated on your site.",  'really-simple-ssl' ) . ' ' . sprintf( _n( "You still have %s task open.", "You still have %s tasks open.", $open_task_count, 'really-simple-ssl' ), $open_task_count );
			if ( $open_task_count === 0 ) {
				_e("Configuration finished!", "really-simple-ssl");
			} elseif ( !defined('rsssl_pro_version') ){
				if ( $open_task_count >= $lowest_possible_task_count) {
					echo $doing_well;
				} else {
					printf(__("Basic configuration finished! Improve your score with %sReally Simple SSL Pro%s.", "really-simple-ssl"), '<a target="_blank" rel="noopener noreferrer" href="' . RSSSL()->admin->pro_url . '">', '</a>');
				}
			} else {
				echo $doing_well;
			}
		} else if ( !is_network_admin() ) {
			_e( "SSL is not yet enabled on this site.", "really-simple-ssl" );
		}
		do_action('rsssl_progress_feedback');
		return ob_get_clean();
	}

	/**
	 * Count number of premium notices we have in the list.
	 * @return int
	 */
	public function get_lowest_possible_task_count() {
		$premium_notices = RSSSL()->admin->get_notices_list(array('premium_only'=>true));
		return count($premium_notices) ;
	}

	/**
	 * @return void
	 */
	public function dismiss_from_admin_notice(){
		if ( !rsssl_user_can_manage() ) {
			return;
		}
		if (isset($_GET['dismiss_notice'])) {
			$id = sanitize_title($_GET['dismiss_notice']);
			$this->dismiss_task($id);
		}
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
			$count = get_option( 'rsssl_plusone_count' );
			if (is_numeric($count) && $count>0) {
				$count--;
			}
			update_option('rsssl_plusone_count', $count, WEEK_IN_SECONDS);
			//remove this notice from the admin notices list
			$notices = get_option( 'rsssl_admin_notices' );
			if (isset($notices[$id])) {
				unset($notices[$id]);
			}
			update_option('rsssl_admin_notices', $notices);
		}

		return [
			'percentage' => $this->percentage(),
		];
	}
}
