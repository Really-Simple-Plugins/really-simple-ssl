<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

?>
<div class="rsssl-section">
    <h2></h2>
    <p>
		<?php _e("Detected status of your setup.", "really-simple-ssl"); ?>
    </p>

    <?php
    /*
     *
     */
    ?>
    <ul>
        <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
            <?php _e("Hosting environment:", "really-simple-ssl");?>
            <?php echo rsssl_hosting_environment(true)?>
        </li>
	    <?php if (strpos(site_url(), 'localhost') !==false ) { ?>
        <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
            <?php _e("Localhost:", "really-simple-ssl");?>
            <?php _e('It is not possible to generate a certificate for localhost', 'really-simple-ssl'); ?>
        </li>
        <?php }?>
        <li class="rsssl-tooltip-icon dashicons-before rsssl-icon arrow-right-alt2 dashicons-arrow-right-alt2">
            <?php _e("SSL status:", "really-simple-ssl");?>
            <?php if (RSSSL()->really_simple_ssl->site_has_ssl) {
                _e("Successfully detected SSL.", "really-simple-ssl");
            } else {
	            _e("No SSL detected.", "really-simple-ssl");

            }?>
        </li>
    </ul>
</div>
