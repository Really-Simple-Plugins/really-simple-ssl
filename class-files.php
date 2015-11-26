<?php
defined('ABSPATH') or die("you do not have acces to this page!");

  class rlrsssl_files {
  public
    $fileArray                     = Array(),
    $filesWithHTTP                 = Array(),
    $search_array                  = Array();


  public function __construct()
  {

  }

      public function scan($search_array){
        $this->search_array = $search_array;
        $childtheme = get_stylesheet_directory();
        $this->get_filelist_from_dir($childtheme);

        $parenttheme = get_template_directory();
        //if parentthemedir and childtheme dir are different, check those as well
        if (strcasecmp($childtheme, $parenttheme)==0) {
          $this->get_filelist_from_dir($parenttheme);
        }

        //search for occurence of links without https
        $search_array = $this->search_array;
        foreach ($this->fileArray as $fName => $file) {
          if(file_exists($file)) {
            $str = file_get_contents($file);
            foreach ($search_array as $needle) {
              if (strpos($str, $needle)!==false && !in_array($file, $this->filesWithHTTP)) {
                $this->filesWithHTTP[$fName] = $file;
              }
            }
          }
        }
      }

      private function get_filelist_from_dir($path) {
        if ($handle = opendir($path))
        {
            while (false !== ($file = readdir($handle)))
            {
                if ($file != "." && $file != "..")
                {
                    $fName  = $file;
                    $file   = $path.'/'.$file;

                    if(is_file($file) && strpos($fName, ".php")!==false) {
                      $this->fileArray[$fName] = $file;
                    } elseif (is_dir($file)) {
                      $this->get_filelist_from_dir($file);
                    }
                }
            }

            closedir($handle);
        }

      }

      public function get_path_to_themes($file) {
        //find position fo wp-content
        $needle = "wp-content/themes/";
        $pos = strpos($file,$needle);
        if ($pos!==false)
          return substr($file, $pos+strlen($needle));
        return $file;
      }


}
