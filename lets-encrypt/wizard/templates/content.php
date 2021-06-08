<?php defined( 'ABSPATH' ) or die(  );?>
<div class="rsssl-section-content">
    <form action="{page_url}" method="POST">
		<input type="hidden" value="{step}" name="step">
		<input type="hidden" value="{section}" name="section">
		<?php wp_nonce_field( 'rsssl_save', 'rsssl_le_nonce' ); ?>

        <div class="rsssl-wizard-title rsssl-section-content-title-header">
			<h1>{title}</h1>
            <span><a href="<?php echo esc_url(add_query_arg(array("page"=>"rlrsssl_really_simple_ssl"),admin_url("options-general.php") ) );?>"><?php _e("Back to Dashboard","really-simple-ssl")?></a></span>
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

