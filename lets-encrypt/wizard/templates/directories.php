<?php
defined( 'ABSPATH' ) or die(  );
rsssl_progress_add('directories');

?>
<div class="rsssl-section">




    <div class="rsssl-hidden rsssl-check_key_directory rsssl-show-on-error">
        <h3>
			<?php _e("Create a key directory", "really-simple-ssl")
			      . RSSSL()->rsssl_help->get_help_tip(__("The key directory is needed to store the generated keys.","really-simple-ssl").' '.__("By placing it outside the root folder, it is not accessible over the internet.", "really-simple-ssl") ); ?>
        </h3>
        <p>
			<?php _e("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Create a folder called “ssl”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Inside the folder called “ssl” create a new folder called “keys”, with 644 writing permissions.', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Click the refresh button.', 'really-simple-ssl'); ?>
            </li>
        </ul>
    </div>

    <div class="rsssl-hidden rsssl-check_certs_directory rsssl-show-on-error">
        <h2>
			<?php _e("Create a certs directory", "really-simple-ssl")
			      . RSSSL()->rsssl_help->get_help_tip(__("The certificate will get stored in this directory.", "really-simple-ssl").' '.__("By placing it outside the root folder, it is not accessible over the internet.", "really-simple-ssl") ); ?>
        </h2>
        <p>
			<?php _e("Navigate in FTP or File Manager to one level above the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Create a folder called “ssl”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Inside the folder called “ssl” create a new folder called “certs”, with 644 writing permissions.', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Click the refresh button.', 'really-simple-ssl'); ?>
            </li>
        </ul>
    </div>
</div>