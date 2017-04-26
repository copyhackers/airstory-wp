<?php
/**
 * Tests for the plugin settings.
 *
 * @package Airstory
 */

namespace Airstory\Settings;

use WP_Mock as M;
use Mockery;

class SettingsTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'settings.php',
	);

	public function testSaveProfileSettings() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
			'airstory-token'  => 'my-secret-token',
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => true,
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_token', true ),
			'return' => 'my-old-token',
		) );

		M::userFunction( 'update_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_token', 'my-secret-token', 'my-old-token' ),
			'return' => true,
		) );

		M::expectAction( 'airstory_user_connect', 123 );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertTrue( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsChecksForNonce() {
		$_POST = array();

		$this->assertFalse( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsVerifiesNonce() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => false,
		) );

		$this->assertFalse( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsVerifiesPermissions() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => false,
		) );

		$this->assertFalse( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsCanDelete() {
		$_POST = array(
			'_airstory_nonce'     => 'abc123',
			'airstory-disconnect' => true,
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => true,
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_token', true ),
			'return' => 'my-old-token',
		) );

		M::userFunction( 'delete_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_token', 'my-old-token' ),
			'return' => true,
		) );

		M::expectAction( 'airstory_user_disconnect', 123 );

		$this->assertTrue( save_profile_settings( 123 ) );
	}
}
