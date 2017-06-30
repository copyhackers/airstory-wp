<?php
/**
 * Tests for the credentials storage mechanism.
 *
 * @package Airstory
 */

namespace Airstory\Credentials;

use WP_Mock as M;
use InvalidArgumentException;
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

	public function testGetCipherAlgorithm() {
		M::userFunction( __NAMESPACE__ . '\openssl_get_cipher_methods', array(
			'return' => array(
				'AES-256-CTR',
				'AES-256-CFB',
				'AES-128-CFB',
			),
		) );

		M::userFunction( 'get_site_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_cipher_algorithm' ),
		) );

		M::userFunction( 'add_site_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_cipher_algorithm', 'AES-256-CTR' ),
		) );

		$this->assertEquals( 'AES-256-CTR', get_cipher_algorithm() );
	}

	public function testGetCipherAlgorithmCachesResult() {
		$uniqid = uniqid();

		M::userFunction( 'get_site_option', array(
			'return' => $uniqid,
		) );

		$this->assertEquals( $uniqid, get_cipher_algorithm() );
	}

	public function testGetCipherAlgorithmSecondChoice() {
		M::userFunction( __NAMESPACE__ . '\openssl_get_cipher_methods', array(
			'return' => array(
				'AES-256-CFB',
				'AES-128-CFB',
			),
		) );

		M::userFunction( 'get_site_option' );
		M::userFunction( 'add_site_option' );

		$this->assertEquals( 'AES-256-CFB', get_cipher_algorithm() );
	}

	public function testGetCipherAlgorithmThirdChoice() {
		M::userFunction( __NAMESPACE__ . '\openssl_get_cipher_methods', array(
			'return' => array(
				'AES-128-CFB',
			),
		) );

		M::userFunction( 'get_site_option' );
		M::userFunction( 'add_site_option' );

		$this->assertEquals( 'AES-128-CFB', get_cipher_algorithm() );
	}

	/**
	 * @expectException InvalidArgumentException
	 */
	public function testGetCipherAlgorithmThrowsExceptionIfNoCiphersFound() {
		M::userFunction( __NAMESPACE__ . '\openssl_get_cipher_methods', array(
			'return' => array(),
		) );

		M::userFunction( 'get_site_option' );

		$this->expectException( InvalidArgumentException::class );

		get_cipher_algorithm();
	}

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
		$encrypted = openssl_encrypt( $token, 'AES-256-CTR', AUTH_KEY, null, $iv );

		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => 'AES-256-CTR',
		) );

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

	public function testSetTokenReturnsWPErrorIfGetCipherAlgorithmThrowsException() {
		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => function () {
				throw new InvalidArgumentException;
			},
		) );

		$this->assertInstanceOf( 'WP_Error', set_token( 123, 'token' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetTokenReturnsWPErrorIfEncryptionFails() {
		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => 'AES-256-CTR',
		) );

		M::userFunction( __NAMESPACE__ . '\get_iv', array(
			'return' => uniqid(),
		) );

		M::userFunction( __NAMESPACE__ . '\openssl_encrypt', array(
			'return' => false,
		) );

		$this->assertInstanceOf( 'WP_Error', set_token( 123, 'token' ) );
	}

	/**
	 * @requires extension openssl
	 */
	public function testGetToken() {
		$token     = uniqid();
		$iv        = '1234567890123456';
		$encrypted = openssl_encrypt( $token, 'AES-256-CTR', AUTH_KEY, null, $iv );

		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => 'AES-256-CTR',
		) );

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

	public function testGetTokenReturnsWPErrorIfGetCipherAlgorithmThrowsException() {
		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => function () {
				throw new InvalidArgumentException;
			}
		) );

		M::userFunction( 'get_user_by', array(
			'return' => new \stdClass,
		) );

		M::userFunction( 'Airstory\Settings\get_user_data', array(
			'return' => array(
				'token' => uniqid(),
				'iv'    => uniqid(),
			),
		) );

		$this->assertInstanceOf( 'WP_Error', get_token( 123 ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetTokenReturnsWPErrorIfDecryptionFails() {
		M::userFunction( __NAMESPACE__ . '\get_cipher_algorithm', array(
			'return' => 'AES-256-CTR',
		) );

		M::userFunction( 'get_user_by', array(
			'return' => new \stdClass,
		) );

		M::userFunction( 'Airstory\Settings\get_user_data', array(
			'return' => array(
				'token' => uniqid(),
				'iv'    => uniqid(),
			),
		) );

		M::userFunction( __NAMESPACE__ . '\openssl_decrypt', array(
			'return' => false,
		) );

		$this->assertInstanceOf( 'WP_Error', get_token( 123 ) );
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
