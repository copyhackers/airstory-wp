<?php
/**
 * Asynchronous task to mirror "airstory_import_post".
 *
 * @package Airstory
 */

namespace Airstory\AsyncTasks;

/**
 * Async task for "airstory_import_post".
 *
 * @link https://github.com/techcrunch/wp-async-task
 */
class ImportPost extends \WP_Async_Task {

	/**
	 * The action that normally would have been called.
	 *
	 * @var string
	 */
	protected $action = 'airstory_import_post';

	/**
	 * Prepare data for the asynchronous request.
	 *
	 * @param array $data An array of arguments sent to the hook.
	 * @return array The arguments, formatted into an associative array.
	 */
	protected function prepare_data( $data ) {
		return array(
			'post_id' => $data[0],
		);
	}

	/**
	 * Run the async task action.
	 */
	protected function run_action() {
		$post_id = (int) $_POST['post_id'];
		$action  = sprintf( 'wp_async_%s', $this->action );

		do_action( $action, $post_id );
	}
}
