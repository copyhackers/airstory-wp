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

	public function testRenderToolsPage() {
		$this->markTestIncomplete();

		M::passthruFunction( 'esc_html' );

		render_tools_page();
	}

	/**
 	 * @requires extension dom
 	 * @requires extension mcrypt
 	 * @requires extension openssl
 	 */
	public function testCheckCompatibility() {
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

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['php'] );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCheckCompatibilityWithDom() {
		M::userFunction( __NAMESPACE__ . '\extension_loaded', array(
			'return' => false,
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

		$compatibility = check_compatibility();

		$this->assertFalse( $compatibility['compatible'] );
		$this->assertFalse( $compatibility['details']['openssl'] );
	}
}
