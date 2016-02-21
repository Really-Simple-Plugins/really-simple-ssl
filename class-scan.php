<?php
defined('ABSPATH') or die("you do not have acces to this page!");

class rlrsssl_scan {
  private $search_array;
  private $img_warning;
  private $img_success;
  private $img_error;
  private $mixed_content_detected;
  private $autoreplace_insecure_links;

  public function __construct()
  {
    $this->load_translation();
  }

  public function load_translation()
  {
      load_plugin_textdomain('really-simple-ssl', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
  }

  public function set_images($success,$error,$warning) {
    $this->img_warning = $warning;
    $this->img_success = $success;
    $this->img_error   = $error;
  }

  public function init($search_array, $autoreplace){
    $this->search_array               = $search_array;
    $this->autoreplace_insecure_links = $autoreplace;
  }
public function insert_scan() {
  $ajax_nonce = wp_create_nonce( "really-simple-ssl" );
  ?>
  <script type='text/javascript'>
    jQuery(document).ready(function($) {
        //$('#scan-result tr:last').after('<tr id="loader_result"><td></td><td><div class="loader"><?php _e("Scanning...", "really-simple-ssl");?></div></td></tr>');
        $('#scan-list tr:last').after('<tr id="loader_list"><td></td><td><div class="loader"><?php _e("Scanning...", "really-simple-ssl");?><br><?php _e("Please wait, this could take a few minutes", "really-simple-ssl");?></div></td></tr>');

          var data = {
          'action': 'scan',
          'security': '<?php echo $ajax_nonce; ?>',
        };
        $.post(ajaxurl, data, function(response) {
            var obj;
            if (!response) {
              //htmlresult="<tr><td></td><td>Scan not completed, please scan again</td></tr>";
              htmllist = "<tr><td></td><td>Scan not completed, please scan again</td></tr>";
            }
            else {
             obj = jQuery.parseJSON( response );
             //$('#loader_result').replaceWith(obj['htmlresult']);
             $('#loader_list').replaceWith(obj['htmllist']);
            }
        });
    });
  </script>
  <?php
}

public function scan_callback() {
  global $wpdb;
  $result_html = "";
  $list_html = "";
  check_ajax_referer( 'really-simple-ssl', 'security' );
  $files = new rlrsssl_files;
  $files->scan($this->search_array);
  //$database = new rlrsssl_database;
  //$database->scan($this->search_array);

/*
  if ($files->mixed_content_detected) {
    $result_html .= "<tr><td>";
    $result_html .= $this->autoreplace_insecure_links ? $this->img_success :$this->img_warning;
    $result_html .= "</td><td>".__('Mixed content detected ','really-simple-ssl');
    $result_html .= $this->autoreplace_insecure_links ? __("but that's ok, because the mixed content fixer is active.","really-simple-ssl") : __("but the mixed content fix is not active.","really-simple-ssl");
    $result_html .= "</td>";
    $result_html .= "<td><a href='?page=rlrsssl_really_simple_ssl&tab=settings'>".__("Manage settings","really-simple-ssl")."</a></td></td></tr>";
    //next row
    $result_html .= "<tr><td></td>";//with extra column for warning images
    $result_html .= "<td>".__('In the tab "detected mixed content" you can find a list of items with mixed content.','really-simple-ssl')."</td>";
    $result_html .= "<td></td></tr>";
  } else {
    $result_html .= "<tr>";
    $result_html .= "<td>".$this->img_success."</td>";
    $result_html .= "<td>".__("No mixed content was detected. You could try to run your site without using the auto replace of insecure links, but check carefully. ","really-simple-ssl")."</td>";
    $result_html .= "<td><a href='?page=rlrsssl_really_simple_ssl&tab=settings'>".__("Manage settings","really-simple-ssl")."</a></td></tr>";
  }
  */

  if ($files->mixed_content_detected) {
    $list_html .= "<tr><td colspan='2'><h2>".__('List of detected items with mixed content','really-simple-ssl')."</h2></td></tr>";
    $list_html .= "<tr><td colspan='2'>";
    $list_html .= __('These files contain references to other websites that will get blocked on a https website.','really-simple-ssl');
    $list_html .= __('Please note that links might be found that are not used on back or front-end. This is just a list of links that give an error when loaded over https.','really-simple-ssl');
    $list_html .= "</td></tr>";
    $list_html .= "<tr><td></td><td id='scan-results'>";
    $list_html .= "<table class='wp-list-table widefat fixed striped pages'>";


    foreach ($files->files_with_blocked_resources as $fName => $file) {
      $list_html .= "<tr><td>";
      if (strpos($file, "themes")!==false) {
        $list_html .= "Theme file";
        $file_type = "themes";
      } else {
        $list_html .= "Plugin file";
        $file_type = "plugins";
      }
      $list_html .= ": wp-content/".$file_type."/".$files->get_path_to($file_type,$file);
      foreach($files->blocked_urls[$file] as $blocked_url) {
        $list_html .= "<br> &nbsp;-".__('blocked url','really-simple-ssl').": ".$blocked_url;
      }
      $list_html .= "</td></tr>";
    }

    $list_html .= "</table>";
    $list_html .= "<br>".__('CSS and JS files with http links. Change every http:// to //','really-simple-ssl');
    $list_html .= "<table class='wp-list-table widefat fixed striped pages'>";

    foreach ($files->files_with_http as $fName => $file) {
      $list_html .= "<tr><td>";
      if (strpos($file, "themes")!==false) {
        $list_html .= "Theme file";
        $lookup = "themes";
      } else {
        $list_html .= "Plugin file";
        $lookup = "plugins";
      }
      $list_html .= ": wp-content/themes/".$files->get_path_to($lookup,$file)."</td></tr>";
    }



    $list_html .= "</table>";

    if ($files->mixed_content_detected) {
      parse_str($_SERVER['QUERY_STRING'], $params);
          $list_html .= "<br>";
          $list_html .= "<button id='rlrsssl_scan' class='button button-primary' onclick='document.location.reload();'>";
          $list_html .= __("Scan again","really-simple-ssl");
          $list_html .= "</button>";
    }

    $list_html .= "</td></tr>";
  }

  //now, create json object

    $obj = new stdClass();
    $obj = array(
      'htmlresult' => $result_html,
      'htmllist'=> $list_html
    );

    echo json_encode($obj);
    wp_die(); // this is required to terminate immediately and return a proper response
  }
}
