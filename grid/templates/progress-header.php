<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>

<div class="rsssl-secondary-header-item">
	<?php $all_task_count = RSSSL()->really_simple_ssl->get_all_task_count(); ?>
	<div class="rsssl-tasks-container rsssl-all-tasks">
		<input type="checkbox" class="rsssl-task-toggle" id="rsssl-all-tasks" name="rsssl_all_tasks" <?php if (get_option('rsssl_all_tasks') ) echo "checked"?>>
		<label class="rsssl-tasks <?php if (get_option('rsssl_all_tasks') ) echo "active"?>" for="rsssl-all-tasks"><?php _e( "All tasks", "really-simple-ssl" ); ?><?php echo " " . "(" . $all_task_count . ")"; ?></label>
	</div>
    <div class="rsssl-spacer"></div>
	<?php
	$open_task_count = RSSSL()->really_simple_ssl->get_remaining_tasks_count();
	if ($open_task_count ==! 0) {?>
		<div class="rsssl-tasks-container rsssl-remaining-tasks">
			<input type="checkbox" class="rsssl-task-toggle" id="rsssl-remaining-tasks" name="rsssl_remaining_tasks" <?php if (get_option('rsssl_remaining_tasks') ) echo "checked"?>>
			<label for="rsssl-remaining-tasks" id="rsssl-remaining-tasks-label" class="<?php if (get_option('rsssl_remaining_tasks') ) echo "checked"?>"><?php _e( "Remaining tasks", "really-simple-ssl" ); ?><?php echo " " . '(<span class="rsssl_remaining_task_count">' . $open_task_count . "</span>)"; ?></label>
		</div>
        <?php
	}
	?>
</div>
