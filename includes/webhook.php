<?php
/**
 * Establishes the webhook listener for incoming requests from Airstory.
 *
 * @package Airstory
 */

namespace Airstory\Webhook;

use Airstory;
use Airstory\Connection as Connection;
use Airstory\Core as Core;
use Airstory\Credentials as Credentials;
use WP_REST_Request;

/**
 * Register the /airstory/v1/webhook endpoint within the WP REST API.
 */
function register_webhook_endpoint() {
	register_rest_route( 'airstory/v1', '/webhook', array(
		'methods'  => 'POST',
		'callback' => __NAMESPACE__ . '\handle_webhook',
	) );
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_webhook_endpoint' );

/**
 * Handle a request to the Airstory webhook.
 *
 * The payload should be delivered via an HTTP POST request, with the following structure:
 *
 * {
 *   id: XXX,
 *   project: 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
 *   document: 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'
 * }
 *
 * @param WP_REST_Request $request The WP REST API request object.
 */
function handle_webhook( WP_REST_Request $request ) {
	$user_id  = $request->get_param( 'id' );
	$project  = $request->get_param( 'project' );
	$document = $request->get_param( 'document' );

	// Establish an API connection, using the Airstory token of the connection owner.
	$api = new Airstory\API;
	$api->set_token( Credentials\get_token( $user_id ) );

	// Import the document, acting as the connection owner.
	$post_id  = Core\import_document( $api, $project, $document, $user_id );

	// Return early if import_document() gave us a WP_Error object.
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Since get_edit_post_link() depends on permission checks, we'll construct the link manually.
	$edit_path = add_query_arg( array(
		'post'   => $post_id,
		'action' => 'edit',
	), '/post.php' );

	return array(
		'project'  => $project,
		'document' => $document,
		'post_id'  => $post_id,
		'edit_url' => admin_url( $edit_path ),
	);
}
