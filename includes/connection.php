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
	$api     = new API;
	$profile = $api->get_profile();

	// Nothing valid came back, so we have no data to store.
	if ( is_wp_error( $profile ) ) {
		return array();
	}

	// Retrieve only the necessary items.
	return array(
		'user_id'    => $profile->id,
		'first_name' => $profile->first_name,
		'last_name'  => $profile->last_name,
		'email'      => $profile->email,
	);
}
