Do action not working
<?php
//ob_start();
//do_action('rsssl_activation_notice');
//$content = ob_get_clean();
ob_start();
do_action('rsssl_activation_notice_inner');
$content = ob_get_clean();

ob_start();
do_action('rsssl_activation_notice_footer');
$footer = ob_get_clean();
echo $content . $footer;