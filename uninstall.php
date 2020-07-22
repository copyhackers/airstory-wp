<?php
/**
 * Uninstall routine for the Airstory plugin.
 *
 * Upon removing the plugin, all related user meta should be cleaned up, but *post* meta should be
 * left intact.
 *
 * @package Airstory
 */

namespace Airstory\Uninstall;

use Airstory\Connection as Connection;

require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/connection.php';
require_once __DIR__ . '/includes/credentials.php';
require_once __DIR__ . '/includes/settings.php';

/**
 * Retrieve the IDs of any sites with active Airstory connections.
 *
 * @global $wpdb
 *
 * @return array An array of WordPress site IDs that have one or more active Airstory connections.
 */
function get_active_site_ids() {
	global $wpdb;

	/*
	 * The SQL query attempts to parse a site ID out of the key name within wp_usermeta.
	 *
	 * Assuming the default WordPress $table_prefix (as set in wp-config.php) of "wp_", keys will
	 * look like:
	 *
	 * - wp__airstory_target
	 * - wp_2__airstory_target
	 * - wp_3__airstory_target
	 *
	 * The SUBSTRING() function within MySQL accepts three arguments: the string, the starting point
	 * (starting at 0), and the length of the string to return.
	 *
	 * The meta_key will be our string, and the length of $table_prefix (available via the
	 * $wpdb->base_prefix property) + 1 gives us a starting point (e.g. everything before the first
	 * digit of the site ID).
	 *
	 * The third argument is where things get more complicated: we need the length of the meta_key,
	 * less the length of the static portion ("_airstory_target"), the length of the table prefix,
	 * and one more (for the extra underscore immediately following the site ID).
	 */
	// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
	$site_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
		SELECT DISTINCT SUBSTRING(
			meta_key,
			LENGTH(%s) + 1,
			LENGTH(meta_key) - LENGTH('_airstory_target') - LENGTH(%s) - 1
		) AS site_id
		FROM $wpdb->usermeta WHERE meta_key LIKE '%_airstory_target'
		ORDER BY site_id;", $wpdb->base_prefix, $wpdb->base_prefix
		)
	);
	$site_ids = array_map( 'intval', $site_ids );
	// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery

	return array_values( array_filter( $site_ids ) );
}

/**
 * Collect any users that are connected to Airstory and close their connections.
 *
 * @global $wpdb
 */
function disconnect_all_users() {
	global $wpdb;

	$user_args       = array(
		'fields'      => 'ID',
		'number'      => 100,
		'count_total' => false,
		'meta_query'  => array(
			array(
				'key'     => $wpdb->prefix . '_airstory_target',
				'compare' => 'EXISTS',
			),
		),
	);
	$connected_users = new \WP_User_Query( $user_args );
	$user_ids        = $connected_users->results;

	while ( ! empty( $user_ids ) ) {
		$user_id = array_shift( $user_ids );

		Connection\remove_connection( $user_id );

		// If we've reached the end, get the next batch.
		if ( empty( $user_ids ) ) {
			$connected_users = new \WP_User_Query( $user_args );
			$user_ids        = $connected_users->results;
		}
	}
}

/**
 * Delete all _airstory_data usermeta keys from the database.
 *
 * @global $wpdb
 */
function delete_airstory_data() {
	global $wpdb;

	$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key = '_airstory_data';" ); // WPCS: unprepared SQL ok.

	// Remove the _airstory_encryption_algorithm site option.
	delete_site_option( '_airstory_cipher_algorithm' );
}

// Prevent this file from being executed outside of the plugin uninstallation.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ) {
	return;
}

/**
 * If we're in multisite, ensure we're working on the main site.
 *
 * Note that is_main_site() will always return true if we're not in a multisite environment.
 */
$is_switched = false;

if ( ! is_main_site() ) {
	switch_to_blog( get_network()->site_id );
	$is_switched = true;
}

// Clear out users on the main site.
disconnect_all_users();

// Switch back if we weren't on the main site before.
if ( $is_switched ) {
	restore_current_blog();
}

// Determine if there are other sites that need clearing.
$active_blogs = get_active_site_ids();

if ( ! empty( $active_blogs ) ) {
	foreach ( $active_blogs as $blog_id ) {
		switch_to_blog( $blog_id );
		disconnect_all_users();
		restore_current_blog();
	}
}

// Finally, clear out profile data.
delete_airstory_data();
