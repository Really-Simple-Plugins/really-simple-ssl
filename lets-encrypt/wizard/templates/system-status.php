<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

?>
<div class="rsssl-section">
    <h2></h2>
    <p>
		<?php _e("Detected status of your setup.", "really-simple-ssl"); ?>
    </p>
    <?php 		delete_transient('rsssl_account_checked');
    RSSSL_LE()->letsencrypt_handler->progress_add('system-status');?>
    <ul>
        <?php if (function_exists('wp_get_direct_update_https_url') && !empty(wp_get_direct_update_https_url())) {?>
        <li class="rsssl-success">
            <?php _e("We received a signal from your hosting company to install SSL.", "really-simple-ssl");?>&nbsp;
	        <?php printf(__("Please follow this %slink%s", "really-simple-ssl"), '<a target="_blank" href="'.wp_get_direct_update_https_url().'">', '</a>');?>
        </li>
        <?php } ?>
        <li>
            <?php _e("Hosting environment:", "really-simple-ssl");?>
            <?php echo rsssl_hosting_environment(true)?>
        </li>
	    <?php if (strpos(site_url(), 'localhost') !==false ) { ?>
		    <?php //RSSSL_LE()->letsencrypt_handler->progress_remove('system-status');?>

            <li class="rsssl-error">
            <?php _e("Localhost:", "really-simple-ssl");?>
            <?php _e('It is not possible to generate a certificate for localhost', 'really-simple-ssl'); ?>
        </li>
        <?php }?>
        <?php if (RSSSL()->rsssl_certificate->is_valid()) { ?>
        <li class="rsssl-success">
            <?php _e("SSL status:", "really-simple-ssl");?>
            <?php _e("Successfully detected SSL.", "really-simple-ssl"); ?>
        </li>
        <?php } else {?>
        <li class="rsssl-error">
            <?php _e("SSL status:", "really-simple-ssl");?>
            <?php _e("No SSL detected.", "really-simple-ssl");?>
        </li>
        <?php } ?>
    </ul>
</div>
