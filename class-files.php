<?php
defined('ABSPATH') or die("you do not have acces to this page!");

  class rlrsssl_files {
  public
    $files_with_http         = array(),
    $mixed_content_detected        = FALSE,
    $files_with_blocked_resources  = array(),
    $plugin_path,
    $blocked_urls = array(),
    $url;


  public function __construct()
  {
    require_once( dirname( __FILE__ ) .  '/class-url.php' );
    $this->plugin_path = dirname(dirname( __FILE__ ));
    $this->url = new rlrsssl_url;
  }

  public function scan($url_list){
    //remove last items of array so only two remain.
    $url_list = array_slice($url_list, 0, 1);
    $file_array = array();

    $childtheme_dir = get_stylesheet_directory();
    $parenttheme_dir = get_template_directory();

    $file_array = $this->get_filelist_from_dir($childtheme_dir);
    //if parentthemedir and childtheme dir are different, check those as well
    if (strcasecmp($childtheme_dir, $parenttheme_dir)==0) {
      $file_array = array_merge($file_array, $this->get_filelist_from_dir($parenttheme_dir));
    }

    $file_array = array_merge($file_array, $this->get_filelist_from_dir($this->plugin_path));

    $this->files_with_blocked_resources = $this->get_files_with_blocked_resources($file_array, $url_list);
    $this->files_with_http = $this->get_files_with_http($file_array);

    //only values that do not occur in blocke resources array.
    $this->files_with_http = array_diff($this->files_with_http, $this->files_with_blocked_resources);

    if (  (count($this->files_with_http)>0) || (count($this->files_with_blocked_resources)>0) ) $this->mixed_content_detected = TRUE;
  }




  private function get_files_with_blocked_resources($file_array, $url_list) {
    $filesWithHTTP = array();
    $url_pattern = '([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-]?[\'"]+)';

    $url_list = str_replace("http://", "", $url_list);
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

    //search for occurence of blocked links
    foreach ($file_array as $fName => $file) {
      if(file_exists($file)) {
        $str = file_get_contents($file);
        foreach ($patterns as $pattern){
          if (preg_match_all($pattern, $str, $matches, PREG_PATTERN_ORDER)) {
            $timeout = 2;
            foreach($matches[0] as $match) {
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
    }
    return $filesWithHTTP;

  }

  private function get_files_with_http($file_array) {
    $filesWithHTTP = array();

    $patterns = array(
      '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
      '/<link .*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      '/<meta property="og:image" .*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      '/<(?:img|iframe) .*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
      '/<script [^>]*?src=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
    );

    //search for occurence of links without https
    foreach ($file_array as $fName => $file) {
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      //only check js and css
      if(file_exists($file) && in_array($ext, array("css", "js"))) {
        $str = file_get_contents($file);
        foreach ($patterns as $pattern){
          if (preg_match($pattern, $str)) {
            $filesWithHTTP[$fName] = $file;
          }
        }
      }
    }
    return $filesWithHTTP;

  }

      /**
      *  Get a list of files from a directory, with the extensions as passed.
      *   @param array() $extensions list of extensions to search for.
      *   @param string $path: path to directory to search in.
      */

      private function get_filelist_from_dir($path) {
        $filelist = array();
        $extensions = array("php", "css", "js");
        if ($handle = opendir($path))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." && $file != "..")
                {
                    $fName  = $file;
                    $file   = $path.'/'.$file;
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if(is_file($file) && in_array($ext, $extensions)){
                      $filelist[$fName] = $file;
                    } elseif (is_dir($file)) {
                      $filelist = array_merge($filelist, $this->get_filelist_from_dir($file, $extensions));
                    }
                }
            }
            closedir($handle);
        }
        return $filelist;
      }

      public function get_path_to($directory, $file) {
        //find position witin wp-content
        $needle = "wp-content/".$directory."/";
        $pos = strpos($file,$needle);
        if ($pos!==false)
          return substr($file, $pos+strlen($needle));
        return $file;
      }


}
