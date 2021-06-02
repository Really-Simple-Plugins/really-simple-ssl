<?php
defined( 'ABSPATH' ) or die();
do_action('rsssl_le_activation');

//non cached SSL check.
$response = RSSSL_LE()->letsencrypt_handler->certificate_status();
$certificate_is_valid = $response->status === 'error';
$already_enabled = RSSSL()->really_simple_ssl->ssl_enabled;
$already_done = $certificate_is_valid && $already_enabled;
if ($already_done) {?>
    <div class="rsssl-section">
        <h2>
			<?php _e("Your site is secured with a valid SSL certificate!", "really-simple-ssl") . RSSSL()->rsssl_help->get_help_tip(__("In some cases it takes a few minutes for the certificate to get detected. In that case, check back in a few minutes.", "really-simple-ssl") ); ?>
        </h2>
		<?php
		_e("If you just activated SSL, please check for: ", 'really-simple-ssl'); ?>
        <p>
        <ul>
            <li class="rsssl-warning"><?php _e('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl'); ?></li>
            <li class="rsssl-warning"><?php _e('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl'); ?></li>
            <li class="rsssl-success"><?php _e("SSL was already activated on your website!", "really-simple-ssl") ?></li>
        </ul>
        </p>
        <p><?php
			if (!defined('rsssl_pro_version')) {
			_e('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl');
			?>
            <a target="_blank"
               href="<?php
			   echo RSSSL()->really_simple_ssl->pro_url; ?>"><?php _e("Check out Really Simple SSL Pro", "really-simple-ssl");
				}
				?>
            </a>
        </p>

    </div>

<?php } else { ?>

<div class="rsssl-section">
	<h2>
		<?php _e("Almost ready to activate SSL!", "really-simple-ssl") . RSSSL()->rsssl_help->get_help_tip(__("In some cases it takes a few minutes for the certificate to get detected. In that case, check back in a few minutes.", "really-simple-ssl") ); ?>
	</h2>
    <?php
    _e("Before you migrate, please check for: ", 'really-simple-ssl'); ?>
    <p>
    <ul>
        <li class="rsssl-warning"><?php _e('Http references in your .css and .js files: change any http:// into https://', 'really-simple-ssl'); ?></li>
        <li class="rsssl-warning"><?php _e('Images, stylesheets or scripts from a domain without an SSL certificate: remove them or move to your own server.', 'really-simple-ssl'); ?></li><?php

		$backup_link = "https://really-simple-ssl.com/knowledge-base/backing-up-your-site/";
		$link_open = '<a target="_blank" href="'.$backup_link.'">';
		$link_close = '</a>';
		?>
        <li class="rsssl-warning"><?php printf(__("We strongly recommend to create a %sbackup%s of your site before activating SSL", 'really-simple-ssl'), $link_open, $link_close); ?> </li>
        <li class="rsssl-warning"><?php _e("You may need to login in again.", "really-simple-ssl") ?></li>
		<?php if ($certificate_is_valid) { ?>
            <li class="rsssl-success"><?php _e("An SSL certificate has been detected", "really-simple-ssl") ?></li>
		<?php } else { ?>
            <li class="rsssl-error"><?php _e("No SSL certificate has been detected yet. In some cases this takes a few minutes.", "really-simple-ssl") ?></li>
		<?php }?>
    </ul>
    </p>
    <p><?php
		if (!defined('rsssl_pro_version')) {
		_e('You can also let the automatic scan of the pro version handle this for you, and get premium support, increased security with HSTS and more!', 'really-simple-ssl');
		?>
        <a target="_blank"
           href="<?php
		   echo RSSSL()->really_simple_ssl->pro_url; ?>"><?php _e("Check out Really Simple SSL Pro", "really-simple-ssl");
			}
			?>
        </a>
    </p>

</div>
<?php } ?>