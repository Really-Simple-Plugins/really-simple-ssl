<?php defined( 'ABSPATH' ) or die(); ?>
    <div class="rsssl-section">
        <h2>
            <?php _e("FTP or Hosting Credentials", "really-simple-ssl")
            . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
        <p>
            <?php _e("In the next step you will need to create folders and a add file to the root of your website.  This can be done with FTP or with your hostingʼs file manager.  Please login to speed up the process", "really-simple-ssl"); ?>
        </p>
        <h2>
            <?php _e("Instructions for specific environments", "really-simple-ssl")
            . RSSSL()->rsssl_help->get_help_tip(__("Placeholder", "really-simple-ssl") ); ?>
        </h2>
        <p>
            <?php _e("After accessing your root folder, you will need to install the certificate. Please keep the instruction manual for your server configuration open during this process.", "really-simple-ssl"); ?>
        </p>
        <ul>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                <a href="" target="_blank"><?php _e("Artikel 1", "really-simple-ssl");?></a>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                <a href="" target="_blank"><?php _e("Artikel 2", "really-simple-ssl");?></a>
            </li>
            <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
                <a href="" target="_blank"><?php _e("Artikel 3", "really-simple-ssl");?></a>
            </li>
        </ul>
        <h2>
            <?php _e("Video tutorials", "really-simple-ssl"); ?>
        </h2>
        <p>
            <?php echo sprintf(__('Would you rather have a video walkthrough to help your during this process.  Please check out our video playlist %shere%s.', 'really-simple-ssl'), '<a target="_blank" href="https://really-simple-ssl.com/knowledge-base/htaccess-wp-config-files-not-writable/">', '</a>'); ?>
        </p>
    </div>
