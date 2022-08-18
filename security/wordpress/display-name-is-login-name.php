<?php
defined( 'ABSPATH' ) or die();

/**
 * Add javascript to make first and last name fields required
 */
function rsssl_maybe_disable_registration_js() {
    ?>
    <script>
        window.addEventListener('load', () => {
            let firstName = document.getElementById('first_name');
            let lastName = document.getElementById('last_name');
            firstName.closest('tr').classList.add("form-required");
            lastName.closest('tr').classList.add("form-required");
        });
    </script>
    <?php
}
add_action( 'admin_print_footer_scripts', 'rsssl_maybe_disable_registration_js' );