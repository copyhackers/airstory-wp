<?php
/**
 * Establishes the webhook listener for incoming requests from Airstory.
 *
 * @package Airstory
 */

namespace Airstory\Webhook;

use Airstory;
use Airstory\Core as Core;
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
 *   project: 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'
 *   document: 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'
 * }
 *
 * @param WP_REST_Request $request The WP REST API request object.
 */
function handle_webhook( WP_REST_Request $request ) {
	$api      = new Airstory\API;
	$project  = $request->get_param( 'project' );
	$document = $request->get_param( 'document' );
	$post_id  = Core\import_document( $api, $project, $document );

	// Return early if import_document() gave us a WP_Error object.
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	return array(
		'project'  => $project,
		'document' => $document,
		'post_id'  => $post_id,
		'edit_url' => get_edit_post_link( $post_id ),
	);
}
