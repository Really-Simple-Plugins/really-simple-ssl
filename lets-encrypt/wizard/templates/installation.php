<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
$status = RSSSL_LE()->letsencrypt_handler->copy_certificates();

?>
<div class="rsssl-section">
    <div class="rsssl-template-intro">
        <p>
            <?php
            echo $status;
            _e("We will now install your SSL Certificate in your hosting environment. You will now need the specific instructions for your hosting environment", "really-simple-ssl"); ?>
        </p>
    </div>
	<?php if ( $status==='success' ){ ?>
        <p>
			<?php _e("The certificate was copied over automatically.", "really-simple-ssl"); ?>
        </p>
	<?php } elseif ( $status==='not-ready' ){ ?>
        <p>
			<?php _e("The required steps for this step were not completed.", "really-simple-ssl"); ?>
        </p>
	<?php } else if ( $status==='not-copied' ){  ?>
        <p>
			<?php _e("To install the certificate, please follow these steps:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Create a folder called “certs”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Inside the folder called “.certs” create a new file called “certificate.crt”', 'really-simple-ssl'); ?>
            </li>
        </ul>
	<?php } else if ( $status==='copied' ){  ?>
        <p>
			<?php printf(__("The certificate, private key and intermediate key were copied to the %s folder successfully!", "really-simple-ssl"), RSSSL_LE()->letsencrypt_handler->certs_directory); ?>
        </p>
	<?php } ?>

    <?php if ($status!=='not-ready') {?>
    <h2>
        <?php _e("Certifcate (CRT)", "really-simple-ssl")
        . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <p>
        <button class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></button>
        <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
    </p>
    <h2>
        <?php _e("Private Key (KEY)", "really-simple-ssl")
        . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <p>
        <button class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></button>
        <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
    </p>
    <h2>
        <?php _e("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl")
        . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <p>
        <button class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></button>
        <button class="button button-primary"><?php _e("View content", "really-simple-ssl")?></button>
    </p>
    <?php }?>
</div>
