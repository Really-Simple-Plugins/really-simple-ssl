<?php defined('ABSPATH') or die("you do not have access to this page!");
$plugins = array(
	'COMPLIANZTC' => array(
		'constant_free' => 'cmplz_tc_version',
		'constant_premium' => 'cmplz_tc_version',
		'website' => 'https://complianz.io?src=rsssl-plugin',
		'search' => 'complianz+terms+conditions+stand-alone',
	),
	'COMPLIANZ' => array(
		'constant_free' => 'cmplz_plugin',
		'constant_premium' => 'cmplz_premium',
		'website' => 'https://complianz.io/pricing/?src=rsssl-plugin',
		'search' => 'complianz+really+simple+cookies+rogierlankhorst',
	),
	'ZIP' => array(
		'constant_free' => 'ZRDN_PLUGIN_BASENAME',
		'constant_premium' => 'ZRDN_PREMIUM',
		'website' => 'https://ziprecipes.net/premium/?src=rsssl-plugin',
		'search' => 'zip+recipes+recipe+maker+really+simple+plugins+complianz',
	),
);
?>
<div>
	<div class="rsssl-upsell rsssl-cmplz">
		<div class="plugin-color">
			<div class="cmplz-blue rsssl-bullet"></div>
		</div>
		<div class="plugin-text">
			<a href="https://wordpress.org/plugins/complianz-gdpr/" target="_blank">Complianz – GDPR/CCPA Cookie Consent</a>
		</div>
		<div class="plugin-status">
			<?php echo RSSSL()->really_simple_ssl->get_status_link($plugins['COMPLIANZ'])?>
		</div>
	</div>
	<div class="rsssl-upsell rsssl-zip">
		<div class="plugin-color">
			<div class="zip-pink rsssl-bullet"></div>
		</div>
		<div class="plugin-text">
            <a href="https://wordpress.org/plugins/zip-recipes/" target="_blank">Zip Recipes - <?php _e("Beautiful recipes optimized for Google ", "really-simple-ssl")?></a>
        </div>
		<div class="plugin-status">
			<?php echo RSSSL()->really_simple_ssl->get_status_link($plugins['ZIP'])?>
		</div>
	</div>
    <div class="rsssl-upsell rsssl-cmplztc">
        <div class="plugin-color">
            <div class="cmplztc-black rsssl-bullet"></div>
        </div>
        <div class="plugin-text">
            <a href="https://wordpress.org/plugins/complianz-terms-conditions/" target="_blank">Complianz - Terms & Conditions</a>
        </div>
        <div class="plugin-status">
			<?php echo RSSSL()->really_simple_ssl->get_status_link($plugins['COMPLIANZTC'])?>
        </div>
    </div>
</div>