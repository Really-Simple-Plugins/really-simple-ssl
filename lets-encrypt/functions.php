<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! function_exists( 'rsssl_user_can_manage' ) ) {
    function rsssl_user_can_manage() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return true;
    }
}



/**
 * Check if we need to use DNS verification
 * @return bool
 */
function rsssl_dns_verification_required(){

	/**
	 * If our current hosting provider does not allow or require local SSL certificate generation,
	 * We do not need to DNS verification either.
	 */

	if ( !rsssl_do_local_lets_encrypt_generation() ) {
		return false;
	}

	if ( get_option('rsssl_verification_type')==='DNS' ) {
		return true;
	}

	if ( rsssl_wildcard_certificate_required() ) {
		return true;
	}

	return false;
}

if ( !function_exists('rsssl_is_cpanel')) {
	/**
	 * Check if we're on CPanel
	 *
	 * @return bool
	 */
	function rsssl_is_cpanel() {
		if (get_option('rsssl_force_cpanel')) {
			return true;
		}

		$open_basedir = ini_get("open_basedir");
		if ( empty($open_basedir) && file_exists( "/usr/local/cpanel" ) ) {
			return true;
		} else if (rsssl_check_port(2082)) {
			return true;
		} else {
			return false;
		}
	}
}

if (!function_exists('rsssl_cpanel_api_supported')) {
	/**
	 * Check if CPanel supports the api
	 *
	 * @return bool
	 */
	function rsssl_cpanel_api_supported() {
		return rsssl_is_cpanel() && file_exists( "/usr/local/cpanel/php/cpanel.php" );
	}
}

if (!function_exists('rsssl_activated_by_default')) {
	/**
	 * Check if the host has ssl, activated by default
	 *
	 * @return bool
	 */
	function rsssl_activated_by_default() {
		$activated_by_default =  false;
		$activated_by_default_hosts = RSSSL_LE()->config->activated_by_default;
		$current_host         = rsssl_get_other_host();
		if ( in_array( $current_host, $activated_by_default_hosts ) ) {
			$activated_by_default =  true;
		}
		return $activated_by_default;
	}
}

if (!function_exists('rsssl_activation_required')) {
	/**
	 * Check if the host has ssl, activation required
	 *
	 * @return bool
	 */
	function rsssl_activation_required() {
		$dashboard_activation_required =  false;
		$dashboard_activation_required_hosts = RSSSL_LE()->config->dashboard_activation_required;
		$current_host         = rsssl_get_other_host();
		if ( in_array( $current_host, $dashboard_activation_required_hosts ) ) {
			$dashboard_activation_required =  true;
		}
		return $dashboard_activation_required;
	}
}

if (!function_exists('rsssl_paid_only')) {
	/**
	 * Check if the host has ssl, paid only
	 *
	 * @return bool
	 */
	function rsssl_paid_only() {
		$paid_only =  false;
		$paid_only_hosts = RSSSL_LE()->config->paid_only;
		$current_host         = rsssl_get_other_host();
		if ( in_array( $current_host, $paid_only_hosts ) ) {
			$paid_only =  true;
		}
		return $paid_only;
	}
}

if ( !function_exists('rsssl_is_plesk')) {
	/**
	 * https://stackoverflow.com/questions/26927248/how-to-detect-servers-control-panel-type-with-php
	 * @return false
	 */
	function rsssl_is_plesk() {
		if (get_option('rsssl_force_plesk')) {
			return true;
		}

		if ( get_option('rsssl_hosting_dashboard')==='plesk' ){
			return true;
		}

		//cpanel takes precedence, as it's more precise
		if ( rsssl_is_cpanel() ) {
			return false;
		}

		$open_basedir = ini_get("open_basedir");
		if ( empty($open_basedir) && is_dir( '/usr/local/psa' ) ) {
			return true;
		} else if (rsssl_check_port(2222)) {
			return true;
		} else {
			return false;
		}
	}
}

if ( !function_exists('rsssl_is_directadmin')) {
	/**
	 * https://stackoverflow.com/questions/26927248/how-to-detect-servers-control-panel-type-with-php
	 * @return bool
	 */
	function rsssl_is_directadmin() {
		if (get_option('rsssl_force_directadmin')) {
			return true;
		}

		if ( get_option('rsssl_hosting_dashboard')==='directadmin' ){
			return true;
		}

		//cpanel takes precedence, as it's more precise
		if ( rsssl_is_cpanel() ) {
			return false;
		}

		if ( rsssl_is_plesk() ) {
			return false;
		}

		if (rsssl_check_port(2222)) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * @param int $port
 *
 * @return bool
 * @throws Exception
 */
function rsssl_check_port( $port)
{
	try {
		$ipAddress = gethostbyname('localhost');
		$link = @fsockopen($ipAddress, $port, $errno, $error);
		if ($error) {
			return false;
		}
	} catch (\Exception $ex) {
		return false;
	}

	if ( $link ) {
		return true;
	}
	return false;
}

if ( !function_exists('rsssl_get_other_host') ) {
	/**
	 * Get the selected hosting provider, if any.
	 * @return bool|string
	 */
	function rsssl_get_other_host() {
		return rsssl_get_value( 'other_host_type', false );
	}
}

if ( !function_exists('rsssl_progress_add')) {
	/**
	 * @param string $item
	 */
	function rsssl_progress_add( $item ) {
		$progress = get_option( "rsssl_le_installation_progress", array() );
		if ( ! in_array( $item, $progress ) ) {
			$progress[] = $item;
			update_option( "rsssl_le_installation_progress", $progress );
		}
	}
}

if ( !function_exists('rsssl_uses_known_dashboard')) {
	/**
	 * Check if website uses any of the known dashboards.
	 */
	function rsssl_uses_known_dashboard( ) {
		if ( rsssl_is_cpanel() || rsssl_is_plesk() || rsssl_is_directadmin() ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( !function_exists('rsssl_is_ready_for')) {
	/**
	 * @param string $item
	 */
	function rsssl_is_ready_for( $item ) {
		if ( !rsssl_do_local_lets_encrypt_generation() ) {
			rsssl_progress_add('directories');
			rsssl_progress_add('generation');
			rsssl_progress_add('dns-verification');
		}

		if ( !rsssl_dns_verification_required() ) {
			rsssl_progress_add('dns-verification');
		}

		if (empty(rsssl_get_not_completed_steps($item))){
			return true;
		} else{
			return false;
		}
	}
}

 function rsssl_get_not_completed_steps($item){
	$sequence = array_column( RSSSL_LE()->config->steps['lets-encrypt'], 'id');
	//drop all statuses after $item. We only need to know if all previous ones have been completed
	$index = array_search($item, $sequence);
	$sequence = array_slice($sequence, 0, $index, true);
	$not_completed = array();
	$finished = get_option("rsssl_le_installation_progress", array());
	foreach ($sequence as $status ) {
		if (!in_array($status, $finished)) {
			$not_completed[] = $status;
		}
	}

	return $not_completed;
}

if ( !function_exists('rsssl_progress_remove')) {
	/**
	 * @param string $item
	 */
	function rsssl_progress_remove( $item ) {
		$progress = get_option( "rsssl_le_installation_progress", array() );
		if ( in_array( $item, $progress ) ) {
			$index = array_search( $item, $progress );
			unset( $progress[ $index ] );
			update_option( "rsssl_le_installation_progress", $progress );
		}
	}
}

if ( !function_exists('rsssl_php_requirement_met')) {
	/**
	 * Get PHP version status for tests
	 *
	 * @return RSSSL_RESPONSE
	 */
	function rsssl_php_requirement_met() {
		if ( version_compare( PHP_VERSION, rsssl_le_php_version, '<' ) ) {
			rsssl_progress_remove( 'system-status' );
			$action  = 'stop';
			$status  = 'error';
			$message = sprintf( __( "The minimum requirements for the PHP version have not been met. Please upgrade to %s", "really-simple-ssl" ), rsssl_le_php_version );
		} else {
			$action  = 'continue';
			$status  = 'success';
			$message = __( "You have the required PHP version to continue.", "really-simple-ssl" );
		}

		return new RSSSL_RESPONSE( $status, $action, $message );
	}
}


if ( ! function_exists( 'rsssl_get_value' ) ) {

    /**
     * Get value for an a rsssl option
     * For usage very early in the execution order, use the $page option. This bypasses the class usage.
     *
     * @param string $fieldname
     * @param bool $use_default
     *
     * @return array|bool|mixed|string
     */

	function rsssl_get_value(
		$fieldname, $use_default = true
	) {
		$default = false;
		$fields = get_option( 'rsssl_options_lets-encrypt' );
		if ($use_default) {
			if ( ! isset( RSSSL_LE()->config->fields[ $fieldname ] ) ) {
				return false;
			}
			$default = ( isset( RSSSL_LE()->config->fields[ $fieldname ]['default'] ) ) ? RSSSL_LE()->config->fields[ $fieldname ]['default'] : '';
		}

		$value   = isset( $fields[ $fieldname ] ) ? $fields[ $fieldname ] : $default;
		return $value;
	}
}

if ( !function_exists('rsssl_do_local_lets_encrypt_generation')) {
	/**
	 * Check if the setup requires local certificate generation
	 * @return bool
	 */
	function rsssl_do_local_lets_encrypt_generation() {
		$not_local_cert_hosts = RSSSL_LE()->config->not_local_certificate_hosts;
		$current_host         = rsssl_get_other_host();
		if ( in_array( $current_host, $not_local_cert_hosts ) ) {
			return false;
		}

		return true;
	}
}

function rsssl_get_manual_instructions_text( $url ){
	$default_url = 'https://really-simple-ssl.com/install-ssl-certificate';
	$dashboard_activation_required =  rsssl_activation_required();
	$activated_by_default =  rsssl_activated_by_default();
	$paid_only =  rsssl_paid_only();
	$button_activate = '<br><a href="'.$default_url.'" target="_blank" class="button button-primary">'.__("Instructions","really-simple-ssl").'</a>&nbsp;&nbsp;';
	$button_complete = '<br><a href="'.$default_url.'" target="_blank" class="button button-primary">'.__("Instructions","really-simple-ssl").'</a>&nbsp;&nbsp;';

	if ( $url === $default_url ) {
		$complete_manually = sprintf(__("Please complete manually in your hosting dashboard.", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>');
		$activate_manually = sprintf(__("Please activate it manually on your hosting dashboard.", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>');
	} else {
		$complete_manually = sprintf(__("Please complete %smanually%s", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>');
		$activate_manually = sprintf(__("Please activate it on your dashboard %smanually%s", "really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>');
		$button_activate .= '<a href="'.$url.'" target="_blank" class="button button-primary">'.__("Go to activation","really-simple-ssl").'</a>';
		$button_complete .= '<a href="'.$url.'" target="_blank" class="button button-primary">'.__("Go to installation","really-simple-ssl").'</a>';
	}

	if ( $activated_by_default ) {
		$msg = sprintf(__("According to our information, your hosting provider supplies your account with an SSL certificate by default. Please contact your %shosting support%s if this is not the case.","really-simple-ssl"), '<a target="_blank" href="'.$url.'">', '</a>').'&nbsp'.
		       __("After completing the installation, you can continue to the next step to complete your configuration.","really-simple-ssl");
	} else if ( $dashboard_activation_required ) {
		$msg = __( "You already have free SSL on your hosting environment.", "really-simple-ssl" ).'&nbsp'.
		       $activate_manually.' '.
		       __("After completing the installation, you can continue to the next step to complete your configuration.","really-simple-ssl")
		       .$button_activate;
	} else if ( $paid_only ) {
		$msg = sprintf(__("According to our information, your hosting provider does not allow any kind of SSL installation, other then their own paid certificate. For an alternative hosting provider with SSL, see this %sarticle%s.","really-simple-ssl"), '<a target="_blank" href="https://really-simple-ssl.com/hosting-providers-with-free-ssl">', '</a>');
	} else {
		$msg = __("Your hosting environment does not allow automatic SSL installation.","really-simple-ssl").' '.
		       $complete_manually.' '.
		       sprintf(__("You can follow these %sinstructions%s.","really-simple-ssl"), '<a target="_blank" href="'.$default_url.'">', '</a>').'&nbsp'.
				__("After completing the installation, you can continue to the next step to complete your configuration.","really-simple-ssl")
		       .$button_complete;
	}

	return $msg;
}

if ( ! function_exists( 'rsssl_notice' ) ) {
    /**
     * Notification without arrow on the left. Should be used outside notifications center
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $remove_after_change
     * @param bool   $echo
     * @param array  $condition $condition['question'] $condition['answer']
     *
     * @return string|void
     */
    function rsssl_notice( $msg, $type = 'notice', $remove_after_change = false, $echo = true, $condition = false) {
        if ( $msg == '' ) {
            return;
        }

        // Condition
        $condition_check = "";
        $condition_question = "";
        $condition_answer = "";
        $rsssl_hidden = "";
        if ($condition) {
            $condition_check = "condition-check";
            $condition_question = "data-condition-question='{$condition['question']}'";
            $condition_answer = "data-condition-answer='{$condition['answer']}'";
            $args['condition'] = array($condition['question'] => $condition['answer']);
            $rsssl_hidden = rsssl_field::this()->condition_applies($args) ? "" : "rsssl-hidden";;
        }

        // Hide
        $remove_after_change_class = $remove_after_change ? "rsssl-remove-after-change" : "";

        $html = "<div class='rsssl-panel-wrap'><div class='rsssl-panel rsssl-notification rsssl-{$type} {$remove_after_change_class} {$rsssl_hidden} {$condition_check}' {$condition_question} {$condition_answer}><div>{$msg}</div></div></div>";

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}

if ( ! function_exists( 'rsssl_sidebar_notice' ) ) {
    /**
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $echo
     * @param bool|array  $condition $condition['question'] $condition['answer']
     *
     * @return string|void
     */

    function rsssl_sidebar_notice( $msg, $type = 'notice', $echo = true, $condition = false) {
        if ( $msg == '' ) {
            return;
        }

        // Condition
        $condition_check = "";
        $condition_question = "";
        $condition_answer = "";
        $rsssl_hidden = "";
        if ($condition) {
            $condition_check = "condition-check";
            $condition_question = "data-condition-question='{$condition['question']}'";
            $condition_answer = "data-condition-answer='{$condition['answer']}'";
            $args['condition'] = array($condition['question'] => $condition['answer']);
            $rsssl_hidden = rsssl_field::this()->condition_applies($args) ? "" : "rsssl-hidden";;
        }

        $html = "<div class='rsssl-help-modal rsssl-notice rsssl-{$type} {$rsssl_hidden} {$condition_check}' {$condition_question} {$condition_answer}>{$msg}</div>";

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}


if (!function_exists('rsssl_read_more')) {
    /**
     * Create a generic read more text with link for help texts.
     *
     * @param string $url
     * @param bool   $add_space
     *
     * @return string
     */
    function rsssl_read_more( $url, $add_space = true ) {
        $html = sprintf( __( "For more information on this subject, please read this %sarticle%s",
            'really-simple-ssl' ), '<a target="_blank" href="' . $url . '">',
            '</a>' );
        if ( $add_space ) {
            $html = '&nbsp;' . $html;
        }

        return $html;
    }
}


register_activation_hook( __FILE__, 'rsssl_set_activation_time_stamp' );
if ( ! function_exists( 'rsssl_set_activation_time_stamp' ) ) {
    function rsssl_set_activation_time_stamp( $networkwide ) {
        update_option( 'rsssl_activation_time', time() );
    }
}

if ( ! function_exists( 'rsssl_array_filter_multidimensional' ) ) {
    function rsssl_array_filter_multidimensional(
        $array, $filter_key, $filter_value
    ) {
        $new = array_filter( $array,
            function ( $var ) use ( $filter_value, $filter_key ) {
                return isset( $var[ $filter_key ] ) ? ( $var[ $filter_key ]
                    == $filter_value )
                    : false;
            } );

        return $new;
    }
}

if ( !function_exists('rsssl_is_subdomain') ) {
	/**
	 * Check if we're on a subdomain.
	 * If this is a www domain, we return false
	 */
	function rsssl_is_subdomain(){
		$domain = rsssl_get_domain();
		if ( strpos($domain, 'www.') !== false ) return false;

		$root = rsssl_get_root_domain($domain);

		if ($root === $domain ) {
			return false;
		} else {
			return true;
		}
	}
}

if ( !function_exists('rsssl_get_subdomain') ) {
	/**
	 * Get root domain of a domain
	 */
	function rsssl_get_root_domain($domain){
		$sub = strtolower(trim($domain));
		$count = substr_count($sub, '.');
		if($count === 2){
			if(strlen(explode('.', $sub)[1]) > 3) $sub = explode('.', $sub, 2)[1];
		} else if($count > 2){
			$sub = rsssl_get_root_domain(explode('.', $sub, 2)[1]);
		}
		return $sub;
	}
}

if ( ! function_exists( 'rsssl_get_domain' ) ) {
    function rsssl_get_domain() {

        //Get current domain
        $domain = site_url();
        //Parse to strip off any /subfolder/
        $parse = parse_url($domain);
        $domain = $parse['host'];

        $domain = str_replace(array('http://', 'https://' ), '', $domain);

        return $domain;
    }
}

function rsssl_insert_after_key($array, $key, $items){
	$keys = array_keys($array);
	$key = array_search($key, $keys);
	$array = array_slice($array, 0, $key, true) +
	$items +
	array_slice($array, 3, count($array)-3, true);

	return $array;
}

if ( !function_exists('rsssl_wildcard_certificate_required') ) {
	/**
	 * Check if the site requires a wildcard
	 *
	 * @return bool
	 */
	function rsssl_wildcard_certificate_required() {
		//if DNS verification, create wildcard.
		if ( get_option('rsssl_verification_type') === 'DNS' ) {
			return true;
		}

		if ( ! is_multisite() ) {
			return false;
		} else {
			if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
				return true;
			} else {
				return false;
			}
		}
	}
}

if ( !function_exists('rsssl_maybe_drop_subdomain_test') ) {
	function rsssl_maybe_drop_subdomain_test( $steps ) {
		if ( is_multisite() ) {
			$index = array_search( 'system-status', array_column( $steps['lets-encrypt'], 'id' ) );
			$index ++;
			$actions = $steps['lets-encrypt'][ $index ]['actions'];
			//get the is_subdomain_setup
			$sub_index = array_search( 'is_subdomain_setup', array_column( $actions, 'action' ) );
			unset( $actions[ $sub_index ] );
			$steps['lets-encrypt'][ $index ]['actions'] = $actions;
		}

		return $steps;
	}

	add_filter( 'rsssl_steps', 'rsssl_maybe_drop_subdomain_test', 20 );
}