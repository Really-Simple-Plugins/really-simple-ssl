<?php
login_header();

if ( ! empty( $error_msg ) ) {
	echo '<div id="login_error" class="notice notice-error"><strong>Error: </strong>' . esc_html( $error_msg ) . '<br /></div>';
} else {
	\RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor::maybe_show_last_login_failure_notice( $user );
}
?>

<form name="rsssl_validate_2fa_form" id="loginform"
      action="<?php echo esc_url( \RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor::login_url( array( 'action' => 'validate_2fa' ), 'login_post' ) ); ?>"
      method="post" autocomplete="off">
    <input type="hidden" name="provider" id="provider" value="<?php echo esc_attr( $provider_class ); ?>"/>
    <input type="hidden" name="rsssl-wp-auth-id" id="rsssl-wp-auth-id"
           value="<?php echo esc_attr( $user->ID ); ?>"/>
    <input type="hidden" name="rsssl-wp-auth-nonce" id="rsssl-wp-auth-nonce"
           value="<?php echo esc_attr( $login_nonce ); ?>"/>
	<?php if ( $interim_login ) { ?>
        <input type="hidden" name="interim-login" value="1"/>
	<?php } else { ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"/>
	<?php } ?>
    <input type="hidden" name="rememberme" id="rememberme" value="<?php echo esc_attr( $rememberme ); ?>"/>

	<?php $provider->authentication_page( $user ); ?>
</form>

<?php
 if ( get_class($provider) === 'RSSSL\Pro\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Totp') {
?>
<div class="backup-methods-wrap">
<!--    <p class="backup-methods">-->
<!--        <a>-->
<!--			--><?php
//			echo esc_html__('Or, enter your backup code.', 'really-simple-ssl');
//			?>
<!--        </a>-->
<!--    </p>-->
</div>
<?php
}
?>
<style>
    /* @todo: migrate to an external stylesheet. */
    .backup-methods-wrap {
        margin-top: 16px;
        padding: 0 24px;
    }

    .backup-methods-wrap a {
        color: #999;
        text-decoration: none;
    }

    ul.backup-methods {
        display: none;
        padding-left: 1.5em;
    }

    /* Prevent Jetpack from hiding our controls, see https://github.com/Automattic/jetpack/issues/3747 */
    .jetpack-sso-form-display #loginform > p,
    .jetpack-sso-form-display #loginform > div {
        display: block;
    }

    #login form p.two-factor-prompt {
        margin-bottom: 1em;
    }

    .input.rsssl-authcode {
        letter-spacing: .3em;
    }

    .input.rsssl-authcode::placeholder {
        opacity: 0.5;
    }
</style>
<script>
    (function () {
        // Enforce numeric-only input for numeric inputmode elements.
        const form = document.querySelector('#loginform'),
            inputEl = document.querySelector('input.rsssl-authcode[inputmode="numeric"]'),
            expectedLength = inputEl?.dataset.digits || 0;

        if (inputEl) {
            let spaceInserted = false;
            inputEl.addEventListener(
                'input',
                function () {
                    let value = this.value.replace(/[^0-9 ]/g, '').trimStart();

                    if (!spaceInserted && expectedLength && value.length === Math.floor(expectedLength / 2)) {
                        value += ' ';
                        spaceInserted = true;
                    } else if (spaceInserted && !this.value) {
                        spaceInserted = false;
                    }

                    this.value = value;

                    // Auto-submit if it's the expected length.
                    if (expectedLength && value.replace(/ /g, '').length == expectedLength) {
                        if (undefined !== form.requestSubmit) {
                            form.requestSubmit();
                            form.submit.disabled = "disabled";
                        }
                    }
                }
            );
        }
    })();
</script>
