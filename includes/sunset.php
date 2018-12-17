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
