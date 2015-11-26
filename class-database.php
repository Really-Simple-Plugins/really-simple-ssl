<?php
defined('ABSPATH') or die("you do not have acces to this page!");

class rlrsssl_database {
  public
    $postsWithHTTP                 = Array(),
    $optionsWithHTTP               = Array(),
    $search_array                  = Array(),
    $results_limit                 = 25;

  public function __construct()
  {

  }

  public function build_query( $where = '' ){
      global $wpdb;
      $where.= ' AND (';

      $arr = $this->search_array;
      $i = 0;
      foreach ($arr as $needle) {
        $needle = addslashes($needle);
        $i++;
        $where.= sprintf(' %1$s.post_content ',$wpdb->posts);
        $where.= " LIKE '%{$needle}%'";
        if ($i<count($arr)) {
          $where.= " OR ";
        }
      }
      $where.=") ";
      return $where;
  }

    public function fix_insecure_post_links() {
        global $wpdb;
        /*
        $wpdb->query(
          $wpdb->prepare(
            "
            UPDATE $wpdb->posts
            SET Value = REPLACE(Value, '%1$s', '%2$s')
            WHERE ID <=4
            ",
                  $string1, $string2
                )
        );*/
      }

      public function scan($search_array) {
        $this->search_array = $search_array;

        add_filter('posts_where', array($this,'build_query'));
        $args = array(
            'post_type'   => get_post_types( '','names' ),
            'suppress_filters' => false
        );
        $the_query = new WP_Query($args);
        //limit results
        $count = 0;
        if ($the_query->have_posts() ) {
          while ( $the_query->have_posts() && $count<$this->results_limit) {
            $count++;
            $the_query->the_post();
            $this->postsWithHTTP[get_the_title()] = get_the_ID();
          }
        }
        remove_filter( 'posts_where', array($this,'build_query'));
        wp_reset_postdata();

        //scan options
        global $wpdb;
        $where = sprintf('Select * FROM %1$s WHERE (',$wpdb->options);
        $arr = $this->search_array;
        $i = 0;
        foreach ($arr as $needle) {
          $needle = addslashes($needle);
          $i++;
          $where.= sprintf(' %1$s.option_value ',$wpdb->options);
          $where.= " LIKE '%{$needle}%'";
          if ($i<count($arr)) {
            $where.= " OR ";
          }
        }
        //ignore siteurl and home, because we take care of these
        $where.= sprintf(') AND NOT (%1$s.option_name = "siteurl" OR %1$s.option_name = "home" ',$wpdb->options);
        $where.= " OR ".$wpdb->options.".option_name LIKE '_transient%' OR ".$wpdb->options.".option_name LIKE '_site_transient%')";
        $results = $wpdb->get_results($where);
        //limit to 25
        $count=0;
        foreach ($results as $result) {
          if ($count<$this->results_limit ) {
            array_push($this->optionsWithHTTP,$result->option_name);
            $count++;
          }
        }

      }
}
