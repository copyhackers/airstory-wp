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

use Airstory\Credentials as Credentials;
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
	 * Cache the user token, once it's been collected.
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * Retrieve information about a particular project.
	 *
	 * @param string $project_id The project's UUID.
	 * @return stdClass|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_project( $project_id ) {
		return $this->decode_json_response(
			$this->make_authenticated_request(
				sprintf(
					'/projects/%s',
					$project_id
				)
			)
		);
	}

	/**
	 * Retrieve information about a particular document.
	 *
	 * @param string $project_id The project's UUID.
	 * @param string $document_id The document's UUID.
	 * @return stdClass|WP_Error The response from Airstory\API::make_authenticated_request().
	 */
	public function get_document( $project_id, $document_id ) {
		return $this->decode_json_response(
			$this->make_authenticated_request(
				sprintf(
					'/projects/%s/documents/%s',
					$project_id,
					$document_id
				)
			)
		);
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
		$request = $this->make_authenticated_request(
			sprintf(
				'/projects/%s/documents/%s/content',
				$project_id,
				$document_id
			)
		);

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
	 *                         WP_Error should anything go awry.
	 */
	public function post_target( $email, $target ) {
		$response = $this->make_authenticated_request(
			sprintf( '/users/%s/targets', $email ), array(
				'method'  => 'POST',
				'headers' => array(
					'content-type' => 'application/json',
				),
				'body'    => wp_json_encode( $target ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['headers']['link'] ) ) {
			return new WP_Error( 'airstory-link', __( 'Invalid response from Airstory when connecting account', 'airstory' ), $response );
		}

		return sanitize_text_field( $response['headers']['link'] );
	}

	/**
	 * Update an existing target within Airstory.
	 *
	 * @param string $email         The user's Airstory email address.
	 * @param string $connection_id The target connection's UUID.
	 * @param array  $target        Updated target properties. For the full list, @see API::post_target().
	 * @return bool|WP_Error Either a boolean TRUE or a WP_Error should anything go awry.
	 */
	public function put_target( $email, $connection_id, $target ) {
		$response = $this->make_authenticated_request(
			sprintf( '/users/%s/targets/%s', $email, $connection_id ), array(
				'method'  => 'PUT',
				'headers' => array(
					'content-type' => 'application/json',
				),
				'body'    => wp_json_encode( $target ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'airstory-link', __( 'Invalid response from Airstory when updating account', 'airstory' ), $response );
		}

		return true;
	}

	/**
	 * Remove an existing target from within Airstory.
	 *
	 * @param string $email  The user's Airstory email address.
	 * @param string $target The target ID.
	 * @return string|WP_Error Either the UUID of the now-destroyed target within Airstory, or a
	 *                         a WP_Error should anything go awry.
	 */
	public function delete_target( $email, $target ) {
		$response = $this->make_authenticated_request(
			sprintf( '/users/%s/targets/%s', $email, $target ), array(
				'method' => 'DELETE',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $target;
	}

	/**
	 * Explicitly set the Airstory user token.
	 *
	 * This can be used to run a request as a given user, since get_credentials() will return the
	 * $this->token property if it's already set.
	 *
	 * @param string|WP_Error $token The Airstory user token to use, or a WP_Error object that may
	 *                               have been returned by Credentials\get_token().
	 * @return string The token that has been set. If $token was a valid string, this will just be
	 *                the value of $token.
	 */
	public function set_token( $token ) {
		if ( is_wp_error( $token ) ) {
			$token = '';
		}

		$this->token = (string) $token;

		return $this->token;
	}

	/**
	 * Retrieve the credentials for the currently logged-in user.
	 *
	 * @return string The bearer token to be passed with API requests.
	 */
	protected function get_credentials() {
		/*
		 * If someone's attempted to assign a token via set_token(), this value will be an empty string
		 * instead of NULL. This should be enough to indicate we may not necessarily want to default to
		 * the current user.
		 */
		if ( null !== $this->token ) {
			return $this->token;
		}

		return $this->set_token( Credentials\get_token( wp_get_current_user()->ID ) );
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
		$args = wp_parse_args(
			$args, array(
				'method'  => 'GET',
				'headers' => array(),
			)
		);

		// Explicitly append the Authorization header, which is required by Airstory.
		$args['headers']['Authorization'] = sprintf( 'Bearer=%s', $token );

		// Assemble the request, along with an Authorization header.
		return wp_remote_request( $url, $args );
	}

	/**
	 * For requests that return JSON (e.g. anything except getting the generated HTML), JSON-decode
	 * the API response and return it.
	 *
	 * @param array|WP_Error $response The HTTP response array, or a WP_Error object that might be
	 *                                 passed from the HTTP request.
	 * @return stdClass|WP_Error If JSON-decoded successfully, a stdClass representation of the
	 *                           response body, otherwise a WP_Error object.
	 */
	protected function decode_json_response( $response ) {

		// If we were given a WP_Error object, give it right back.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

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
