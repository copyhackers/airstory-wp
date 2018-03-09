<?php
/**
 * Compatibility with third-party plugins.
 *
 * @package Airstory
 */

namespace Airstory\Compatibility;

/**
 * Don't let WP-SpamShield block the Airstory webhook.
 *
 * By default, WP-SpamShield's Anti-Spam for Miscellaneous Forms feature will block incoming POST
 * requests that aren't explicitly whitelisted.
 *
 * @link https://www.redsandmarketing.com/plugins/wp-spamshield-anti-spam/compatibility-guide/
 *
 * @param bool $bypass Whether or not to bypass WP-SpamShield for the request.
 *
 * @return bool The possibly-modified $bypass value.
 */
function wpspamshield_whitelist_webhook( $bypass ) {
	if ( untrailingslashit( $_SERVER['REQUEST_URI'] ) === '/' . rest_get_url_prefix() . '/airstory/v1/webhook' ) {
		$bypass = true;
	}

	return $bypass;
}
add_filter( 'wpss_misc_form_spam_check_bypass', __NAMESPACE__ . '\wpspamshield_whitelist_webhook' );
