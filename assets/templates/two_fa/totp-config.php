<?php
// Ensure the $user variable is available
if (!isset($user) || !$user instanceof WP_User) {
    // We throw an error here because the $user variable is required
    throw new RuntimeException('The $user variable is required.');
}

if (isset($_GET['error']) && $_GET['error'] === 1) {
    ?>
    <p class="error">
        <?php echo esc_html__('Authentication code is incorrect.', 'really-simple-ssl'); ?>
    </p>
    <?php
}
?>
<br/>
<p>
    <strong><?php echo esc_html__('Install Authentication app:', 'really-simple-ssl'); ?></strong><br/>
    <?php
    printf(
    // Translators: %s is the hyperlink for "Download"
        esc_html__('Use your authenticator app like Google Authenticator to scan the QR code below, then paste the provided Authentication code. %s', 'really-simple-ssl'),
        '<a id="download_codes" href="#">' . esc_html__('Download Backup Codes', 'really-simple-ssl') . '</a>'
    );
    ?>
</p>
<p id="two-factor-qr-code">
    <a href="#">
        Loading...
        <img src="<?php echo esc_url(admin_url('images/spinner.gif')); ?>" alt=""/>
    </a>
</p>
<p style="margin-bottom: 10px;">
    <i id="totp-key">
        <?php
        echo esc_html__('Copy setup key', 'really-simple-ssl');
        ?>
    </i>
</p>
<p>
    <label for="two-factor-totp-authcode">
        <strong><?php echo esc_html__('Authentication Code:', 'really-simple-ssl'); ?></strong>
        <?php
        /* translators: Example auth code. */
        $placeholder = sprintf(esc_html__('eg. %s', 'really-simple-ssl'), '123 456');
        ?>
        <input type="tel" name="two-factor-totp-authcode" id="two-factor-totp-authcode" class="input" value=""
               size="20" pattern="[0-9 ]*" placeholder="<?php echo esc_attr($placeholder); ?>"/>
    </label>
</p>

<input type="submit" class="button button-primary button-large totp-submit" name="two-factor-totp-submit"
       id="two-factor-totp-submit"
       value="<?php echo esc_html__('Submit', 'really-simple-ssl'); ?>"/>
