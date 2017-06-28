<?php
/**
 * Tests for the plugin tools.
 *
 * @package Airstory
 */

namespace Airstory\Tools;

use WP_Mock as M;
use Mockery;

class ToolsTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'tools.php',
	);

	public function testRegisterMenuPage() {
		M::userFunction( 'add_submenu_page', array(
			'times'  => 1,
			'args'   => array( 'tools.php', '*', '*', 'manage_options', 'airstory', __NAMESPACE__ . '\render_tools_page' ),
		) );

		register_menu_page();
	}

	/**
 	 * @requires extension dom
 	 * @requires extension mcrypt
 	 * @requires extension openssl
 	 */
	public function testCheckCompatibility() {
		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertTrue( $compatibility['compatible'], 'The compatibility array should include a single go/no-go for compatibility' );
		$this->assertArrayHasKey( 'details', $compatibility, 'The compatibility array should include details for each dependency' );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithPHPVersion() {
		M::userFunction( __NAMESPACE__ . '\version_compare', array(
			'return' => false,
		) );

		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['php'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithHttps() {
		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => false,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['https'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithLibxml() {
		M::userFunction( __NAMESPACE__ . '\version_compare', array(
			'return_in_order' => array( true, false ),
		) );

		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['libxml'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithDom() {
		M::userFunction( __NAMESPACE__ . '\extension_loaded', array(
			'return' => false,
		) );

		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['dom'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithMcrypt() {
		M::userFunction( __NAMESPACE__ . '\extension_loaded', array(
			'return' => false,
		) );

		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['mcrypt'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithOpenSSL() {
		M::userFunction( __NAMESPACE__ . '\extension_loaded', array(
			'return' => false,
		) );

		M::userFunction( __NAMESPACE__ . '\verify_https_support', array(
			'return' => true,
		) );

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['openssl'] );
	}

	public function testVerifyHttpsSupport() {
		$response = new \stdClass;

		M::userFunction( 'is_ssl', array(
			'return' => false,
		) );

		M::userFunction( 'get_rest_url', array(
			'args'   => array( null, '/airstory/v1', 'https' ),
			'return' => 'https://example.com/airstory/v1',
		) );

		M::userFunction( 'wp_remote_request', array(
			'args'   => array( 'https://example.com/airstory/v1', array(
				'method'    => 'HEAD',
				'sslverify' => false,
			) ),
			'return' => $response,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_remote_retrieve_response_code', array(
			'args'   => array( $response ),
			'return' => 200,
		) );

		$this->assertTrue( verify_https_support() );
	}

	public function testVerifyHttpsSupportReturnsEarlyIfAlreadyOnSSL() {
		M::userFunction( 'is_ssl', array(
			'return' => true,
		) );

		$this->assertTrue( verify_https_support() );
	}

	public function testVerifyHttpsSupportReturnsFalseIfWPError() {
		$response = new \stdClass;

		M::userFunction( 'is_ssl', array(
			'return' => false,
		) );

		M::userFunction( 'get_rest_url' );
		M::userFunction( 'wp_remote_request' );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertFalse( verify_https_support() );
	}
}
