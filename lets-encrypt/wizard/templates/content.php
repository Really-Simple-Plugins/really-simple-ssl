<?php defined( 'ABSPATH' ) or die(  );?>
<div class="rsssl-section-content">
	<?php
    $hide = isset( $_POST['rsssl-save']) ? 'rsssl-settings-saved--fade-in': ''; ?>
    <div class="rsssl-settings-saved <?php echo $hide?>">
        <div class="rsssl-settings-saved__text_and_icon">
            <span><div class="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-success check"><svg width="18" height="18" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"></path></svg></div></span>
            <span><?php _e('Changes saved successfully', 'really-simple-ssl') ?> </span>
        </div>
    </div>
    <form action="{page_url}" method="POST">
		<input type="hidden" value="{step}" name="step">
		<input type="hidden" value="{section}" name="section">
		<?php wp_nonce_field( 'rsssl_save', 'rsssl_le_nonce' ); ?>
        <div class="rsssl-wizard-title rsssl-section-content-title-header">
			<h1>{title}</h1>
            <span>
                <a class="rsssl-reset" onclick="return confirm('<?php _e("This will clear all settings for Really Simple SSL Let\'s Encrypt, and will clear the order in the ssl/keys directory.","really-simple-ssl")?>');" href="<?php echo esc_url(add_query_arg(array("page"=>"rlrsssl_really_simple_ssl", 'tab'=>"letsencrypt", "reset-letsencrypt" => 1),admin_url("options-general.php") ) );?>"><?php _e("Reset Let's Encrypt","really-simple-ssl")?></a>
            </span>
		</div>
        <div class="rsssl-wizard-title rsssl-section-content-notifications-header">
			<h1><?php _e("Notifications", 'really-simple-ssl')?></h1>
		</div>
	    {intro}
        <!-- before do action -->
	    <?php do_action("rsssl_le_installation_step" ); ?>
        <!-- after do action -->

        {fields}
        <div class="rsssl-section-footer">
            {save_notice}
            <div class="rsssl-buttons-container">
                {previous_button}
                {save_button}
                {next_button}
            </div>
        </div>

    </form>
</div>

