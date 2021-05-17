<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

update_option('rsssl_le_ssl_user', get_current_user_id() );
?>
<div class="rsssl-section">
    <?php rsssl_progress_add('system-status');?>
</div>
