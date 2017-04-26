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

class CredentialsTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'credentials.php',
	);

	/**
	 * @runInSeparateProcess
	 */
	public function testGetEncryptionKey() {
		$key = uniqid();
		define( 'AIRSTORY_ENCRYPTION_KEY', $key );

		$this->assertEquals( $key, get_encryption_key() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetEncryptionKeyDefaultsToAuthKey() {
		$key = uniqid();
		define( 'AUTH_KEY', $key );

		$this->assertFalse( defined( 'AIRSTORY_ENCRYPTION_KEY' ), 'AIRSTORY_ENCRYPTION_KEY may be polluted from another test!' );
		$this->assertEquals( $key, get_encryption_key() );
	}

	public function testGetIv() {
		$iv = get_iv();

		$this->assertEquals( 16, strlen( $iv ) );
	}

	/**
	 * @requires extension openssl
	 */
	public function testSetToken() {
		$token     = uniqid();
		$iv        = '1234567890123456';
		$encrypted = openssl_encrypt( $token, AIRSTORY_ENCRYPTION_ALGORITHM, 'secret_key', null, $iv );

		M::userFunction( __NAMESPACE__ . '\get_encryption_key', array(
			'return' => 'secret_key',
		) );

		M::userFunction( __NAMESPACE__ . '\get_iv', array(
			'return' => $iv,
		) );

		M::userFunction( 'update_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_token', $encrypted ),
		) );

		M::userFunction( 'update_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_iv', $iv ),
		) );

		$this->assertEquals( $encrypted, set_token( 123, $token ), 'The same string, encrypted twice with the same arguments, should produce the same result' );
	}

	/**
	 * @requires extension openssl
	 */
	public function testGetToken() {
		$token     = uniqid();
		$iv        = '1234567890123456';
		$encrypted = openssl_encrypt( $token, AIRSTORY_ENCRYPTION_ALGORITHM, 'secret_key', null, $iv );

		M::userFunction( __NAMESPACE__ . '\get_encryption_key', array(
			'return' => 'secret_key',
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_token', true ),
			'return' => $encrypted,
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_iv', true ),
			'return' => $iv,
		) );

		$this->assertEquals( $token, get_token( 123 ), 'The same string, encrypted twice with the same arguments, should produce the same result' );
	}
}
