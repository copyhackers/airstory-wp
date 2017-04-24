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
		return $this->make_authenticated_request( sprintf( '/projects/%s', $project_id ) );
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
	 * @return stdClass|WP_Error If everything comes back okay, the JSON-decoded response will be
	 *                           returned as a stdClass object. Otherwise, a WP_Error object will be
	 *                           given with an explanation of what went wrong.
	 */
	protected function make_authenticated_request( $path ) {
		$url   = sprintf( '%s%s', self::API_BASE, $path );
		$token = $this->get_credentials();

		// Don't even attempt the request if we don't have a token.
		if ( ! $token ) {
			return new WP_Error(
				'airstory-missing-credentials',
				__( 'An Airstory token is required to make this request', 'airstory' )
			);
		}

		// Assemble the request, along with an Authorization header.
		$request = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => sprintf( 'Bearer=%s', $token ),
			),
		) );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		// Retrieve the request body and JSON-decode it.
		$json   = wp_remote_retrieve_body( $request );
		$result = json_decode( $json, false );

		// Something went wrong decoding the JSON.
		if ( ! $result ) {
			return new WP_Error(
				'airstory-invalid-json',
				__( 'The request did not return valid JSON', 'airstory' ),
				array( 'request' => $request )
			);
		}

		// If we've gotten this far, the request was completed successfully.
		return $result;
	}
}
