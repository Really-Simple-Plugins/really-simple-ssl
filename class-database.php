<?php
defined('ABSPATH') or die("you do not have acces to this page!");

class rlrsssl_database {
  public
    $filesWithHTTP                 = Array(),
    $optionsWithHTTP               = Array(),
    $blocked_urls                  = Array(),
    $url_pattern                   = '([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-]?[\'"]+)',
    $search_array                  = Array();


  public function __construct()
  {
    require_once( dirname( __FILE__ ) .  '/class-url.php' );
    $this->url = new rlrsssl_url;
  }

  public function build_query( $where = '' ){
      global $wpdb;
      $where.= ' AND (';
      $where.= sprintf(' %1$s.post_content ',$wpdb->posts);
      $where.= " LIKE '%http://%'";
      $where.=") ";
      return $where;
  }



      public function scan($search_array) {
        global $wpdb;
        $tbl_query = "show tables";
        $tables = $wpdb->get_results($tbl_query);
        foreach ($tables as $table) {
          $col_query = "show columns from {$table}";
          $columns = $wpdb->get_results($col_query);

          $pattern = '/<(?:img|iframe) .*?src=[\'"]\K(http:\/\/)(?!'.$url.')'.$url_pattern.'/i';
          $query = "select * from {$table} where someName REGEXP '{$pattern}'";
        }
        $posts = array();




        $this->filesWithHTTP = $this->get_posts_with_blocked_resources($posts, $url_list);


        //scan options
        global $wpdb;
        $where = sprintf('Select * FROM %1$s WHERE (',$wpdb->options);
        $where.= sprintf(' %1$s.option_value ',$wpdb->options);
        $where.= " LIKE '%http://%'";

        $where.= sprintf(') AND NOT (%1$s.option_name = "siteurl" OR %1$s.option_name = "home" ',$wpdb->options);
        $where.= " OR ".$wpdb->options.".option_name LIKE '_transient%' OR ".$wpdb->options.".option_name LIKE '_site_transient%')";
        $results = $wpdb->get_results($where);

        foreach ($results as $result) {
          array_push($this->optionsWithHTTP,$result->option_name);
        }
      }




        private function get_posts_with_blocked_resources($filesWithHTTP, $url_list, $url_only = false) {

          $url_pattern = $this->url_pattern;

          $url_list = str_replace("http://", "", $url_list);
          if ($url_only) {
            $patterns = array($url_pattern);
          } else {
            $patterns = array();
            foreach($url_list as $url) {
              $url = preg_quote($url, "/");
              $patterns = array_merge($patterns, array(
                '/url\([\'"]?\K(http:\/\/)'.$url_pattern.'(?!'.$url.')/i',
                '/<link .*?href=[\'"]\K(http:\/\/)'.$url_pattern.'(?!'.$url.')/i',
                '/<meta property="og:image" .*?content=[\'"]\K(http:\/\/)'.$url_pattern.'(?!'.$url.')/i',
                '/<(?:img|iframe) .*?src=[\'"]\K(http:\/\/)(?!'.$url.')'.$url_pattern.'/i',
                '/<script [^>]*?src=[\'"]\K(http:\/\/)(?!'.$url.')'.$url_pattern.'/i',
              ));
            }
          }

          //search for occurence of blocked links
          foreach ($filesWithHTTP as $fName => $id) {

            $str = get_the_content($id);
            foreach ($patterns as $pattern){
              if (preg_match_all($pattern, $str, $matches, PREG_PATTERN_ORDER)) {
                foreach($matches[0] as $match) {
                  $timeout = 2;
                  $test_url = rtrim($match,'"');
                  $test_url = rtrim($test_url,"'");
                  $test_url = str_replace("http://", "http://", $test_url);

                  if (isset($this->blocked_urls[$file]) && in_array($test_url, $this->blocked_urls[$file])) break;
                  $filecontents = $this->url->get_contents($test_url, $timeout);
                  if($this->url->error_number!=0) {
                    $filesWithHTTP[$fName] = $file;
                    if (!isset($this->blocked_urls[$file]) || (isset($this->blocked_urls[$file]) && !in_array($test_url, $this->blocked_urls[$file]))) {
                      $this->blocked_urls[$file][] = $test_url;
                    }
                  }
                }
              }
            }

          }

          return $filesWithHTTP;

        }



}
