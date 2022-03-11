<?php
	$current = isset($_GET['tab']) ? sanitize_title($_GET['tab']) : 'dashboard';
	$tabs = apply_filters("rsssl_grid_tabs",
		$tabs = [
			'dashboard' => __("Dashboard", "really-simple-ssl"),
			'settings' => __("Settings", "really-simple-ssl"),
		]
	);

	//allow the license tab to show up for older version, to allow for upgrading
	$legacy_tabs = apply_filters("rsssl_tabs", array());
	if (isset($legacy_tabs['license'])) $tabs['license']= $legacy_tabs['license'];

	$high_contrast = RSSSL()->really_simple_ssl->high_contrast ? 'rsssl-high-contrast' : ''; ?>
<div class="rsssl-header nav-tab-wrapper <?php echo $high_contrast ?>">
	<div class="rsssl-logo-container">
		<div id="rsssl-logo"><img src="<?php echo rsssl_url?>/assets/really-simple-ssl-logo.png" alt="review-logo"></div>
	</div>
	<?php

    foreach ( $tabs as $tab => $name ) {
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=rlrsssl_really_simple_ssl&tab=$tab'>$name</a>";
    }

	?>
	<div class="header-links">
		<div class="documentation">
			<a href="https://really-simple-ssl.com/knowledge-base" class="<?php if (defined('rsssl_pro_version')) echo "button button-primary"?>" target="_blank"><?php _e("Documentation", "really-simple-ssl");?></a>
		</div>
		<div class="header-upsell">
			<?php if (defined('rsssl_pro_version')) { ?>
			<?php } else { ?>
				<div class="documentation">
					<a href="https://wordpress.org/support/plugin/really-simple-ssl/" class="button button-primary" target="_blank"><?php _e("Support", "really-simple-ssl") ?></a>
				</div>
			<?php } ?>
		</div>
	</div>
</div>