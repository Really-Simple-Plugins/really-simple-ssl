<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>

<a href="<?php echo trailingslashit(rsssl_url).'system-status.php' ?>" class="button button-rsssl-secondary rsssl-wide-button"><?php _e("Download system status", "really-simple-ssl")?></a>
<div id="rsssl-feedback"></div>
<div class="rsssl-system-status-footer-info">
	<span class="system-status-info"><?php echo "<b>" . __("Server type:", "really-simple-ssl") . "</b> " . RSSSL()->rsssl_server->get_server(); ?></span>
	<span class="system-status-info"><?php echo "<b>" . __("SSL type:", "really-simple-ssl") . "</b> " . $this->ssl_type; ?></span>
</div>
