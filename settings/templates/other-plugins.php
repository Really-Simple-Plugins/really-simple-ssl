<?php defined('ABSPATH') or die("you do not have access to this page!");
$plugins = array(
	'BURST' => array(
		'constant_free' => 'burst_version',
		'constant_premium' => 'burst_version',
		'website' => 'https://burst-statistics.com/?src=rsssl-plugin',
		'search' => 'burst+statistics+really+simple+plugins+self-hosted',
		'url' => 'https://wordpress.org/plugins/burst-statistics/',
		'title' => 'Burst Statistics - '. __("Self-hosted, Privacy-Friendly Analytics for WordPress.", "really-simple-ssl"),
	),
    'COMPLIANZ' => array(
        'constant_free' => 'cmplz_plugin',
        'constant_premium' => 'cmplz_premium',
        'url' => 'https://wordpress.org/plugins/complianz-gdpr/',
        'website' => 'https://complianz.io/pricing?src=rsssl-plugin',
        'search' => 'complianz',
        'title' => 'Complianz - ' . __("The Privacy Suite for WordPress, consent as it should be", "really-simple-ssl" ),
    ),
	'COMPLIANZTC' => array(
		'constant_free' => 'cmplz_tc_version',
		'constant_premium' => 'cmplz_tc_version',
		'url' => 'https://wordpress.org/plugins/complianz-terms-conditions/',
		'website' => 'https://complianz.io?src=rsssl-plugin',
		'search' => 'complianz+terms+conditions+stand-alone',
		'title' => 'Complianz - '. __("Terms & Conditions", "really-simple-ssl"),

	),
);
?>
<div class="rsssl-other-plugins-container">
	<?php foreach ($plugins as $id => $plugin) {
		$prefix = strtolower($id);
		?>
        <div class="rsssl-other-plugins-element rsssl-<?php echo $prefix?>">
            <a href="<?php echo esc_url_raw($plugin['url'])?>" target="_blank" title="<?php echo esc_html($plugin['title'])?>">
                <div class="rsssl-bullet"></div>
                <div class="rsssl-other-plugins-content"><?php echo esc_html($plugin['title'])?></div>
            </a>
            <div class="rsssl-other-plugin-status">
				<?php echo RSSSL()->really_simple_ssl->get_status_link($plugin)?>
            </div>
        </div>
	<?php }?>
</div>
