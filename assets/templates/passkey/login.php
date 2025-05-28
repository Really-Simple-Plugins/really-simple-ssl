<div id="custom-login">
    <form name="loginform" id="loginform" class="custom-login-form" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
        <p>
            <label for="user_login"><?php _e('Username or Email Address'); ?></label>
            <input type="text" name="log" id="user_login" class="input" value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>" size="20" />
        </p>
        <p>
            <label for="user_pass"><?php _e('Password'); ?></label>
            <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" />
        </p>
        <p class="forgetmenot">
            <label for="rememberme">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e('Remember Me'); ?>
            </label>
        </p>
        <p class="submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Log In'); ?>" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr(isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : home_url()); ?>" />
        </p>
    </form>
</div>
