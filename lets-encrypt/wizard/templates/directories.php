<?php
defined( 'ABSPATH' ) or die(  );
rsssl_progress_add('directories');

?>
<div class="rsssl-section">
    <div class="rsssl-hidden rsssl-general rsssl-show-on-error">
        <h2>
			<?php _e("Next step", "really-simple-ssl"); ?>
        </h2>
    </div>

    <div class="rsssl-hidden rsssl-check_challenge_directory rsssl-challenge_directory_reachable rsssl-show-on-warning rsssl-show-on-error">
        <p>
			<?php _e("If the challenge directory cannot be created, or is not reachable, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl"); ?>
        </p><br>
        <button class="button button-default" name="rsssl-switch-to-dns"><?php _e("Switch to DNS verification", "really-simple-ssl"); ?></button>
    </div>
    <div class="rsssl-hidden rsssl-check_challenge_directory rsssl-show-on-error">
        <h2>
			<?php _e("Create a challenge directory", "really-simple-ssl")
			      . RSSSL()->rsssl_help->get_help_tip(__("The challenge directory is used to verify the domain ownership.", "really-simple-ssl") ); ?>
        </h2>
        <p>
			<?php _e("Navigate in FTP or File Manager to the root of your WordPress installation:", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Create a folder called “.well-known”', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Inside the folder called “.well-known” create a new folder called “acme-challenge”, with 644 writing permissions.', 'really-simple-ssl'); ?>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
				<?php _e('Click the refresh button.', 'really-simple-ssl'); ?>
            </li>
        </ul>
        <h2>
		    <?php _e("Or you can switch to DNS verification", "really-simple-ssl"); ?>
        </h2>
        <p><?php _e("If the challenge directory cannot be created, you can either remove the server limitation, or change to DNS verification.", "really-simple-ssl"); ?></p><br>
        <button class="button button-default" name="rsssl-switch-to-dns"><?php _e("Switch to DNS verification", "really-simple-ssl"); ?></button>

    </div><div class="rsssl-hidden rsssl-check_key_directory rsssl-show-on-error">
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
    </div><div class="rsssl-hidden rsssl-check_certs_directory rsssl-show-on-error">
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