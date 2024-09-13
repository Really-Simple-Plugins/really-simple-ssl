<?php
// first we validate the data variables
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Email;
use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Totp;

/**
 * @param $variable
 * @param $default
 * @return void
 */
function rsssl_check_and_set(&$variable, $default = null)
{
    if (!isset($variable)) {
        $variable = $default;
    }
}

// Use the function for all your variables
$variables_to_check = array(
    'available_providers',
    'selected_provider',
    'rememberme',
    'interim_login',
    'nonce',
    'login_nonce',
    'action',
    'redirect_to',
    'url',
    'minified_js',
    'minified_css',
    'interim_login',
    'backup_providers',
    'provider',
    'primary_provider',
    '$skip_two_fa_url',
    'is_today'
);

foreach ($variables_to_check as $var) {
    rsssl_check_and_set($$var);
}
login_header();
// We create the onboarding form.
?>

    <form id="two_fa_onboarding_form" class="login-form" method="post">
        <h3><?php echo esc_html__('Two-Factor Authentication', 'really-simple-ssl'); ?></h3>
        <p>
            <?php
            if ($is_forced) {
                echo esc_html__(
                    'This site requires you to secure your account with a second authentication method.',
                    'really-simple-ssl'
                );
            } else {
                echo sprintf(esc_html__(
                    'You can protect your account with a second authentication layer. Please choose one of the following methods, or click %s if you don’t want to use Two-Factor Authentication.',
                    'really-simple-ssl'
                ), esc_html__('Don\'t ask again', 'really-simple-ssl'));
            }
            ?>
        </p>
        <?php
        if ($is_forced && $grace_period) {
            ?>
            <br/>
            <p>
                <?php
                if (!$is_today) {
                    echo sprintf(esc_html__(
                        'Please make sure to configure a method, access to your account will be denied if no method is configured within the next %s days.',
                        'really-simple-ssl'
                    ), $grace_period);
                } else {
                    echo esc_html__('Please make sure to configure a method, access to your account will be denied if no method is configured today.', 'really-simple-ssl');
                }

                ?>
            </p>
            <?php
        }
        ?>
        <div id="rsssl_step_one_onboarding" class="rsssl_step_one_onboarding">
            <?php
            // We loop through the available providers and create a radio button for each but the first one if it is TOTP is checked
            foreach ($available_providers as $provider) {
                $checked = get_class($provider) === $primary_provider;
                $provider::get_selection_option($user, $checked);
            }
            ?>
        </div>
        <div id="rsssl_step_two_onboarding" class="rsssl_step_two_onboarding">
            <p>
                <?php
                try {
                    Rsssl_Two_Factor_Totp::display_onboarding_step_totp($user);
                } catch (Exception $e) {
                    // We just continue to the redirected page
                    wp_redirect($redirect_to);
                }
                ?>
            </p>
        </div>
        <div id="rsssl_step_three_onboarding" class="rsssl_step_three_onboarding">
            <p class="two-factor-prompt"><?php echo esc_html__('A verification code has been sent to the email address associated with your account.', 'really-simple-ssl'); ?></p>
            <p>
                <label for="rsssl-authcode"><?php echo esc_html__('Verification Code:', 'really-simple-ssl'); ?></label>
                <input type="text" inputmode="numeric" name="rsssl-two-factor-email-code" id="rsssl-authcode"
                       class="input rsssl-authcode" value="" size="20" pattern="[0-9 ]*" placeholder="1234 5678"
                       data-digits="8"/>
            </p>
            <p class="rsssl-two-factor-email-resend">
                <button class="button"
                        id="<?php echo esc_attr(Rsssl_Two_Factor_Email::RSSSL_INPUT_NAME_RESEND_CODE); ?>"
                        name="<?php echo esc_attr(Rsssl_Two_Factor_Email::RSSSL_INPUT_NAME_RESEND_CODE); ?>"><?php esc_attr_e('Resend Code', 'really-simple-ssl'); ?></button>
            </p>
        </div>
        <p class="skip_container">
            <?php
            if (!$is_forced) {
                ?>
                <a href="#" id="do_not_ask_again">
                    <?php echo esc_html__('Don\'t ask again', 'really-simple-ssl'); ?>
                </a>
                <a href="#" id="skip_onboarding">
                    <?php echo esc_html__('Skip', 'really-simple-ssl'); ?>
                </a>
                <?php
            } else {
                // We check if there is a grace period.
                if ($grace_period) {
                    ?>
                    <a href="#" id="skip_onboarding">
                        <?php
                        if ($is_today) {
                            echo esc_html__('Skip (Only today remaining)', 'really-simple-ssl');

                        } else {
                            echo sprintf(
                                esc_html__('Skip (%1$d %2$s remaining)', 'really-simple-ssl'),
                                $grace_period,
                                $grace_period > 1 ? esc_html__('days', 'really-simple-ssl') : esc_html__('day', 'really-simple-ssl')
                            );
                        }
                        ?>
                    </a>
                    <?php
                } else {
                    ?>
                    <span></span>
                    <?php
                }
            }
            ?>
            <input type="submit" id="rsssl_continue_onboarding" name="onboarding_submit"
                   class="button button-primary button-large"
                   value="<?php echo esc_html__('Continue', 'really-simple-ssl'); ?>"/>
        </p>
    </form>
<?php
login_footer();
