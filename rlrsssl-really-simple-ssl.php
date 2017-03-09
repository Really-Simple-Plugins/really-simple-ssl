<?php
/**
 * Plugin Name: Really Simple SSL
 * Plugin URI: https://www.really-simple-ssl.com
 * Description: Lightweight plugin without any setup to make your site ssl proof
 * Version: 2.5.11
 * Text Domain: really-simple-ssl
 * Domain Path: /languages
 * Author: Rogier Lankhorst
 * Author URI: https://www.rogierlankhorst.com
 * License: GPL2
 */

/*  Copyright 2014  Rogier Lankhorst  (email : rogier@rogierlankhorst.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

  defined('ABSPATH') or die("you do not have acces to this page!");

  /* initializing */

  require_once(ABSPATH.'wp-admin/includes/plugin.php');
  $plugin_data = get_plugin_data( __FILE__ );
  define('rsssl_url', plugin_dir_url(__FILE__ ));
  define('rsssl_path', plugin_dir_path(__FILE__ ));
  define('rsssl_plugin', plugin_basename( __FILE__ ) );
  define('rsssl_version', $plugin_data['Version'] );

  require_once( dirname( __FILE__ ) .  '/class-front-end.php' );
  require_once( dirname( __FILE__ ) .  '/class-mixed-content-fixer.php' );

  $rsssl_front_end            = new rsssl_front_end;
  $rsssl_mixed_content_fixer  = new rsssl_mixed_content_fixer;

  //$rsssl_front_end->set_ssl_var();

  add_action("wp_loaded", array($rsssl_front_end, "force_ssl"),20);

  if (is_admin()) {
    require_once( dirname( __FILE__ ) .  '/class-admin.php' );
    require_once( dirname( __FILE__ ) .  '/class-cache.php' );
    require_once( dirname( __FILE__ ) .  '/class-server.php' );
    require_once( dirname( __FILE__ ) .  '/class-help.php' );
    //require_once( dirname( __FILE__ ) .  '/class-maintain-plugin-load-position.php' );
    //$rsssl_maintain_plugin_position     = new rsssl_maintain_plugin_position;

    $rsssl_cache                        = new rsssl_cache;
    $rsssl_server                       = new rsssl_server;
    $really_simple_ssl                  = new rsssl_admin;
    $rsssl_help                         = new rsssl_help;

    add_action("plugins_loaded", array($really_simple_ssl, "init"),10);
  }
