<?php
/**
 * Credentials storage for Airstory API keys.
 *
 * At the time of this writing, Airstory exposes bearer tokens directly via the "My Account" panel
 * when logged into the app. Since it would be silly to simply store these tokens — despite the API
 * being largely read-only — in the database, this approach leverages the OpenSSL library.
 *
 * @link http://php.net/manual/en/intro.openssl.php
 *
 * @package Airstory
 */

namespace Airstory\Sunset;

use Airstory\Connection as Connection;
use Airstory\Settings as Settings;

define( __NAMESPACE__ . '\DEPRECATION_NOTICE', 'https://www.airstory.co/airstory-update-2018/' );

/**
 * Add a deprecation notice to the plugin screen.
 *
 * @param array  $links  Plugin meta links.
 * @param string $plugin The plugin filename.
 *
 * @return array The filtered $links array.
 */
function plugin_screen_deprecation_link( $links, $plugin ) {
	if ( 'airstory.php' === basename( $plugin ) ) {
		$links[] = sprintf(
			/* Translators: %1$s is the URL to the deprecation notice, %2$s is the anchor. */
			'<a href="%1$s"><strong>%2$s</strong></a>',
			esc_url( DEPRECATION_NOTICE ),
			esc_html__( 'Deprecation Notice', 'airstory' )
		);
	}

	return $links;
}
add_filter( 'plugin_row_meta', __NAMESPACE__ . '\plugin_screen_deprecation_link', 10, 2 );

/**
 * Show site admins and/or connected users a notice about the deprecation.
 */
function render_sunset_notice() {
	$user_id = wp_get_current_user()->ID;

	// Don't show this message if the user has already dismissed it.
	if ( Settings\get_user_data( $user_id, 'sunset_notice_dismissed', false ) ) {
		return;
	}

	if ( current_user_can( 'activate_plugins' ) ) {
		$message = __( 'On this date, users will no longer be able to import posts from Airstory. Please plan on deactivating/removing the Airstory plugin ahead of this date.', 'airstory' );
	} elseif ( Connection\has_connection( $user_id ) ) {
		$message = __( 'On this date, you will no longer be able to import posts from Airstory. Please import any posts you wish to save into WordPress (or otherwise export from Airstory) ahead of this date.', 'airstory' );
	} else {
		// No connection or power to change things, so there's no message.
		return;
	}

	// Ensure the necessary script is loaded.
	wp_enqueue_script( 'airstory-sunset' );

	// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>

	<div id="airstory-sunset-notice" class="notice notice-warning is-dismissible">
		<p><strong><?php esc_html_e( 'Airstory documents are being deprecated on January 15, 2019!', 'airstory' ); ?></strong></p>
		<p><?php echo esc_html( $message ); ?></p>
		<p><a href="<?php echo esc_url( DEPRECATION_NOTICE ); ?>"><?php esc_html_e( 'Additional information is available on the Airstory site.', 'airstory' ); ?></a></p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'airstory' ); ?></span>
		</button>
	</div>

<?php
	// phpcs:enable Generic.WhiteSpace.ScopeIndent
}
add_action( 'admin_notices', __NAMESPACE__ . '\render_sunset_notice' );
add_action( 'network_admin_notices', __NAMESPACE__ . '\render_sunset_notice' );

/**
 * Enqueue the Airstory sunset notice scripting.
 */
function register_script() {
	wp_register_script(
		'airstory-sunset',
		plugins_url( 'assets/js/sunset.js', __DIR__ ),
		array( 'jquery' ),
		AIRSTORY_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_script' );

/**
 * Dismiss the sunset notice.
 */
function dismiss_notice() {
	Settings\set_user_data( wp_get_current_user()->ID, 'sunset_notice_dismissed', true );
}
add_action( 'wp_ajax_airstory-dismiss-sunset-notice', __NAMESPACE__ . '\dismiss_notice' );
