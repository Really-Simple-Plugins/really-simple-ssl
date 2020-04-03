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
				$percentage_incomplete = "{percentage_incomplete}";
				echo __("You're doing well.", "really-simple-ssl");
				echo sprintf(__("Only %s left!", "really-simple-ssl"), $percentage_incomplete."%");
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