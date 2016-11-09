<?php
defined('ABSPATH') or die("you do not have acces to this page!");
/*

    class for handling url retrieval

*/

if ( ! class_exists( 'rsssl_url' ) ) {
  class rsssl_url {
    public $error_number = 0;
    private static $_this;

  function __construct() {
    if ( isset( self::$_this ) )
        wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

    self::$_this = $this;
  }

  static function this() {
    return self::$_this;
  }


  /**
   * Handles any errors as the result of trying to open a https page when there may be no ssl.
   *
   * @since  2.0
   *
   * @access public
   *
   */

  private function custom_error_handling($errno, $errstr, $errfile, $errline, array $errcontext) {
      $this->error_number = $errno;
  }

  /*
      retrieves the content of an url
      If a redirection is in place, the new url serves as input for this function
      max 5 iterations
  */


  public function get_contents($url, $timeout = 5, $iteration=0) {
    $use_curl = $this->is_curl_installed();
    //prevent infinite loops.
    if ($iteration>3) {
      $this->error_number = 404;
      $use_curl = false;
    }

    //preferrably with curl, but else with file get contents
    if ($use_curl) {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER, true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        curl_setopt($ch,CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch,CURLOPT_USERAGENT, 'User-Agent: curl/7.39.0');
        $filecontents = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(curl_errno($ch)) {
          $this->error_number = curl_errno($ch);
        } else {
          $this->error_number = 0;
        }

        curl_close($ch);
        if ($this->error_number==0 && $http_code != 200) { //301, 302, 403, 404, etc.
            if ($http_code == 301 || $http_code == 302) {
                list($header) = explode("\r\n\r\n", $filecontents, 2);
                $matches = array();
                preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
                $url = trim(str_replace($matches[1],"",$matches[0]));
                $url_parsed = parse_url($url);
                //return isset($url_parsed) ? $this->get_contents($url, $timeout, $iteration+1) : '';
                if (isset($url_parsed)) {
                  return $this->get_contents($url, $timeout, $iteration+1);
                } else {
                  $this->error_number = 404;
                }
            } else { //403, 404
              $this->error_number = $http_code;
            }
        } elseif( ($this->error_number==0) && ($http_code == 200)) {
          error_log("returning filecontents through curl.");
          return $filecontents;
        }
      }
      //if we are here, curl didn't return a valid response, so we try with file_get_contents
      set_error_handler(array($this,'custom_error_handling'));
      $filecontents = file_get_contents($url);
      //errors back to normal
      restore_error_handler();
      return $filecontents;
  }

  /**
   * Checks if the curl function is available
   *
   * @since  2.1
   *
   * @access public
   *
   */

  private function is_curl_installed() {
    if  (in_array  ('curl', get_loaded_extensions())) {
      return true;
    }
    else {
      return false;
    }
  }

  public function get_curl_error($error_no) {

    $error_codes=array(
      0 => 'CURLE_SUCCESS',
      1 => 'CURLE_UNSUPPORTED_PROTOCOL',
      2 => 'CURLE_FAILED_INIT',
      3 => 'CURLE_URL_MALFORMAT',
      4 => 'CURLE_URL_MALFORMAT_USER',
      5 => 'CURLE_COULDNT_RESOLVE_PROXY',
      6 => 'CURLE_COULDNT_RESOLVE_HOST',
      7 => 'CURLE_COULDNT_CONNECT',
      8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
      9 => 'CURLE_REMOTE_ACCESS_DENIED',
      11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
      13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
      14 =>'CURLE_FTP_WEIRD_227_FORMAT',
      15 => 'CURLE_FTP_CANT_GET_HOST',
      17 => 'CURLE_FTP_COULDNT_SET_TYPE',
      18 => 'CURLE_PARTIAL_FILE',
      19 => 'CURLE_FTP_COULDNT_RETR_FILE',
      21 => 'CURLE_QUOTE_ERROR',
      22 => 'CURLE_HTTP_RETURNED_ERROR',
      23 => 'CURLE_WRITE_ERROR',
      25 => 'CURLE_UPLOAD_FAILED',
      26 => 'CURLE_READ_ERROR',
      27 => 'CURLE_OUT_OF_MEMORY',
      28 => 'CURLE_OPERATION_TIMEDOUT',
      30 => 'CURLE_FTP_PORT_FAILED',
      31 => 'CURLE_FTP_COULDNT_USE_REST',
      33 => 'CURLE_RANGE_ERROR',
      34 => 'CURLE_HTTP_POST_ERROR',
      35 => 'CURLE_SSL_CONNECT_ERROR',
      36 => 'CURLE_BAD_DOWNLOAD_RESUME',
      37 => 'CURLE_FILE_COULDNT_READ_FILE',
      38 => 'CURLE_LDAP_CANNOT_BIND',
      39 => 'CURLE_LDAP_SEARCH_FAILED',
      41 => 'CURLE_FUNCTION_NOT_FOUND',
      42 => 'CURLE_ABORTED_BY_CALLBACK',
      43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
      45 => 'CURLE_INTERFACE_FAILED',
      47 => 'CURLE_TOO_MANY_REDIRECTS',
      48 => 'CURLE_UNKNOWN_TELNET_OPTION',
      49 => 'CURLE_TELNET_OPTION_SYNTAX',
      51 => 'CURLE_PEER_FAILED_VERIFICATION',
      52 => 'CURLE_GOT_NOTHING',
      53 => 'CURLE_SSL_ENGINE_NOTFOUND',
      54 => 'CURLE_SSL_ENGINE_SETFAILED',
      55 => 'CURLE_SEND_ERROR',
      56 => 'CURLE_RECV_ERROR',
      58 => 'CURLE_SSL_CERTPROBLEM',
      59 => 'CURLE_SSL_CIPHER',
      60 => 'CURLE_SSL_CACERT',
      61 => 'CURLE_BAD_CONTENT_ENCODING',
      62 => 'CURLE_LDAP_INVALID_URL',
      63 => 'CURLE_FILESIZE_EXCEEDED',
      64 => 'CURLE_USE_SSL_FAILED',
      65 => 'CURLE_SEND_FAIL_REWIND',
      66 => 'CURLE_SSL_ENGINE_INITFAILED',
      67 => 'CURLE_LOGIN_DENIED',
      68 => 'CURLE_TFTP_NOTFOUND',
      69 => 'CURLE_TFTP_PERM',
      70 => 'CURLE_REMOTE_DISK_FULL',
      71 => 'CURLE_TFTP_ILLEGAL',
      72 => 'CURLE_TFTP_UNKNOWNID',
      73 => 'CURLE_REMOTE_FILE_EXISTS',
      74 => 'CURLE_TFTP_NOSUCHUSER',
      75 => 'CURLE_CONV_FAILED',
      76 => 'CURLE_CONV_REQD',
      77 => 'CURLE_SSL_CACERT_BADFILE',
      78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
      79 => 'CURLE_SSH',
      80 => 'CURLE_SSL_SHUTDOWN_FAILED',
      81 => 'CURLE_AGAIN',
      82 => 'CURLE_SSL_CRL_BADFILE',
      83 => 'CURLE_SSL_ISSUER_ERROR',
      84 => 'CURLE_FTP_PRET_FAILED',
      84 => 'CURLE_FTP_PRET_FAILED',
      85 => 'CURLE_RTSP_CSEQ_ERROR',
      86 => 'CURLE_RTSP_SESSION_ERROR',
      87 => 'CURLE_FTP_BAD_FILE_LIST',
      88 => 'CURLE_CHUNK_FAILED',
      401 => 'NOT AUTHORISED',
      403 => 'FORBIDDEN',
      404 => 'NOT FOUND',
    );
      if (!isset($error_codes[$error_no])) return "Unknown error";
      return $error_codes[$error_no];
    }
  }
}
