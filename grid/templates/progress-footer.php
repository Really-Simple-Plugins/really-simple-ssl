<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>

<?php
if (RSSSL()->really_simple_ssl->ssl_enabled) {
	$ssl_enabled = "rsssl-dot-success";
	$ssl_text = __("SSL Activated", "really-simple-ssl");
} else {
	$ssl_enabled = "rsssl-dot-error";
	$ssl_text = __("SSL Not activated", "really-simple-ssl");
}

if (RSSSL()->really_simple_ssl->has_301_redirect()) {
	$redirect_301 = "rsssl-dot-success";
} else {
	$redirect_301 = "rsssl-dot-error";
}


$button_text = __("Go PRO!", "really-simple-ssl");
$button_link = RSSSL()->really_simple_ssl->pro_url;
$go_pro = "<a href='$button_link' target='_blank' class='button button-default upsell'>$button_text</a>";
$activate_btn = "";
if (!RSSSL()->really_simple_ssl->ssl_enabled) {
	if ( RSSSL()->really_simple_ssl->site_has_ssl || ( defined( 'RSSSL_FORCE_ACTIVATE' ) && RSSSL_FORCE_ACTIVATE ) ) {
		$button_text = __( "Activate SSL", "really-simple-ssl" );
		$activate_btn = '<form action="" method="post" ><input type="submit" class="button button-primary" value="' . $button_text . '" id="rsssl_do_activate_ssl" name="rsssl_do_activate_ssl"></form>';
	}
}

$items = array(
		1 => array(
			'class' => 'footer-right',
			'dot_class' => $ssl_enabled,
			'text' => $ssl_text,
		),
		2 => array(
			'class' => 'footer-right',
			'dot_class' => $redirect_301,
			'text' => __("301 Redirect", "really-simple-ssl"),
		),
	);

?>
<div id="rsssl-progress-footer">
    <span class="rsssl-footer-item footer-left">
        <?php echo apply_filters("rsssl_progress_footer_left", '').$activate_btn.apply_filters("rsssl_progress_footer_right", $go_pro )?>
    </span>
	<?php
	foreach ($items as $item) { ?>
		<span class="rsssl-footer-item <?php echo $item['class']?>">
		    <span class="rsssl-grid-footer dot <?php echo $item['dot_class']?>"></span>
            <?php echo $item['text']?>
		</span>

	<?php }  ?>
</div>
