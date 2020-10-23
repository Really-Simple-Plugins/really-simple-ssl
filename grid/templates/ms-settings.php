<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<div class="rsssl-settings">
	<?php
	settings_fields('rsssl_network_options');
	do_settings_sections("really-simple-ssl");

	?>
</div>