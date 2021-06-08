<?php
defined( 'ABSPATH' ) or die();

ob_start();
do_action('rsssl_activation_notice_inner');
$content = ob_get_clean();

ob_start();
do_action('rsssl_activation_notice_footer');
$footer = ob_get_clean();

$class = apply_filters("rsssl_activation_notice_classes", "updated activate-ssl rsssl-pro-dismiss-notice");
$title = __("Almost ready to migrate to SSL!", "really-simple-ssl");
echo RSSSL_LE()->really_simple_ssl->notice_html( $class, $title, $content, $footer);