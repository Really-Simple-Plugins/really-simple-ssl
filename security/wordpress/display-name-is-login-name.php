<?php
defined( 'ABSPATH' ) or die();

/**
 * Add javascript to make first and last name fields required
 */
function rsssl_disable_registration_js() {
    if ( !isset($_SERVER['REQUEST_URI']) || (strpos($_SERVER['REQUEST_URI'], 'user-new.php')===false && strpos($_SERVER['REQUEST_URI'], 'profile.php')===false) ) {
        return;
    }
    ?>
    <script>
        window.addEventListener('load', () => {
            document.getElementById('first_name').closest('tr').classList.add("form-required");
            document.getElementById('last_name').closest('tr').classList.add("form-required");
        });
    </script>
    <?php
}
add_action( 'admin_print_footer_scripts', 'rsssl_disable_registration_js' );

/**
 * Add javascript to make first and last name fields required
 */
function rsssl_strip_userlogin() {
	if ( !isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], 'profile.php')===false ) {
		return;
	}
	?>
    <script>
        let rsssl_user_login = document.querySelector('input[name=user_login]');
        let rsssl_display_name = document.querySelector('select[name=display_name]');
        if ( rsssl_display_name.options.length>1) {
            for (let i = rsssl_display_name.options.length-1; i >= 0; i--) {
                if ( rsssl_user_login.value.toLowerCase() === rsssl_display_name.options[i].value.toLowerCase() ) {
                    rsssl_display_name.removeChild(rsssl_display_name.options[i])
                }
            }
        }
    </script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'rsssl_strip_userlogin' );