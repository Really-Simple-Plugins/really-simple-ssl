<?php

/**
 * Socket communication class.
 *
 * Originally designed for use with DirectAdmin's API, this class will fill any HTTP socket need.
 *
 * Very, very basic usage:
 *   $Socket = new HTTPSocket;
 *   $Socket->connect('ssl://example.com', 2222);
 *   echo $Socket->get('/CMD_API_SHOW_RESELLER_USER_USAGE');
 *
 * @author Phi1 'l0rdphi1' Stier <l0rdphi1@liquenox.net>
 * @package HTTPSocket
 * @version 3.0.4
 */
class HTTPSocket
{

	var $version = '3.0.4';

	/* all vars are private except $error, $query_cache, and $doFollowLocationHeader */

	var $method = 'GET';

	var $remote_host;
	var $remote_port;
	var $remote_uname;
	var $remote_passwd;

	var $result;
	var $result_header;
	var $result_body;
	var $result_status_code;

	var $bind_host;

	var $error = array();
	var $warn = array();
	var $query_cache = array();

	var $doFollowLocationHeader = TRUE;
	var $redirectURL;
	var $max_redirects = 5;
	var $ssl_setting_message = 'DirectAdmin appears to be using SSL. Change your script to connect to ssl://';

	var $proxy = false;
	var $proxy_headers = array();

	/**
	 * Create server "connection".
	 *
	 */
	function connect($host, $port = '' )
	{
		if (!is_numeric($port)) {
			$port = 80;
		}

		if ( !$this->is_valid_host($host) ) {
			throw new RuntimeException( 'Invalid connection host.' );
		}

		$this->remote_host = $host;
		$this->remote_port = $port;
	}

	/**
	 * Validate a DirectAdmin connection host value.
	 *
	 * Allows plain hostnames and IP addresses, optionally prefixed with
	 * the legacy ssl:// or tcp:// transport strings used by this class.
	 *
	 * @param string $host Connection host value.
	 *
	 * @return bool
	 */
	private function is_valid_host($host)
	{
		$host = trim((string) $host);
		$host = preg_replace('!^(ssl|tcp)://!i', '', $host);

		if ( strlen($host) < 1 || preg_match('![/?#@]!',$host) ) {
			return false;
		}

		$ip_host = trim($host, '[]');
		$is_ip_address = (bool) filter_var($ip_host, FILTER_VALIDATE_IP);
		if ( $is_ip_address ) {
			return true;
		}

		// Reject bracketed non-IP values and host:port combinations.
		if ( $host !== $ip_host ) {
			return false;
		}

		if ( strpos($host, ':') !== false ) {
			return false;
		}

		return (bool) filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
	}

	/**
	 * Normalize a request target to a path on the current origin.
	 *
	 * @param string $location Request or redirect target.
	 *
	 * @return string|false
	 */
	private function normalize_request_target($location)
	{
		$location = trim((string) $location);

		if ( strlen($location) < 1 ) {
			$this->error[] = 'Invalid request URL.';
			return false;
		}

		if ( preg_match('/[\x00-\x1F\x7F]/', $location) ) {
			$this->error[] = 'Invalid request URL.';
			return false;
		}

		if ( preg_match('!^https?://!i', $location) || strpos($location, '//') === 0 ) {
			$redirect = parse_url($location);
			if ( $redirect === false || empty($redirect['host']) ) {
				$this->error[] = 'Invalid request URL.';
				return false;
			}

			$current_host = preg_replace('!^(https?|ssl|tcp)://!i', '', (string) $this->remote_host);
			$current_host = trim($current_host, '[]');
			$redirect_host = trim($redirect['host'], '[]');
			$current_scheme = preg_match('!^(https://|ssl://)!i', (string) $this->remote_host) ? 'https' : 'http';
			$redirect_scheme = isset($redirect['scheme']) ? strtolower($redirect['scheme']) : $current_scheme;

			if ( strtolower($redirect_host) !== strtolower($current_host) ) {
				$this->error[] = 'Cross-host redirects are not allowed.';
				return false;
			}

			if ( $redirect_scheme !== $current_scheme ) {
				$this->error[] = 'Cross-protocol redirects are not allowed.';
				return false;
			}

			if ( isset($redirect['port']) && (int) $redirect['port'] !== (int) $this->remote_port ) {
				$this->error[] = 'Cross-port redirects are not allowed.';
				return false;
			}

			if ( isset($redirect['user']) || isset($redirect['pass']) ) {
				$this->error[] = 'Redirect credentials are not allowed.';
				return false;
			}

			$location = $redirect['path'] ?? '/';
			if ( isset($redirect['query']) && $redirect['query'] !== '' ) {
				if ( preg_match('/[\x00-\x1F\x7F]/', $redirect['query']) ) {
					$this->error[] = 'Invalid request URL.';
					return false;
				}

				$location .= '?' . $redirect['query'];
			}
		}

		if ( !isset($location[0]) || $location[0] !== '/' ) {
			$this->error[] = 'Invalid request path.';
			return false;
		}

		return $location;
	}

	/**
	 * Build a validated same-origin request URL for cURL.
	 *
	 * @param string       $request Request target.
	 * @param string|array $content Query payload.
	 *
	 * @return string|false
	 */
	private function build_safe_request_url($request, $content)
	{
		$request = $this->normalize_request_target($request);
		if ( $request === false ) {
			return false;
		}

		$content = $this->normalize_query_content($content);
		if ($this->method === 'GET' && isset($content) && $content !== '') {
			$request .= '?'.$content;
		}

		$safe_request_url = $this->remote_host.':'.$this->remote_port.$request;
		if ( !filter_var($safe_request_url, FILTER_VALIDATE_URL) ) {
			$this->error[] = 'Invalid request URL.';
			return false;
		}

		return $safe_request_url;
	}

	/**
	 * Normalize query content to the string format used by this class.
	 *
	 * @param string|array $content Query payload.
	 *
	 * @return string
	 */
	private function normalize_query_content($content)
	{
		if (!is_array($content)) {
			return (string) $content;
		}

		$pairs = array();
		foreach ( $content as $key => $value ) {
			$pairs[] = "$key=".urlencode($value);
		}

		return join('&',$pairs);
	}

	function bind( $ip = '' )
	{
		if ( $ip === '' ) {
			$ip = $_SERVER['SERVER_ADDR'];
		}

		$this->bind_host = $ip;
	}

	/**
	 * Change the method being used to communicate.
	 *
	 * @param string|null request method. supports GET, POST, and HEAD. default is GET
	 */
	function set_method( $method = 'GET' )
	{
		$this->method = strtoupper($method);
	}

	/**
	 * Specify a username and password.
	 *
	 * @param string|null username. defualt is null
	 * @param string|null password. defualt is null
	 */
	function set_login( $uname = '', $passwd = '' )
	{
		if ( strlen($uname) > 0 ) {
			$this->remote_uname = $uname;
		}

		if ( strlen($passwd) > 0 ) {
			$this->remote_passwd = $passwd;
		}

	}
	/**
	 * For pass through, this function writes the data in chunks.
	 */
	private function stream_chunk($ch, $data)
	{
		echo($data);
		return strlen($data);
	}
	private function stream_header($ch, $data)
	{
		if (!preg_match('/^HTTP/i', $data)) {
			header($data);
		}
		return strlen($data);
	}


	/**
	 * Query the server
	 *
	 * @param string path like '/CMD_SSL', or same-origin absolute URL.
	 * @param string|array query to pass to url
	 * @param int if connection KB/s drops below value here, will drop connection
	 */
	function query( $request, $content = '', $doSpeedCheck = 0 )
	{
		$this->error = $this->warn = array();
		$this->result_status_code = null;

		$is_ssl = false;

		if (preg_match('!^ssl://!i', $this->remote_host)) {
			$this->remote_host = 'https://'.substr($this->remote_host, 6);
		}

		if (preg_match('!^tcp://!i', $this->remote_host)) {
			$this->remote_host = 'http://'.substr($this->remote_host, 6);
		}

		if (preg_match('!^https://!i', $this->remote_host)) {
			$is_ssl = true;
		}

		$array_headers = array(
			'Host' => ( $this->remote_port == 80 ? $this->remote_host : "$this->remote_host:$this->remote_port" ),
			'Accept' => '*/*',
			'Connection' => 'Close' );

		$this->result = $this->result_header = $this->result_body = '';

		$content = $this->normalize_query_content($content);

		$safe_request_url = $this->build_safe_request_url($request, $content);
		if ( $safe_request_url === false ) {
			return false;
		}

		// Only validated same-origin HTTP/HTTPS URLs reach this sink.
		$ch = curl_init($safe_request_url);

		if ( defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS') ) {
			curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		}

		if ( defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS') ) {
			curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		}

		if ($is_ssl) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //1
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //2
			//curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		}

		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_USERAGENT, "HTTPSocket/$this->version");
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
		curl_setopt($ch, CURLOPT_HEADER, 1);

		if ($this->proxy) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, false);
			curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192); // 8192
			curl_setopt($ch, CURLOPT_WRITEFUNCTION,  array($this, "stream_chunk"));
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, "stream_header"));
		}

		curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 512);
		curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 120);

		// instance connection
		if ($this->bind_host) {
			curl_setopt($ch, CURLOPT_INTERFACE, $this->bind_host);
		}

		// if we have a username and password, add the header
		if ( isset($this->remote_uname) && isset($this->remote_passwd) ) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->remote_uname.':'.$this->remote_passwd);
		}

		// for DA skins: if $this->remote_passwd is NULL, try to use the login key system
		if ( isset($this->remote_uname) && $this->remote_passwd == null ) {
			curl_setopt($ch, CURLOPT_COOKIE, "session={$_SERVER['SESSION_ID']}; key={$_SERVER['SESSION_KEY']}");
		}

		// if method is POST, add content length & type headers
		if ( $this->method == 'POST' ) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

			//$array_headers['Content-type'] = 'application/x-www-form-urlencoded';
			$array_headers['Content-length'] = strlen($content);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $array_headers);


		if( !($this->result = curl_exec($ch)) ) {
			$this->error[] .= curl_error($ch);
		}

		$header_size			= curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$this->result_header	= substr($this->result, 0, $header_size);
		$this->result_body		= substr($this->result, $header_size);
		$this->result_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$this->query_cache[] = $this->remote_host.':'.$this->remote_port.$request;

		$headers = $this->fetch_header();

		// did we get the full file?
		if ( !empty($headers['content-length']) && $headers['content-length'] != strlen($this->result_body) ) {
			$this->result_status_code = 206;
		}

		// now, if we're being passed a location header, should we follow it?
		if ($this->doFollowLocationHeader) {
			//dont bother if we didn't even setup the script correctly
			if (isset($headers['x-use-https']) && $headers['x-use-https']=='yes') {
				die($this->ssl_setting_message);
			}

			if (isset($headers['location'])) {
				if ($this->max_redirects <= 0) {
					die("Too many redirects on: ".$headers['location']);
				}

				$redirect_request = $this->normalize_request_target($headers['location']);
				if ( $redirect_request === false ) {
					return false;
				}

				$this->max_redirects--;
				$this->redirectURL = $headers['location'];
				return $this->query($redirect_request);
			}
		}

	}

	/**
	 * The quick way to get a URL's content :)
	 *
	 * @param string URL
	 * @param boolean return as array? (like PHP's file() command)
	 * @return string result body
	 */
	function get($location, $asArray = false )
	{
		$this->query($location);

		if ( $this->get_status_code() == 200 ) {
			if ($asArray) {
				return preg_split("/\n/",$this->fetch_body());
			}

			return $this->fetch_body();
		}

		return false;
	}

	/**
	 * Returns the last status code.
	 * 200 = OK;
	 * 403 = FORBIDDEN;
	 * etc.
	 *
	 * @return int status code
	 */
	function get_status_code()
	{
		return $this->result_status_code;
	}

	/**
	 * Return the result of a query.
	 *
	 * @return string result
	 */
	function fetch_result()
	{
		return $this->result;
	}

	/**
	 * Return the header of result (stuff before body).
	 *
	 * @param string (optional) header to return
	 * @return array result header
	 */
	function fetch_header( $header = '' )
	{
		if ($this->proxy) {
			return $this->proxy_headers;
		}

		$array_headers = preg_split("/\r\n/",$this->result_header);

		$array_return = array( 0 => $array_headers[0] );
		unset($array_headers[0]);

		foreach ( $array_headers as $pair ) {
			if ($pair == '' || $pair == "\r\n") continue;
			list($key,$value) = preg_split("/: /",$pair,2);
			$array_return[strtolower($key)] = $value;
		}

		if ( $header != '' ) {
			return $array_return[strtolower($header)];
		}

		return $array_return;
	}

	/**
	 * Return the body of result (stuff after header).
	 *
	 * @return string result body
	 */
	function fetch_body()
	{
		return $this->result_body;
	}

	/**
	 * Return parsed body in array format.
	 *
	 * @return array result parsed
	 */
	function fetch_parsed_body()
	{
		parse_str($this->result_body,$x);
		return $x;
	}


	/**
	 * Set a specifc message on how to change the SSL setting, in the event that it's not set correctly.
	 */
	function set_ssl_setting_message($str)
	{
		$this->ssl_setting_message = $str;
	}
}
