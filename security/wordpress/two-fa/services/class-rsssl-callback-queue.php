<?php

namespace RSSSL\Security\WordPress\Two_Fa\Services;

class Rsssl_Callback_Queue {

    /**
     * Option name used to store the callback queue.
     *
     * @var string
     */
    private $option_name = 'rsssl_callback_queue';

    /**
     * Add a task to the queue.
     *
     * @param callable $callback The callback to run.
     * @param array    $args     The arguments to pass to the callback.
     *
     * @return void
     */
    public function add_task(callable $callback, array $args = []): void {
        $queue = get_option($this->option_name, []);
        // Each task is an associative array with a callback and its args.
        $queue[] = [
            'callback'  => $callback,
            'args'      => $args,
            'timestamp' => time(),
        ];
        $this->save_queue($queue);
    }

    /**
     * Retrieve the current queue.
     *
     * @return array
     */
    public function get_queue(): array {
        return get_option($this->option_name, []);
    }

    /**
     * Save the modified queue.
     *
     * @param array $queue The updated queue.
     *
     * @return void
     */
    public function save_queue(array $queue): void {
        update_option($this->option_name, $queue);
    }

    /**
     * Process a limited number of tasks from the queue.
     *
     * @param int $limit The maximum number of tasks to process.
     *
     * @return void
     */
    public function process_tasks(int $limit = 1): void {
        $queue = $this->get_queue();
        if (empty($queue)) {
            return;
        }
        // Process up to $limit tasks.
        $processed = 0;
        while (!empty($queue) && $processed < $limit) {
            $task = array_shift($queue);
            if (is_callable($task['callback'])) {
                // Call the callback with the stored arguments.
                call_user_func_array($task['callback'], $task['args']);
            }
            $processed++;
        }

        // Save the updated (remaining) queue.
        $this->save_queue($queue);
    }
}