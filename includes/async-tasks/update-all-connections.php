<?php
/**
 * Events that should cause Airstory to update all connections.
 *
 * @package Airstory
 */

namespace Airstory\AsyncTasks;

/**
 * Async task for "airstory_update_all_connections".
 *
 * @link https://github.com/techcrunch/wp-async-task
 */
class UpdateAllConnections extends \WP_Async_Task {

	/**
	 * The action that normally would have been called.
	 *
	 * @var string
	 */
	protected $action = 'airstory_update_all_connections';

	/**
	 * Prepare data for the asynchronous request.
	 *
	 * As nothing needs to be prepared, this method simply returns an empty array.
	 *
	 * @param array $data An array of arguments sent to the hook.
	 * @return array An empty array, as there are no arguments.
	 */
	protected function prepare_data( $data ) {
		return array();
	}

	/**
	 * Run the async task action.
	 */
	protected function run_action() {
		$hook = sprintf( 'wp_async_%s', $this->action );

		do_action( $hook );
	}
}
