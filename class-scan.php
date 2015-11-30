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
        $('#scan-result tr:last').after('<tr id="loader_result"><td></td><td><div class="loader"><?php _e("Scanning...", "really-simple-ssl");?></div></td></tr>');
        $('#scan-list tr:last').after('<tr id="loader_list"><td></td><td><div class="loader"><?php _e("Scanning...", "really-simple-ssl");?></div></td></tr>');

          var data = {
          'action': 'scan',
          'security': '<?php echo $ajax_nonce; ?>',
        };
        $.post(ajaxurl, data, function(response) {
            var obj;
            if (!response) {
              htmlresult="<tr><td></td><td>Scan not completed, please scan again</td></tr>";
              htmllist = "<tr><td></td><td>Scan not completed, please scan again</td></tr>";
            }
            else {
             obj = jQuery.parseJSON( response );
             $('#loader_result').replaceWith(obj['htmlresult']);
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
  $database = new rlrsssl_database;
  $database->scan($this->search_array);

  if (count($database->postsWithHTTP)>0 || count($files->filesWithHTTP)>0 || count($database->optionsWithHTTP)>0) {
    $this->mixed_content_detected = TRUE;
  }

  if ($this->mixed_content_detected) {
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

  if ($this->mixed_content_detected) {
    $search_strings = "<br>";
    foreach($this->search_array as $search_string) {
      $search_strings .= "&nbsp;&nbsp;-&nbsp;".$search_string."<br>";
    }
    $search_strings = sprintf(__('The scan searched for the following insecure links: %s','really-simple-ssl'),$search_strings);

    $list_html .= "<tr><td colspan='2'><h2>".__('List of detected items with mixed content','really-simple-ssl')."</h2></td></tr>";
    $list_html .= "<tr><td colspan='2'>".__('Because really simple ssl includes a mixed content fixer you do not have to worry about this list, but if you want to disable the mixed content fixer, you can find a list of possible issues here.','really-simple-ssl')."</td></tr>";
    $list_html .= "<tr><td></td><td><p>".$search_strings."</p></td></tr>";
    $list_html .= "<tr><td></td><td id='scan-results'>";
    $list_html .= "<table class='wp-list-table widefat fixed striped pages'>";

    foreach ($database->postsWithHTTP as $name => $id) {
      $list_html .= "<tr><td>".$name."&nbsp;|&nbsp;<a href='post.php?post=".$id."&action=edit'>";
      $list_html .= __('edit','really-simple-ssl');
      $list_html .= "</a></td></tr>";
    }

    foreach ($files->filesWithHTTP as $fName => $file) {
      $list_html .= "<tr><td>Theme file: ".$files->get_path_to_themes($file)."</td></tr>";
    }

    foreach ($database->optionsWithHTTP as $option) {
      $list_html .= "<tr><td>Option: ".$option."</td></tr>";
    }

    $list_html .= "</table>";

    if ($this->mixed_content_detected) {
      parse_str($_SERVER['QUERY_STRING'], $params);
          $list_html .= "<br>";
          $list_html .= "<button id='rlrsssl_scan' class='button button-primary' onclick='document.location.reload();'>";
          $list_html .= __("Scan again","really-simple-ssl");
          $list_html .= "</button>";

          /*
          <button class="button button-primary" onclick="document.location.href='<?php printf('%1$s', '?'.http_build_query(array_merge($params, array('rlrsssl_fixposts'=>'1'))));?>'">
            <?php _e("Fix posts","really-simple-ssl"); ?>
          </button>
          */
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
