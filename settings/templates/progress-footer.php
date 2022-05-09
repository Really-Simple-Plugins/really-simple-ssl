<?php defined('ABSPATH') or die();

$go_pro = '<a href="'.RSSSL()->really_simple_ssl->pro_url.'" target="_blank" class="button button-default upsell">'.__("Go PRO!", "really-simple-ssl")."</a>";
$activate_btn = "";
if ( 
     !RSSSL()->really_simple_ssl->ssl_enabled && 
     ( RSSSL()->really_simple_ssl->site_has_ssl || ( defined( 'RSSSL_FORCE_ACTIVATE' ) && RSSSL_FORCE_ACTIVATE ) ) 
) {
	$activate_btn = '<form action="" method="post" ><input type="submit" class="button button-primary" value="' . __( "Activate SSL", "really-simple-ssl" ) . '" id="rsssl_do_activate_ssl" name="rsssl_do_activate_ssl"></form>';
}

$items = [
	[
        'class' => RSSSL()->really_simple_ssl->ssl_enabled ? "rsssl-dot-success" :"rsssl-dot-error",
	    'text' => RSSSL()->really_simple_ssl->ssl_enabled ? __("SSL Activated", "really-simple-ssl") :__("SSL Not activated", "really-simple-ssl"),
	],
	[
		'class' => RSSSL()->really_simple_ssl->has_301_redirect() ? "rsssl-dot-success" :"rsssl-dot-error",
		'text' => __("301 Redirect", "really-simple-ssl"),
	],
];

?>
<div id="rsssl-progress-footer">
    <span class="rsssl-footer-item footer-left">
        <?php echo apply_filters("rsssl_progress_footer_left", '').$activate_btn.apply_filters("rsssl_progress_footer_right", $go_pro )?>
    </span>
	<?php
	foreach ($items as $item) { ?>
		<span class="rsssl-footer-item footer-right">
		    <span class="rsssl-grid-footer rsssl-dot <?php echo $item['class']?>"></span>
            <?php echo $item['text']?>
		</span>
	<?php }  ?>
</div>
