<div class="rsssl-grid">
	<?php $hide = isset( $_GET['settings-updated']) ? 'rsssl-settings-saved--fade-in': ''; ?>
    <div class="rsssl-settings-saved <?php echo $hide?>">
        <div class="rsssl-settings-saved__text_and_icon">
            <span><div class="rsssl-tooltip-icon dashicons-before rsssl-icon rsssl-success check"><svg width="18" height="18" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1671 566q0 40-28 68l-724 724-136 136q-28 28-68 28t-68-28l-136-136-362-362q-28-28-28-68t28-68l136-136q28-28 68-28t68 28l294 295 656-657q28-28 68-28t68 28l136 136q28 28 28 68z"></path></svg></div></span>
            <span><?php _e('Changes saved successfully', 'really-simple-ssl') ?> </span>
        </div>
    </div>
    {content}
</div>