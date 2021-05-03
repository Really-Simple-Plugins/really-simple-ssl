<div class="rsssl-section-content">
    <form action="{page_url}" method="POST">
		<input type="hidden" value="{step}" name="step">
		<input type="hidden" value="{section}" name="section">
		<?php wp_nonce_field( 'rsssl_save', 'rsssl_nonce' ); ?>

        <div class="rsssl-wizard-title rsssl-section-content-title-header">
			<h1>{title}</h1>
		</div>
        <div class="rsssl-wizard-title rsssl-section-content-notifications-header">
			<h1><?php _e("Notifications", 'really-simple-ssl')?></h1>
		</div>
	    {intro}
		{post_id}
		{fields}

	    <?php
	    $step = false;
	    if (isset($_GET['step'])) {
		    $step = intval($_GET['step']);
	    } else if (isset($_POST['step'])) {
		    $step = intval($_POST['step']);
	    }
	    if ($step) {
		    do_action("rsssl_le_installation_step", $step );
	    }
	    ?>

        <div class="rsssl-section-footer">
            {save_as_notice}
            {save_notice}
            <div class="rsssl-buttons-container">
                {previous_button}
                {save_button}
                {next_button}
            </div>
        </div>

    </form>
</div>

