<?php
/**
 * Uninstall routine for the Airstory plugin.
 *
 * Upon removing the plugin, all related user meta should be cleaned up, but *post* meta should be
 * left intact.
 *
 * @package Airstory
 */

namespace Airstory;

global $wpdb;

// Prevent this file from being executed outside of the plugin uninstallation.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

/**
 * Collect any users that are connected to Airstory and close their connections.
 *
 * To avoid possible cache collisions, this is being written as a direct SQL query.
 */
$connected_user_query = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_airstory_target' LIMIT 100";
$connected_user_ids   = $wpdb->get_col( $connected_user_query ); // WPCS: unprepared SQL ok.

while ( ! empty( $connected_user_ids ) ) {
	$user_id = array_shift( $connected_user_ids );

	// Remove the user's connection within Airstory.
	Connection\remove_connection( $user_id );

	// When we get down to 0 entries in the array, run the query again and see if we have more.
	if ( 0 === count( $connected_user_ids ) ) {
		$connected_user_ids = $wpdb->get_col( $connected_user_query ); // WPCS: unprepared SQL ok.
	}
}

// Remove all known user meta keys.
$query = "
	DELETE FROM $wpdb->usermeta WHERE meta_key IN (
		'_airstory_token',  # The encrypted user token.
		'_airstory_iv',     # The initialization vector used when encrypting the password.
		'_airstory_profile' # Information about the Airstory user account.
	);";

$wpdb->query( $query ); // WPCS: unprepared SQL ok.
