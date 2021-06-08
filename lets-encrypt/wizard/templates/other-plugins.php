<?php defined('ABSPATH') or die(); ?>

<?php
$plugins = array(

	'RSSSL' => array(
		'constant_free' => 'rsssl_version',
		'constant_premium' => 'rsssl_pro_version',
		'website' => 'https://ziprecipes.net/premium/',
		'search' => 'really-simple-ssl%20rogier%20lankhorst&tab=search',
		'url' => 'https://wordpress.org/plugins/really-simple-ssl/',
		'title' => 'Really Simple SSL - '. __("Easily migrate your website to SSL.", 'really-simple-ssl'),
	),

	'COMPLIANZ' => array(
		'constant_free' => 'rsssl_plugin',
		'constant_premium' => 'rsssl_premium',
		'website' => 'https://complianz.io/pricing',
		'url' => 'https://wordpress.org/plugins/complianz-gdpr/',
		'search' => 'complianz+really+simple+cookies+rogierlankhorst',
		'title' => 'Complianz GDPR/CCPA - '. __("The Privacy Suite for WordPress", 'really-simple-ssl'),
	),
	'ZIP' => array(
		'constant_free' => 'ZRDN_PLUGIN_BASENAME',
		'constant_premium' => 'ZRDN_PREMIUM',
		'website' => 'https://ziprecipes.net/premium/',
		'search' => 'zip+recipes+recipe+maker+really+simple+plugins+complianz',
		'url' => 'https://wordpress.org/plugins/zip-recipes/',
		'title' => 'Zip Recipes - '. __("Beautiful recipes optimized for Google.", 'really-simple-ssl'),
	),
);
?>

	<div class="rsssl-other-plugin-container">
		<div><!-- / menu column /--></div>
		<div class="rsssl-other-plugin-block">
			<div class="rsssl-other-plugin-header">
                <div class="rsssl-other-plugin-title"><?php _e("Our Plugins", "really-simple-ssl")?></div>
                <div class="rsssl-other-plugin-image"><img src="<?php echo rsssl_le_url?>/assets/images/really-simple-plugins.svg" ></div>
            </div>
            <div class="rsssl-other-plugin-content">
                <?php foreach ($plugins as $id => $plugin) {
                    $prefix = strtolower($id);
                    ?>

                    <div class="rsssl-other-plugin rsssl-<?php echo $prefix?>">
                        <div class="plugin-color">
                            <div class="rsssl-bullet"></div>
                        </div>
                        <div class="plugin-text">
                            <a href="<?php echo $plugin['url']?>" target="_blank"><?php echo $plugin['title']?></a>
                        </div>
                        <div class="plugin-status">
                            <span><?php echo RSSSL::$admin->get_status_link($plugin)?></span>
                        </div>
                    </div>
                <?php }?>
            </div>
		</div>
		<div><!-- / notices column /--></div>
	</div>
