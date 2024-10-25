<?php
login_header();
?>
	<form class="login-form">
		<h3><?php echo esc_html__('Two-Factor Authentication', 'really-simple-ssl'); ?></h3>
		<br>
		<p>
			<?php echo esc_html($message) ?>
		</p>
	</form>
<?php
login_footer();