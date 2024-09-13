<?php
/**
 * This file contains the profile settings for the Two-Factor Authentication.
 * It is used to display the Two-Factor Authentication settings on the user profile page.
 * It also contains the logic to save the Two-Factor Authentication settings.
 *
 * @package really-simple-ssl-pro
 * @since 4.0.0
 *
 */

require_once rsssl_path . 'security/wordpress/two-fa/class-rsssl-two-factor-settings.php';
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Email;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;

?>
<br>
<p>
<h2><?php esc_html__('Two-Factor Authentication', 'really-simple-ssl'); ?></h2>
<p><?php esc_html__('Two-Factor Authentication adds an extra layer of security to your account. You can enable it here.', 'really-simple-ssl'); ?></p>
<?php if ($forced && !$one_enabled) : ?>
    <p class="notice notice-warning">
        <?php esc_html_e('Two-Factor Authentication is mandatory for your account, so you need to make a selection.', 'really-simple-ssl'); ?>
    </p>
<?php endif; ?>
<table class="form-table rsssl-table-two-fa">
    <!-- Two-Factor Authentication Selection -->
    <tr>
        <th scope="row">
            <label for="two-factor-authentication"><?php esc_html_e('Two-Factor Authentication', 'really-simple-ssl'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php esc_html_e('Two-Factor Authentication', 'really-simple-ssl'); ?></span>
                </legend>
                <label for="two-factor-authentication">
                    <input type="hidden" name="two-factor-authentication" value="<?php echo $forced ?>" />
                    <input type="checkbox" name="two-factor-authentication" id="two-factor-authentication"
                           value="1" <?php checked($one_enabled || $forced);
                    disabled($forced) ?> />
                    <?php esc_html_e('Enable Two-Factor Authentication', 'really-simple-ssl'); ?>
                </label>
            </fieldset>
        </td>
    </tr>
    <!-- Two-Factor Authentication Selection -->
    <?php if (!empty($backup_codes) && $one_enabled) : ?>
        <tr>
            <th scope="row">
                <label for="two-factor-backup-codes"><?php esc_html_e('Backup Codes', 'really-simple-ssl'); ?></label>
            </th>

            <td>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e('Backup Codes', 'really-simple-ssl'); ?></span>
                    </legend>
                    <!-- Displaying the download for the backup codes if Two-Factor Authentication is enabled -->
                    <a href="#"
                       id="download_codes"><?php esc_html_e('Download Backup Codes', 'really-simple-ssl'); ?></a>
                    <span class="rsssl-backup-codes warning"><?php esc_html_e('Codes only available for 5 minutes') ?></span>
                </fieldset>
            </td>
        </tr>
    <?php endif; ?>
    <!-- Two-Factor Authentication Method Selection -->
    <tr id="selection_two_fa">
        <th scope="row">
            <label for="two-factor-method"><?php echo esc_html__('Selected provider', 'really-simple-ssl'); ?></label>
        </th>
        <td>
            <fieldset>
                <legend class="screen-reader-text">
                    <span><?php esc_html_e('Preferred Method', 'really-simple-ssl'); ?></span>
                </legend>
                    <?php foreach (!$one_enabled? $available_providers:$providers as $provider) : ?>
                        <label for="two-factor-method-<?php echo esc_attr($provider); ?>">
                            <input type="radio" name="preferred_method" class="preferred_method_selection" id="preferred_method_<?= $provider::METHOD ?>"
                                   value="<?= esc_attr($provider::METHOD) ?>" <?php checked(strtolower($provider::METHOD) === strtolower(Rsssl_Two_Factor_Settings::get_login_action(  $user->ID ))); ?> />
                            <?= esc_html($provider::NAME) ?>
                        <br/>
                    <?php endforeach; ?>
            </fieldset>
        </td>

    </tr>
        <tr class="totp-config">
            <td>
                <div id="qr-code-container">
                    <p id="two-factor-qr-code">
                        <a href="<?= esc_url($totp_url) ?>">
                            Loading...
                            <img src="<?= esc_url(admin_url('images/spinner.gif')) ?>" alt=""/>
                        </a>
                    </p>
                    <p style="margin-bottom: 10px;">
                        <i id="totp-key">
                            <?php
                            echo esc_html(__('Copy setup key', 'really-simple-ssl'));
                            ?>
                        </i>
                    </p>
                    <label for="two-factor-totp-authcode">
                        <strong><?php esc_html_e('Authentication Code:', 'really-simple-ssl'); ?></strong>
                        <?php
                        /* translators: Example auth code. */
                        $placeholder = sprintf(__('eg. %s', 'really-simple-ssl'), '123 456');
                        ?>
                        <input type="tel" name="two-factor-totp-authcode" id="two-factor-totp-authcode" class="input"
                               value=""
                               size="20" pattern="[0-9 ]*" placeholder="<?= esc_attr($placeholder) ?>"/>
                    </label>
                    <!-- TOTP hidden fields -->
                    <input type="hidden" name="two-factor-totp-key" id="two-factor-totp-key"
                           value="<?= esc_attr($key) ?>"/>
                    <input type="hidden" name="two-factor-totp-url" id="two-factor-totp-url"
                           value="<?= esc_attr($totp_url) ?>"/>
                </div>

            </td>
        </tr>
    <tr id="rsssl_verify_email" class="rsssl_verify_email">
        <td colspan="2">
            <label for="rsssl-two-factor-email-code"><?php esc_html_e('Verification Code:', 'really-simple-ssl'); ?></label>
            <input type="text" inputmode="numeric" name="rsssl-two-factor-email-code" id="rsssl-two-factor-email-code"
                   class="input rsssl-authcode" value="" size="20" pattern="[0-9 ]*" placeholder="1234 5678"
                   data-digits="8"/>
            <p class="two-factor-prompt"><i><?php esc_html_e('A verification code has been sent to the email address associated with your account to verify functionality.', 'really-simple-ssl'); ?> <a href="#" id="rsssl_resend_code"> <?php esc_attr_e('Resend Code', 'really-simple-ssl'); ?></a></i></p>
        </td>
    </tr>
</table>

