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
		$task_list = array(
			'',
			'',
			'',
			'',
		);
	?>

	<div class="rsssl-task-list">
		<span class="rsssl-task-open-closed">

		</span>
        <span class="rsssl-task">
			{task}
        </span>
	</div>

</div>