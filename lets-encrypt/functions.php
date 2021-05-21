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

if ( !function_exists('rsssl_letsencrypt_wizard_url') ) {
    function rsssl_letsencrypt_wizard_url(){
    	if (is_multisite() && !is_main_site()) {
		    return add_query_arg(array('page' => 'rlrsssl_really_simple_ssl', 'tab' => 'letsencrypt'), get_admin_url(get_main_site_id(),'options-general.php') );
	    } else {
		    return add_query_arg(array('page' => 'rlrsssl_really_simple_ssl', 'tab' => 'letsencrypt'), admin_url('options-general.php') );
	    }
    }
}

/**
 * Check if we need to use DNS verification
 * @return bool
 */
function rsssl_dns_verification_required(){
	if ( get_option('rsssl_verification_type')==='DNS' ) {
		return true;
	}
	if ( rsssl_wildcard_certificate_required() ) {
		return true;
	}

	return false;
}
/**
 * Check if we're on CPanel
 * @return bool
 */
function rsssl_is_cpanel(){
	return file_exists("/usr/local/cpanel");
}

/**
 * Check if CPanel supports the api
 * @return bool
 */
function rsssl_cpanel_api_supported(){
	return rsssl_is_cpanel() && file_exists("/usr/local/cpanel/php/cpanel.php");
}

/**
 * https://stackoverflow.com/questions/26927248/how-to-detect-servers-control-panel-type-with-php
 * @return false
 */
function rsssl_is_plesk(){
	if ( is_dir( '/usr/local/psa' ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * check if this is a server that is not automatically detected
 * @return bool
 */
function rsssl_is_other(){
	$response = RSSSL_LE()->letsencrypt_handler->server_software();
	return $response->status === 'warning';
}
/**
 * @param string $item
 */
function rsssl_progress_add($item){
	$progress = get_option("rsssl_le_installation_progress", array() );
	if (!in_array($item, $progress)){
		$progress[] = $item;
		update_option("rsssl_le_installation_progress", $progress );
	}
}

/**
 * @param string $item
 */
function rsssl_progress_remove($item){
	$progress = get_option("rsssl_le_installation_progress", array());
	if (in_array($item, $progress)){
		$index = array_search($item, $progress);
		unset($progress[$index]);
		update_option("rsssl_le_installation_progress", $progress);
	}
}

/**
 * Get PHP version status for tests
 * @return RSSSL_RESPONSE
 */
function rsssl_php_requirement_met(){
	if (version_compare(PHP_VERSION, rsssl_le_php_version, '<')) {
		rsssl_progress_remove('system-status');
		$action = 'stop';
		$status = 'error';
		$message = sprintf(__("The minimum requirements for the PHP version have not been met. Please upgrade to %s", "really-simple-ssl"), rsssl_le_php_version );
	} else {
		$action = 'continue';
		$status = 'success';
		$message = __("You have the required PHP version to continue.", "really-simple-ssl" );
	}
	return new RSSSL_RESPONSE($status, $action, $message);
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
        $html
            = sprintf( __( "For more information on this subject, please read this %sarticle%s",
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

function rsssl_get_other_host(){
	return rsssl_get_value('other_host_type', false);
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

/**
 * Move to add on
 */



/**
 * Override for lets encrypt
 * Needs to be loaded directly (not in hook)
 *
 * @param array $subjects
 *
 * @return array
 */

function rsssl_le_subjects( $subjects ) {
	if ( rsssl_wildcard_certificate_required() ) {
		$domain = rsssl_get_domain();
		//in theory, the main site of a subdomain setup can be a www. domain. But we have to request a certificate without the www.
		$domain   = str_replace( 'www.', '', $domain );
		$subjects = array(
			$domain,
			'*.' . $domain,
		);
	}
	return $subjects;
}

add_filter( 'rsssl_le_subjects', 'rsssl_le_subjects' );

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
add_filter( 'rsssl_steps', 'rsssl_maybe_drop_subdomain_test' , 20);

