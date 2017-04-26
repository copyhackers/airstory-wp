<?php
/**
 * Wrapper for the Airstory API.
 *
 * Note that this file is not meant to be a full SDK for Airstory, and only includes the methods
 * necessary for the Airstory WordPress plugin.
 *
 * @package Airstory
 */

namespace Airstory;

use WP_Error;

/**
 * Airstory API wrapper class.
 */
class API {

	/**
	 * The base path for all API requests to Airstory (no trailing slash).
	 */
	const API_BASE = 'https://api.airstory.co/v1';

	/**
	 * Retrieve information about a particular project.
	 *
	 * @param string $project_id The project's UUID.
	 * @return stdClass|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_project( $project_id ) {
		return $this->decode_json_response( $this->make_authenticated_request( sprintf(
			'/projects/%s',
			$project_id
		) ) );
	}

	/**
	 * Retrieve information about a particular document.
	 *
	 * @param string $project_id The project's UUID.
	 * @param string $document_id The document's UUID.
	 * @return stdClass|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_document( $project_id, $document_id ) {
		return $this->decode_json_response( $this->make_authenticated_request( sprintf(
			'/projects/%s/documents/%s',
			$project_id,
			$document_id
		) ) );
	}

	/**
	 * Retrieve the rendered content for a given document.
	 *
	 * Note that this method will return the entire response, which includes a <!DOCTYPE> declaration
	 * and supporting <html> elements. These can be filtered via Airstory\Core\get_body_contents().
	 *
	 * @param string $project_id The project's UUID.
	 * @param string $document_id The document's UUID.
	 * @return string|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_document_content( $project_id, $document_id ) {
		$request = $this->make_authenticated_request( sprintf(
			'/projects/%s/documents/%s/content',
			$project_id,
			$document_id
		) );

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Retrieve basic information about the current user.
	 *
	 * @return stdClass|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_user() {
		return $this->decode_json_response( $this->make_authenticated_request( '/user' ) );
	}

	/**
	 * Create a new target for the given user.
	 *
	 * @param string $email The user's Airstory email address.
	 * @param array  $target {
	 *   The target consists of three properties, all of which are required.
	 *
	 *   @var int    $identifier The WordPress user ID. The API would also accept a username, but this
	 *                           should be something immutable.
	 *   @var string $name       The WordPress site name.
	 *   @var string $url        The webhook URL for this site.
	 * }
	 * @return string|WP_Error Either the UUID of the newly-created target within Airstory, or a
	 *                         a WP_Error should anything go awry.
	 */
	public function post_target( $email, $target ) {
		$response = $this->make_authenticated_request( sprintf( '/users/%s/targets', $email ), array(
			'method'  => 'POST',
			'headers' => array( 'content-type' => 'application/json' ),
			'body'    => wp_json_encode( $target ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['headers']['link'] ) ) {
			return new WP_Error( 'airstory-link', __( 'Invalid response from Airstory when connecting account' ) );
		}

		return $response['headers']['link'];
	}

	/**
	 * Retrieve the credentials for the currently logged-in user.
	 *
	 * @return string The bearer token to be passed with API requests.
	 *
	 * @todo While the authentication is being worked out on Airstory's side, the token will be
	 *       stored in a constant, defined in wp-config.php.
	 */
	protected function get_credentials() {
		return defined( 'AIRSTORY_API_KEY' ) ? AIRSTORY_API_KEY : null;
	}

	/**
	 * Make an authenticated request to the Airstory API.
	 *
	 * @param string $path The API endpoint, relative to the API_BASE constant. The path should begin
	 *                     with a leading slash.
	 * @param array  $args {
	 *   Optional. Additional arguments to pass to wp_remote_request(), which will be merged with
	 *   defaults. For a full list of available settings, @see wp_remote_request().
	 *
	 *   @var string $method The HTTP method (verb) to use. Default is "GET".
	 * }
	 * @return array|WP_Error If everything comes back okay, the response array. Otherwise, a
	 *                        WP_Error object will be given with an explanation of what went wrong.
	 */
	protected function make_authenticated_request( $path, $args = array() ) {
		$token = $this->get_credentials();

		// Don't even attempt the request if we don't have a token.
		if ( empty( $token ) ) {
			return new WP_Error(
				'airstory-missing-credentials',
				__( 'An Airstory token is required to make this request', 'airstory' )
			);
		}

		// Begin assembling the URL and arguments.
		$url  = sprintf( '%s%s', self::API_BASE, $path );
		$args = wp_parse_args( $args, array(
			'method'  => 'GET',
			'headers' => array(),
		) );

		// Explicitly append the Authorization header, which is required by Airstory.
		$args['headers']['Authorization'] = sprintf( 'Bearer=%s', $token );

		// Assemble the request, along with an Authorization header.
		return wp_remote_request( $url, $args );
	}

	/**
	 * For requests that return JSON (e.g. anything except getting the generated HTML), JSON-decode
	 * the API response and return it.
	 *
	 * @param array $response The HTTP response array.
	 * @return stdClass|WP_Error If JSON-decoded successfully, a stdClass representation of the
	 *                           response body, otherwise a WP_Error object.
	 */
	protected function decode_json_response( $response ) {
		$result = json_decode( wp_remote_retrieve_body( $response ), false );

		// Something went wrong decoding the JSON.
		if ( empty( $result ) ) {
			return new WP_Error(
				'airstory-invalid-json',
				__( 'The request did not return valid JSON', 'airstory' ),
				$response
			);
		}

		return $result;
	}
}
