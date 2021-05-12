<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
$download_url = rsssl_le_url.'download.php?token='.wp_create_nonce('rsssl_download_cert');
?>
<div class="rsssl-section">
    <div class="rsssl-template-intro">
        <p>
            <?php
            _e("We will now install your SSL Certificate in your hosting environment. You will now need the specific instructions for your hosting environment", "really-simple-ssl"); ?>
        </p>
    </div>

	<div class="rsssl-success rsssl-hidden">
        <p>
			<?php _e("The certificate was installed successfully.", "really-simple-ssl"); ?>
        </p>
    </div>

    <div class="rsssl-manual">
        <h2>
            <?php _e("Certifcate (CRT)", "really-simple-ssl")
            . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
        <p>
            <a href="<?php echo $download_url?>&type=certificate" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
        </p>
        <h2>
            <?php _e("Private Key (KEY)", "really-simple-ssl")
            . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
        <p>
            <a href="<?php echo $download_url?>&type=private_key" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
        </p>
        <h2>
            <?php _e("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl")
            . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
        <p>
            <a href="<?php echo $download_url?>&type=intermediate" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
        </p>
    </div>
</div>
