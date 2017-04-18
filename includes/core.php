<?php
/**
 * Core functionality for Airstory.
 *
 * @package Airstory.
 */

namespace Airstory\Core;

/**
 * Verify that the WP-API OAuth Server plugin is installed and activated.
 *
 * If not, an admin notification will be queued up with instructions for installing/activating
 * the necessary requirements.
 */
function check_dependencies() {
	if ( ! is_plugin_active( 'rest-api-oauth1/oauth-server.php' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\notify_dependencies_not_installed' );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\check_dependencies' );

/**
 * Display an admin notice, informing the user that the OAuth 1.0a Server plugin is either not
 * installed or not activated.
 *
 * @todo Automate the installation/activation with a button click.
 */
function notify_dependencies_not_installed() {
?>

	<div class="notice notice-warning">
		<p><?php esc_html_e( 'In order to connect with Airstory, the WordPress REST API - OAuth Server 1.0a plugin must be installed and activated', 'airstory' ); ?></p>
	</div>

<?php
}
