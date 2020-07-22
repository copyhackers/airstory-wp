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

namespace Airstory\Credentials;

use Airstory\Settings as Settings;
use Exception;
use InvalidArgumentException;
use WP_Error;

/**
 * Get the cipher algorithm used in this environment.
 *
 * Will compare a list of preferred ciphers against the ciphers available in this environment, then
 * store the cipher used in the database. This avoids users having to reconnect to Airstory after
 * system updates (for instance, a new version of PHP that includes a more preferred cipher).
 *
 * For a full list of available options, @see openssl_get_cipher_methods().
 *
 * @throws InvalidArgumentException If no preferred algorithm is found.
 *
 * @return string The cipher algorithm to use on this site.
 */
function get_cipher_algorithm() {
	$cached = get_site_option( '_airstory_cipher_algorithm' );

	if ( $cached ) {
		return $cached;
	}

	// Of the preferred ciphers, which ones are available?
	$preferred = array(
		'AES-256-CTR', // Must be first in the list, as this used to be the *only* option.
		'AES-256-CFB',
		'AES-128-CFB',
		'aes-256-ctr', // Need lowercase as well since some servers only give lowercase.
		'aes-256-cfb',
		'aes-128-cfb',
	);
	$available = array_intersect( $preferred, openssl_get_cipher_methods() );

	if ( empty( $available ) ) {
		throw new InvalidArgumentException(
			__( 'None of the preferred cipher algorithms are available on this server.', 'airstory' )
		);
	}

	// Get the first value of the filtered array — that's our top choice.
	$algorithm = array_shift( $available );

	// Cache the result.
	add_site_option( '_airstory_cipher_algorithm', $algorithm );

	return $algorithm;
}

/**
 * Generate an initialization vector (IV) for encrypting tokens.
 *
 * In PHP 7.0+, this will be done with random_bytes(), which is the preferred method and will be
 * available on better hosts. For those users who are running on older servers, however, we'll fall
 * back to the now-deprecated mcrypt_create_iv().
 *
 * @see random_bytes()
 *
 * @return string A 16-byte initialization vector, for use with openssl_encrypt().
 */
function get_iv() {
	// phpcs:disable PHPCompatibility.PHP.NewFunctions.random_bytesFound, PHPCompatibility.PHP.RemovedExtensions.mcryptDeprecatedRemoved, PHPCompatibility.PHP.DeprecatedFunctions.mcrypt_create_ivDeprecatedRemoved
	$bytes = function_exists( 'random_bytes' ) ? random_bytes( 8 ) : mcrypt_create_iv( 8 );
	// phpcs:enable PHPCompatibility.PHP.NewFunctions.random_bytesFound, PHPCompatibility.PHP.RemovedExtensions.mcryptDeprecatedRemoved, PHPCompatibility.PHP.DeprecatedFunctions.mcrypt_create_ivDeprecatedRemoved

	return bin2hex( $bytes ); // Will produce an IV 16 characters long.
}

/**
 * Encrypt and store the Airstory token for a given user.
 *
 * @throws Exception When OpenSSL fails to encrypt a token.
 *
 * @param int    $user_id The user ID.
 * @param string $token   The token to store for the user.
 * @return string|WP_Error The encrypted version of the token, which has been stored. If the token
 *                         could not be encrypted, a WP_Error object will be returned instead.
 */
function set_token( $user_id, $token ) {
	try {
		$iv        = get_iv();
		$encrypted = openssl_encrypt( $token, get_cipher_algorithm(), AUTH_KEY, null, $iv );

		if ( false === $encrypted ) {
			throw new Exception( __( 'Encrypted token was empty', 'airstory' ) );
		}
	} catch ( Exception $e ) {
		return new WP_Error(
			'airstory-encryption',
			__( 'Unable to encrypt Airstory token', 'airstory' ),
			$e->getMessage()
		);
	}

	// Store the encrypted values and the IV.
	Settings\set_user_data(
		$user_id, 'user_token', array(
			'token' => $encrypted,
			'iv'    => $iv,
		)
	);

	return $encrypted;
}

/**
 * Retrieve the unencrypted Airstory token for the current user.
 *
 * @throws Exception When OpenSSL fails to decrypt a token.
 *
 * @param  int $user_id The ID of the user to retrieve the token for.
 * @return string|WP_Error Either the unencrypted Airstory token for the current user, an empty
 *                         string if no token exists, or a WP_Error if we're unable to decrypt.
 */
function get_token( $user_id ) {

	// Verify the user actually exists.
	if ( ! get_user_by( 'ID', $user_id ) ) {
		return '';
	}

	$encrypted = Settings\get_user_data( $user_id, 'user_token', false );

	// Return early if either meta value is empty.
	if ( ! isset( $encrypted['token'], $encrypted['iv'] ) ) {
		return '';
	}

	try {
		$token = openssl_decrypt( $encrypted['token'], get_cipher_algorithm(), AUTH_KEY, null, $encrypted['iv'] );

		if ( false === $token ) {
			throw new Exception();
		}
	} catch ( Exception $e ) {
		return new WP_Error(
			'airstory-decryption',
			__( 'Unable to decrypt Airstory token', 'airstory' ),
			$e->getMessage()
		);
	}

	// Extra sanitization on the now-unencrypted value.
	return sanitize_text_field( $token );
}

/**
 * Clear a user's token.
 *
 * @param int $user_id The user ID to clear token-related user meta for.
 * @return bool Were the relevant user meta entries deleted?
 */
function clear_token( $user_id ) {
	return Settings\set_user_data( $user_id, 'user_token', null );
}
