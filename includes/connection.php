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
use Airstory\Credentials as Credentials;

/**
 * Retrieve basic information about the user.
 *
 * @param int $user_id Optional. The ID of the user to retrieve. Defaults to the current user.
 * @return array Either an array containing basic information about the Airstory user (id, name,
 *               and email) or an empty array if the user could not be validated.
 */
function get_user_profile( $user_id = null ) {
	$api = new Airstory\API;

	// If we have a user ID, set the API token accordingly.
	if ( $user_id ) {
		$api->set_token( Credentials\get_token( $user_id ) );
	}

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
 * Build a target array for Airstory.
 *
 * @param int $user_id The ID of the WordPress user associated with the target.
 * @return array An array that will serve as a the post body for the target, containing three keys:
 *               identifier (user ID), name (blog name), and url (webhook URL).
 */
function get_target( $user_id ) {
	return array(
		'identifier' => (string) $user_id, // Airstory expects a string.
		'name'       => get_bloginfo( 'name' ),
		'url'        => get_rest_url( null, '/airstory/v1/webhook' ),
	);
}

/**
 * Display an error if the user was unable to authenticate with Airstory.
 *
 * @param WP_Error $errors Current user errors, passed by reference.
 */
function user_connection_error( &$errors ) {
	$errors->add( 'airstory-connection', __( 'WordPress was unable to authenticate with Airstory, please try again.', 'airstory' ) );
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
	$profile = get_user_profile( $user_id );

	if ( empty( $profile ) ) {
		add_action( 'user_profile_update_errors', __NAMESPACE__ . '\user_connection_error' );

		return;
	}

	$target        = get_target( $user_id );
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
 * Update an existing connection for an Airstory user.
 *
 * @param int $user_id The ID of the user whose connection should be updated.
 */
function update_connection( $user_id ) {
	$profile = get_user_profile( $user_id );

	if ( empty( $profile ) ) {
		return;
	}

	// Overwrite the existing target info for $connection_id.
	$connection_id = get_user_meta( $user_id, '_airstory_target', true );
	$target        = get_target( $user_id );
	$api           = new Airstory\API;
	$response      = $api->put_target( $profile['email'], $connection_id, $target );

	if ( is_wp_error( $response ) ) {
		return;
	}

	/**
	 * A connection between WordPress and Airstory has been established successfully.
	 *
	 * @param int    $user_id       The ID of the user that has connected.
	 * @param string $connection_id The UUID of the connection within Airstory.
	 * @param array  $target        The information sent to create the target within Airstory: site
	 *                              name, callback URL, and the WordPress user ID.
	 */
	do_action( 'airstory_update_connection', $user_id, $connection_id, $target );

	return $connection_id;
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

/**
 * Triggers an asynchronous regeneration of all connections.
 *
 * Certain events, like renaming the site or changing the URL, should cause connections within
 * Airstory to be updated.
 *
 * @param mixed $old_value The previous value for the option.
 * @param mixed $new_value The new value for the option.
 */
function trigger_connection_refresh( $old_value, $new_value ) {
	if ( $old_value === $new_value ) {
		return;
	}

	/**
	 * Cause Airstory to update all known connections.
	 */
	do_action( 'airstory_update_all_connections' );
}
add_action( 'update_option_blogname', __NAMESPACE__ . '\trigger_connection_refresh', 10, 2 );
add_action( 'update_option_siteurl', __NAMESPACE__ . '\trigger_connection_refresh', 10, 2 );
add_action( 'update_option_home', __NAMESPACE__ . '\trigger_connection_refresh', 10, 2 );

/**
 * Update connection details for all currently-connected users.
 *
 * Under certain conditions, it may be necessary to update the connection details within Airstory.
 * For example, if the site URL changes, this would impact webhook URL.
 */
function update_all_connections() {
	$user_args       = array(
		'fields'     => 'ID',
		'number'     => 100,
		'paged'      => 1,
		'meta_query' => array(
			array(
				'key'     => '_airstory_target',
				'compare' => 'EXISTS',
			),
		),
	);
	$connected_users = new \WP_User_Query( $user_args );
	$user_ids        = $connected_users->results;

	while ( ! empty( $user_ids ) ) {
		$user_id = array_shift( $user_ids );

		update_connection( $user_id );

		// If we've reached the end, get the next page.
		if ( empty( $user_ids ) ) {
			$user_args['paged']++;
			$connected_users = new \WP_User_Query( $user_args );
			$user_ids        = $connected_users->results;
		}
	}
}
add_action( 'wp_async_airstory_update_all_connections', __NAMESPACE__ . '\update_all_connections' );
