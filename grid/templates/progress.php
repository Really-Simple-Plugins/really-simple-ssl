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
            $open_task_count = RSSSL()->really_simple_ssl->get_remaining_tasks_count();
            if (RSSSL()->really_simple_ssl->ssl_enabled) {
                if ($open_task_count > 0) {
                _e("You're doing well. You still have");
                ?>
                <div class="rsssl-progress-count">
                    <?php echo  $open_task_count?>
                </div>
                <?php _e("tasks open", "really-simple-ssl");
                } else {
                    $pro_url = RSSSL()->really_simple_ssl->pro_url;
                    echo printf(__("Basic SSL configuration finished! Improve your score with %sReally Simple SSL Pro%s. ", "really-simple-ssl"), '<a target="_blank" href="' . $pro_url . '">', '</a>');
                }
            } else {
                _e("SSL is not yet enabled." , "really-simple-ssl");
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