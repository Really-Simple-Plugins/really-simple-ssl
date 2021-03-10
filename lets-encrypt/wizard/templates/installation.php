<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

?>
<div class="rsssl-section">
    <p>
        <?php _e("We will now install your SSL Certificate in your hosting environment. You will now need the specific instructions for your hosting environment", "really-simple-ssl"); ?>
    </p>
    <h2>
        <?php _e("FTP or Hosting Credentials", "really-simple-ssl")
        . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <p>
        <?php _e("In the next step you will need to create folders and a add file to the root of your website.  This can be done with FTP or with your hostingʼs file manager.  Please login to speed up the process", "really-simple-ssl"); ?>
    </p>
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
</div>
