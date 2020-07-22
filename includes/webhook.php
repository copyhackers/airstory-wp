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
use WP_Error;

/**
 * Register the /airstory/v1/webhook endpoint within the WP REST API.
 */
function register_webhook_endpoint() {
	register_rest_route(
		'airstory/v1', '/webhook', array(
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\handle_webhook',
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_webhook_endpoint' );

/**
 * Handle a request to the Airstory webhook.
 *
 * The payload should be delivered via an HTTP POST request, with the following structure:
 *
 * {
 *   identifier: XXX,
 *   project: 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
 *   document: 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'
 * }
 *
 * @param WP_REST_Request $request The WP REST API request object.
 * @return array|WP_Error An array containing the project ID, document ID, the WordPress post ID,
 *                        and the WordPress post's edit URL if the post was imported successfully,
 *                        or a WP_Error object if anything went wrong.
 */
function handle_webhook( WP_REST_Request $request ) {
	$identifier = $request->get_param( 'identifier' );
	$project    = $request->get_param( 'project' );
	$document   = $request->get_param( 'document' );

	if ( empty( $identifier ) || empty( $project ) || empty( $document ) ) {
		$error = new WP_Error();

		foreach ( array( 'identifier', 'project', 'document' ) as $arg ) {
			if ( empty( $$arg ) ) {
				$error->add(
					'airstory-missing-argument', sprintf(
						/* Translators: %1$s is the request argument that is missing. */
						__( 'The "%1$s" argument is required', 'airstory' ),
						$arg
					)
				);
			}
		}
		return $error;
	}

	// Retrieve the decrypted user token.
	$user_token = Credentials\get_token( $identifier );

	if ( is_wp_error( $user_token ) ) {
		return $user_token;
	} elseif ( empty( $user_token ) ) {
		return new WP_Error(
			'airstory-missing-token',
			__( 'The current user has not provided an Airstory user token', 'airstory' )
		);
	}

	// Ensure that the proper CORS headers are sent.
	add_filter( 'rest_pre_serve_request', __NAMESPACE__ . '\override_cors_headers' );

	// Establish an API connection, using the Airstory token of the connection owner.
	$api = new Airstory\API();
	$api->set_token( $user_token );

	// Determine if there's a current post that matches.
	$post_id = Core\get_current_draft( $project, $document );

	if ( $post_id ) {
		$post_id = Core\update_document( $api, $project, $document, $post_id );
	} else {
		$post_id = Core\create_document( $api, $project, $document, $identifier );
	}

	// Return early if create_document() gave us a WP_Error object.
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Since get_edit_post_link() depends on permission checks, we'll construct the link manually.
	$edit_path = add_query_arg(
		array(
			'post'   => $post_id,
			'action' => 'edit',
		), '/post.php'
	);

	return array(
		'project'  => $project,
		'document' => $document,
		'post_id'  => $post_id,
		'edit_url' => admin_url( $edit_path ),
	);
}

/**
 * Override the default WP REST API CORS headers for the webhook, only enabling requests from the
 * Airstory domain(s).
 *
 * @param bool $served Whether the request has already been served. This will not be used.
 * @return bool The (unmodified) $served value.
 */
function override_cors_headers( $served ) {
	/**
	 * Filter the permitted CORS origins for Airstory webhook requests.
	 *
	 * @param array $origins Origins that should be permitted via CORS.
	 */
	$origins = apply_filters( 'airstory_webhook_cors_origin', array( 'https://app.airstory.co' ) );

	if ( ! empty( $origins ) ) {
		header(
			sprintf(
				'Access-Control-Allow-Origin: %s',
				implode( ' ', array_map( 'esc_url', $origins ) )
			)
		);
	}

	return $served;
}
