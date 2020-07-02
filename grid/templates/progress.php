<div class="rsssl-progress">
	<div class="progress-bar-container">
		<div class="progress">
			<div class="bar" style="width:{percentage_completed}%"></div>
		</div>
	</div>

	<div class="progress-text">
		<span class="rsssl-progress-percentage">
			{percentage_completed}%
		</span>
		<span class="rsssl-progress-text">
			<?php
            if (RSSSL()->really_simple_ssl->ssl_enabled) {
                echo "<b>" . __("Finished!", "really-simple-ssl") . "</b> ";
                echo sprintf(__("You have completed your basic configuration. Improve security with %sReally Simple SSL Pro%s."), '<a target="_blank" href="https://really-simple-ssl.com/pro/">', '</a>');
            } else {
                echo __("SSL is not yet enabled." , "really-simple-ssl");
            }
				?>
		</span>
	</div>

	<?php
	$tasks = RSSSL()->really_simple_ssl->get_notices_list();
	?>

	<div class="rsssl-task-list">
        <table class="really-simple-ssl-table">
        <thead></thead>
			<tbody>
			<?php
			$notices = $this->get_notices_list();
				foreach ($notices as $id => $notice) {
					RSSSL()->really_simple_ssl->notice_row($id, $notice);
				}
			?>
			</tbody>
        </table>
	</div>
</div>