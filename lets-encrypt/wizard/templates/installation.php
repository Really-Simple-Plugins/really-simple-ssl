<?php
defined( 'ABSPATH' ) or die();
?>
<div class="rsssl-section">
    <?php if ( rsssl_generated_by_rsssl() ) {
        $download_url = rsssl_le_url.'download.php?token='.wp_create_nonce('rsssl_download_cert');
        $key_file = get_option('rsssl_private_key_path');
        $cert_file = get_option('rsssl_certificate_path');
        $cabundle_file = get_option('rsssl_intermediate_path');
        $key_content = file_exists($key_file) ? file_get_contents($key_file) : 'no data found';
        $certificate_content = file_exists($cert_file) ? file_get_contents($cert_file) : 'no data found';
        $ca_bundle_content = file_exists($cabundle_file) ? file_get_contents($cabundle_file) : 'no data found';
        ?>
        <div class="rsssl-hidden rsssl-copied-feedback"><?php _e("copied!","really-simple-ssl")?></div>
        <div class="rsssl-manual rsssl-warning rsssl-hidden">
            <div>
                <h2>
			        <?php _e("Next step", "really-simple-ssl"); ?>
                </h2>
            </div>
            <div class="rsssl-template-intro">
                <p><?php _e("Install your certificate.", "really-simple-ssl")?></p>
            </div>
            <h2>
			    <?php _e("Certificate (CRT)", "really-simple-ssl")
			          . RSSSL()->rsssl_help->get_help_tip(__("This is the certificate, which you need to install in your hosting dashboard.", "really-simple-ssl") ); ?>
            </h2>

            <div class="rsssl-certificate-data rsssl-certificate" id="rsssl-certificate"><?php echo $certificate_content ?></div>
            <a href="<?php echo $download_url?>&type=certificate" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button type="button"  class="button button-primary rsssl-copy-content" data-item="certificate"><?php _e("Copy content", "really-simple-ssl")?></button>

            <h2>
			    <?php _e("Private Key (KEY)", "really-simple-ssl")
			          . RSSSL()->rsssl_help->get_help_tip(__("The private key can be uploaded or pasted in the appropriate field on your hosting dashboard.", "really-simple-ssl") ); ?>
            </h2>
            <div class="rsssl-certificate-data rsssl-key" id="rsssl-key"><?php echo $key_content ?></div>
            <a href="<?php echo $download_url?>&type=private_key" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button type="button" class="button button-primary rsssl-copy-content" data-item="key"><?php _e("Copy content", "really-simple-ssl")?></button>
            <h2>
			    <?php _e("Certificate Authority Bundle (CABUNDLE)", "really-simple-ssl")
			          . RSSSL()->rsssl_help->get_help_tip(__("The CA Bundle will sometimes be automatically detected. If not, you can use this file.", "really-simple-ssl") ); ?>
            </h2>
            <div class="rsssl-certificate-data rsssl-cabundle" id="rsssl-cabundle"><?php echo  $ca_bundle_content;?></div>
            <a href="<?php echo $download_url?>&type=intermediate" class="button button-secondary"><?php _e("Download", "really-simple-ssl")?></a>
            <button type="button" class="button button-primary rsssl-copy-content" data-item="cabundle"><?php _e("Copy content", "really-simple-ssl")?></button>
        </div>
    <?php } ?>
</div>
