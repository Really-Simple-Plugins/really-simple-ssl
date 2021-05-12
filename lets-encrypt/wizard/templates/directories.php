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

    $directories_without_permissions = RSSSL_LE()->letsencrypt_handler->directories_without_writing_permissions();
    $has_missing_permissions = count($directories_without_permissions)>0;
    if ( $has_missing_permissions ){ ?>
        <p>
		    <?php _e("One or more folders which require writing permissions do not have writing permissions:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <?php foreach ($directories_without_permissions as $directories_without_permission) {?>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
			    <?php _e('The following directory does not have writing permissions. Set permissions to 644 to enable SSL generation.', 'really-simple-ssl'); ?>
                <?php echo $directories_without_permission?>
            </li>
            <?php } ?>
        </ul>
    <?php }

	if ( !$has_missing_permissions && RSSSL_LE()->letsencrypt_handler->challenge_directory && RSSSL_LE()->letsencrypt_handler->key_directory && RSSSL_LE()->letsencrypt_handler->certs_directory ) {
		RSSSL_LE()->letsencrypt_handler->progress_add('directories');
		?>
        <p>
		    <?php _e("The necessary folders were successfully created, and have the correct permissions.", "really-simple-ssl"); ?>
        </p>
    <?php } else {
		RSSSL_LE()->letsencrypt_handler->progress_remove('directories');
    } ?>

</div>
