<?php
/**
 * Load asynchronous tasks.
 *
 * To prevent locking the main thread and possibly hitting timeouts, the plugin leverages the
 * TechCrunch WP Asynchronous Tasks library. Actions that require some potentially expensive
 * processes can instead be handled asynchronously, by using the wp_async_{hook} pattern.
 *
 * @link https://github.com/techcrunch/wp-async-task
 *
 * @package Airstory
 */

namespace Airstory\AsyncTasks;

require_once AIRSTORY_DIR . '/includes/lib/wp-async-task/wp-async-task.php';
require_once AIRSTORY_DIR . '/includes/async-tasks/class-updateallconnections.php';

/**
 * Each task must be initialized, no earlier than plugins_loaded.
 */
function init_async_tasks() {
	new UpdateAllConnections();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\init_async_tasks' );
