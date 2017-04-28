<?php
/**
 * Functionality related to maintaining a connection between WordPress and Airstory.
 *
 * Functions in this file are only related to establishing and storing the connection.
 *
 * @see class-api.php for specifics about the API.
 * @see settings.php for the user profile screen.
 * @see webhook.php for the webhook, called by Airstory when a user exports content.
 *
 * @package Airstory
 */

namespace Airstory\Connection;

use Airstory;

/**
 * Retrieve basic information about the user.
 *
 * @return array Either an array containing basic information about the Airstory user (id, name,
 *               and email) or an empty array if the user could not be validated.
 */
function get_user_profile() {
	$api     = new Airstory\API;
	$profile = $api->get_user();

	// Nothing valid came back, so we have no data to store.
	if ( is_wp_error( $profile ) ) {
		return array();
	}

	// Retrieve only the necessary items.
	return array(
		'user_id'    => sanitize_text_field( $profile->id ),
		'first_name' => sanitize_text_field( $profile->first_name ),
		'last_name'  => sanitize_text_field( $profile->last_name ),
		'email'      => sanitize_text_field( $profile->email ),
	);
}

/**
 * Given a connection, determine the corresponding user ID.
 *
 * @param string $connection_id The Airstory connection ID.
 * @return int The user ID associated with the connection, or 0 if no match was found.
 */
function get_connection_user_id( $connection_id ) {
	$query = new \WP_User_Query( array(
		'number'      => 1,
		'fields'      => 'ID',
		'count_total' => false,
		'meta_query'  => array(
			'key' => '_airstory_target',
			'value' => $connection_id,
		),
	) );

	return (int) empty( $query ) ? 0 : array_shift( $query->results );
}

/**
 * Once a user has provided their token, authenticate with Airstory and save information locally.
 *
 * This information will include the Airstory user's first/last name, email, and user_id, which
 * are used to connect WordPress to Airstory.
 *
 * After storing the profile information, a new "target" will be registered within the user's
 * Airstory account, and the target ID stored.
 *
 * @param int $user_id The ID of the user who has connected.
 */
function register_connection( $user_id ) {
	$profile = get_user_profile();

	if ( empty( $profile ) ) {
		return;
	}

	$target        = array(
		'identifier' => (string) $user_id, // Airstory expects a string.
		'name'       => get_bloginfo( 'name' ),
		'url'        => get_rest_url( null, '/airstory/v1/webhook' ),
	);
	$api           = new Airstory\API;
	$connection_id = $api->post_target( $profile['email'], $target );

	if ( is_wp_error( $connection_id ) ) {
		return;
	}

	// Store the profile and connection ID for the user.
	update_user_meta( $user_id, '_airstory_profile', $profile );
	update_user_meta( $user_id, '_airstory_target', sanitize_text_field( $connection_id ) );

	/**
	 * A connection between WordPress and Airstory has been established successfully.
	 *
	 * @param int    $user_id       The ID of the user that has connected.
	 * @param string $connection_id The UUID of the connection within Airstory.
	 * @param array  $target        The information sent to create the target within Airstory: site
	 *                              name, callback URL, and the WordPress user ID.
	 */
	do_action( 'airstory_register_connection', $user_id, $connection_id, $target );

	return $connection_id;
}
add_action( 'airstory_user_connect', __NAMESPACE__ . '\register_connection' );

/**
 * Update an existing connection with Airstory.
 *
 * This might occur if the site name or URL changes.
 *
 * @todo Update the corresponding connection via a PUT request.
 */
function update_connection() {

}

/**
 * If a user disconnects from Airstory, the corresponding connection should be removed as well.
 *
 * This function will remove the target within the user's Airstory profile, then remove any stored
 * Airstory profile information.
 *
 * @param int $user_id The ID of the user who has disconnected.
 */
function remove_connection( $user_id ) {
	$profile       = get_user_meta( $user_id, '_airstory_profile', true );
	$connection_id = get_user_meta( $user_id, '_airstory_target', true );

	if ( empty( $profile['email'] ) || empty( $connection_id ) ) {
		return;
	}

	$api = new Airstory\API;
	$api->delete_target( $profile['email'], $connection_id );

	// Clean up the post meta.
	delete_user_meta( $user_id, '_airstory_profile', $profile );
	delete_user_meta( $user_id, '_airstory_target', $connection_id );

	/**
	 * A connection between WordPress and Airstory has been closed successfully.
	 *
	 * @param int    $user_id       The ID of the user that has disconnected.
	 * @param string $connection_id The UUID of the connection within Airstory.
	 */
	do_action( 'airstory_remove_connection', $user_id, $connection_id );
}
add_action( 'airstory_user_disconnect', __NAMESPACE__ . '\remove_connection' );
