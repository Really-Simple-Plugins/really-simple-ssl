<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

?>
<div class="rsssl-section">
    <h2>
		<?php _e("Create a folder to upload verification files", "really-simple-ssl")
		      . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <?php if ( RSSSL()->rsssl_letsencrypt->manual_directory_creation_needed() ){ ?>
        <p>
		    <?php _e("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
			    <?php _e('Create a folder called “.well-known”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
			    <?php _e('Inside the folder called “.well-known” create a new folder called “acme-challenge”', 'really-simple-ssl'); ?>
            </li>
        </ul>
    <?php } else {?>
        <p>
		    <?php _e("The necessary folders were successfully created automatically.", "really-simple-ssl"); ?>
        </p>
    <?php } ?>
    <h2>
        <?php _e("Upload the verification files", "really-simple-ssl")
        . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <p>
        <button class="button button-secondary"><?php _e("Download file", "really-simple-ssl")?></button>
        <button class="button button-primary"><?php _e("Verify", "really-simple-ssl")?></button>
    </p>
    <div class="rsssl-progress-bar">

    </div>
    <p>
        <?php _e("After accessing your root folder, you will need to install the certificate. Please keep the instruction manual for your server configuration open during this process.", "really-simple-ssl"); ?>
    </p>
</div>
