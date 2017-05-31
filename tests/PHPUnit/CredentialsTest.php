<?php
/**
 * Tests for the credentials storage mechanism.
 *
 * @package Airstory
 */

namespace Airstory\Credentials;

use WP_Mock as M;
use Mockery;
use WP_Error;

/**
 * @requires extension openssl
 * @requires extension mcrypt
 */
class CredentialsTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'credentials.php',
		'settings.php',
	);

	public function testGetIv() {
		$iv = get_iv();

		$this->assertEquals( 16, strlen( $iv ) );
	}

	public function testGetIvIsNotBinary() {
		$iv = get_iv();

		$this->assertTrue( ctype_print( $iv ), 'WordPress will not store binary data, so we must run the IV through bin2hex()' );
	}

	/**
	 * @requires extension openssl
	 */
	public function testSetToken() {
		$token     = uniqid();
		$iv        = '1234567890123456';
		$encrypted = openssl_encrypt( $token, AIRSTORY_ENCRYPTION_ALGORITHM, AUTH_KEY, null, $iv );

		M::userFunction( __NAMESPACE__ . '\get_iv', array(
			'return' => $iv,
		) );

		M::userFunction( 'Airstory\Settings\set_user_data', array(
			'times'  => 1,
			'args'   => array( 123, 'user_token', array( 'token' => $encrypted, 'iv' => $iv ) ),
			'return' => true,
		) );

		$this->assertEquals( $encrypted, set_token( 123, $token ), 'The same string, encrypted twice with the same arguments, should produce the same result' );
	}

	/**
	 * @requires extension openssl
	 */
	public function testGetToken() {
		$token     = uniqid();
		$iv        = '1234567890123456';
		$encrypted = openssl_encrypt( $token, AIRSTORY_ENCRYPTION_ALGORITHM, AUTH_KEY, null, $iv );

		M::userFunction( 'get_user_by', array(
			'args'   => array( 'ID', 123 ),
			'return' => new \stdClass,
		) );

		M::userFunction( 'Airstory\Settings\get_user_data', array(
			'times'  => 1,
			'args'   => array( 123, 'user_token', false ),
			'return' => array(
				'token' => $encrypted,
				'iv'    => $iv,
			),
		) );

		M::passthruFunction( 'sanitize_text_field', array(
			'times'  => 1,
		) );

		$this->assertEquals( $token, get_token( 123 ), 'The same string, encrypted twice with the same arguments, should produce the same result' );
	}

	public function testClearToken() {
		M::userFunction( 'Airstory\Settings\set_user_data', array(
			'times'  => 1,
			'args'   => array( 123, 'user_token', null ),
			'return' => true,
		) );

		$this->assertTrue( clear_token( 123 ) );
	}
}
