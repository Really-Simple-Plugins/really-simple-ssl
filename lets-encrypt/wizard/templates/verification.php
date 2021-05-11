<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

?>
<div class="rsssl-section">
    <h2>
		<?php _e("Create folders for the certificate and other files", "really-simple-ssl")
		      . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
    </h2>
    <?php if ( !RSSSL_LE()->letsencrypt_handler->challenge_directory ){ ?>
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
    <?php }

    if ( !RSSSL_LE()->letsencrypt_handler->key_directory ){ ?>
        <p>
            <?php _e("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                <?php _e('Create a folder called “ssl”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                <?php _e('Inside the folder called “ssl” create a new folder called “keys”', 'really-simple-ssl'); ?>
            </li>
        </ul>
	<?php }

    if ( !RSSSL_LE()->letsencrypt_handler->certs_directory ){ ?>
        <p>
		    <?php _e("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
			    <?php _e('Create a folder called “ssl”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
			    <?php _e('Inside the folder called “ssl” create a new folder called “certs”', 'really-simple-ssl'); ?>
            </li>
        </ul>
    <?php }

	if ( RSSSL_LE()->letsencrypt_handler->challenge_directory && RSSSL_LE()->letsencrypt_handler->key_directory && RSSSL_LE()->letsencrypt_handler->certs_directory ) { ?>
        <p>
		    <?php _e("The necessary folders were successfully created.", "really-simple-ssl"); ?>
        </p>
    <?php } ?>

</div>
