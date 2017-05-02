<?php
/**
 * Uninstall routine for the Airstory plugin.
 *
 * Upon removing the plugin, all related user meta should be cleaned up, but *post* meta should be
 * left intact.
 *
 * @todo Remove any connections within Airstory before the user meta is deleted.
 *
 * @package Airstory
 */

global $wpdb;

// Prevent this file from being executed outside of the plugin uninstallation.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

// Remove all known user meta keys.
$query = "
	DELETE FROM $wpdb->usermeta WHERE meta_key IN (
		'_airstory_token',  # The encrypted user token.
		'_airstory_iv',     # The initialization vector used when encrypting the password.
		'_airstory_profile' # Information about the Airstory user account.
	);";

$wpdb->query( $query ); // WPCS: unprepared SQL ok.
