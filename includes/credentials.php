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

/**
 * Defines the cipher algorithm used by set_token().
 *
 * For a full list of available options, @see openssl_get_cipher_methods().
 */
define( 'AIRSTORY_ENCRYPTION_ALGORITHM', 'AES-256-CTR' );

/**
 * Retrieve the encryption key used to store Airstory tokens.
 *
 * Tokens are stored in an encrypted form using the OpenSSL library — in order to do so, it's
 * necessary to have a secret key used for encrypting/decrypting this information.
 *
 * If desired, an AIRSTORY_ENCRYPTION_KEY constant can be added to the site's wp-config.php file,
 * causing that key to be used instead of AUTH_KEY (one of the WordPress defaults). That way, in
 * the event that AUTH_KEY is compromised, Airstory users won't necessarily need to reauthenticate.
 *
 * @return string The encryption key to be used for encrypting and decrypting Airstory tokens.
 *
 * @todo Attempt to generate + inject the constant into wp-config.php upon plugin activation.
 */
function get_encryption_key() {
	return defined( 'AIRSTORY_ENCRYPTION_KEY' ) ? AIRSTORY_ENCRYPTION_KEY : AUTH_KEY;
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
	return function_exists( 'random_bytes' ) ? random_bytes( 16 ) : mcrypt_create_iv( 16 );
}

/**
 * Encrypt and store the Airstory token for a given user.
 *
 * @param int    $user_id The user ID.
 * @param string $token   The token to store for the user.
 * @return string The encrypted version of the token, which has been stored.
 */
function set_token( $user_id, $token ) {
	$iv        = get_iv();
	$encrypted = openssl_encrypt( $token, AIRSTORY_ENCRYPTION_ALGORITHM, get_encryption_key(), null, $iv );

	if ( false === $encrypted ) {
		return new WP_Error( 'airstory-encryption', __( 'Unable to encrypt Airstory token', 'airstory' ) );
	}

	// Store the encrypted values and the IV.
	update_user_meta( $user_id, '_airstory_token', $encrypted );
	update_user_meta( $user_id, '_airstory_iv', $iv );

	return $encrypted;
}

/**
 * Retrieve the unencrypted Airstory token for the current user.
 *
 * @param  int $user_id The ID of the user to retrieve the token for.
 * @return string|WP_Error Either the unencrypted Airstory token for the current user, an empty
 *                         string if no token exists, or a WP_Error if we're unable to decrypt.
 */
function get_token( $user_id ) {
	$encrypted = get_user_meta( $user_id, '_airstory_token', true );
	$iv        = get_user_meta( $user_id, '_airstory_iv', true );

	// Return early if either meta value is empty.
	if ( empty( $encrypted ) || empty( $iv ) ) {
		return '';
	}

	$token = openssl_decrypt( $encrypted, AIRSTORY_ENCRYPTION_ALGORITHM, get_encryption_key(), null, $iv );

	if ( false === $token ) {
		return new WP_Error( 'airstory-decryption', __( 'Unable to decrypt Airstory token', 'airstory' ) );
	}

	return $token;
}
