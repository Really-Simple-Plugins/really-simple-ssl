<?php
defined( 'ABSPATH' ) or die(  );
rsssl_progress_add('directories');

?>
<div class="rsssl-section">
    <div class="rsssl-hidden rsssl-check_challenge_directory rsssl-show-on-error">
        <h2>
		    <?php _e("Create a challenge directory", "really-simple-ssl")
		          . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
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
    </div><div class="rsssl-hidden rsssl-check_key_directory rsssl-show-on-error">

        <h2>
			<?php _e("Create a key directory", "really-simple-ssl")
			      . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
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

    </div><div class="rsssl-hidden rsssl-check_certs_directory rsssl-show-on-error">
        <h2>
			<?php _e("Create a certs directory", "really-simple-ssl")
			      . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
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
    </div>

</div>
