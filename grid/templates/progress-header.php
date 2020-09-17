	<div class="rsssl-secondary-header-item">
		<?php $all_task_count = RSSSL()->really_simple_ssl->get_all_task_count(); ?>
		<div class="all-task-text">
			<input type="checkbox" class="rsssl-task-toggle" id="rsssl-all-tasks" name="rsssl_all_tasks" <?php if (get_option('rsssl_all_tasks') ) echo "checked"?>>
			<label for="rsssl-all-tasks"><?php _e( "All tasks", "really-simple-ssl" ); ?></label>
		</div>
		<div class="all-task-count">
			<?php echo " " . "(" . $all_task_count . ")"; ?>
		</div>
		<?php
		$open_task_count = RSSSL()->really_simple_ssl->get_remaining_tasks_count();
		if ($open_task_count ==! 0) {?>
			<div class="open-task-text">
				<input type="checkbox" class="rsssl-task-toggle" id="rsssl-remaining-tasks" name="rsssl_remaining_tasks" <?php if (get_option('rsssl_remaining_tasks') ) echo "checked"?>>
				<label for="rsssl-remaining-tasks"><?php _e( "Remaining tasks", "really-simple-ssl" ); ?></label>
			</div>
			<div class="open-task-count">
				<?php echo " " . "(" . $open_task_count . ")"; ?>
			</div> <?php
		}
		?>
	</div>
