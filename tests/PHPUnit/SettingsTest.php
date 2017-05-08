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
		'connection.php',
		'settings.php',
	);

	public function testGetUserData() {
		M::userFunction( 'get_user_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_data', 5 ),
			'return' => array( 'some-key' => 'foo' ),
		) );

		M::passthruFunction( 'sanitize_title' );

		$this->assertEquals( 'foo', get_user_data( 5, 'some-key' ) );
	}

	public function testGetUserDataUsesDefaultForUndefinedValues() {
		$default = uniqid();

		M::userFunction( 'get_user_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_data', 5 ),
			'return' => array( 'some-key' => 'foo' ),
		) );

		M::passthruFunction( 'sanitize_title' );

		$this->assertEquals( $default, get_user_data( 5, 'some-different-key', $default ) );
	}

	public function testGetUserDataUsesDefaultForOnlyNullValues() {
		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_data', 5 ),
			'return' => array(
				'value_exists'   => 'foo',
				'value_is_null'  => null,
				'value_is_false' => false,
			),
		) );

		M::passthruFunction( 'sanitize_title' );

		$this->assertEquals( 'foo', get_user_data( 5, 'value_exists', 'bar' ) );
		$this->assertEquals( 'bar', get_user_data( 5, 'value_is_null', 'bar' ) );
		$this->assertFalse( get_user_data( 5, 'value_is_false', 'bar' ) );
	}

	public function testSetUserData() {
		M::userFunction( 'get_user_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_data', 5 ),
			'return' => array( 'some-key' => 'foo' ),
		) );

		M::userFunction( 'update_user_option', array(
			'times'  => 1,
			'args'   => array( 5, '_airstory_data', array( 'some-key' => 'bar' ), true ),
			'return' => true,
		) );

		M::passthruFunction( 'sanitize_title' );

		$this->assertTrue( set_user_data( 5, 'some-key', 'bar' ) );
	}

	public function testSetUserDataCanAddNewKeys() {
		M::userFunction( 'get_user_option', array(
			'times'  => 1,
			'args'   => array( '_airstory_data', 5 ),
			'return' => array( 'some-key' => 'foo' ),
		) );

		M::userFunction( 'update_user_option', array(
			'times'  => 1,
			'args'   => array( 5, '_airstory_data', array(
				'some-key'           => 'foo',
				'some-different-key' => 'bar',
			), true ),
			'return' => true,
		) );

		M::passthruFunction( 'sanitize_title' );

		$this->assertTrue( set_user_data( 5, 'some-different-key', 'bar' ) );
	}

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

		M::userFunction( __NAMESPACE__ . '\get_user_data', array(
			'args'   => array( 123, 'user_token', false ),
			'return' => array( 'token' => 'my-old-token' ),
		) );

		M::userFunction( 'Airstory\Credentials\set_token', array(
			'times'  => 1,
			'args'   => array( 123, 'my-secret-token' ),
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

		M::userFunction( __NAMESPACE__ . '\get_user_data', array(
			'args'   => array( 123, 'user_token', '*' ),
			'return' => array( 'token' => 'my-old-token' ),
		) );

		M::userFunction( 'Airstory\Credentials\clear_token', array(
			'times'  => 1,
			'args'   => array( 123 ),
			'return' => true,
		) );

		M::expectAction( 'airstory_user_disconnect', 123 );

		$this->assertTrue( save_profile_settings( 123 ) );
	}

	public function testGetAvailableBlogs() {
		$site1 = new \stdClass;
		$site1->blogname = 'First site';
		$site2 = new \stdClass;
		$site2->blogname = 'Second site';

		M::userFunction( 'get_blogs_of_user', array(
			'args'   => array( 5 ),
			'return' => array( '1' => $site1, '2' => $site2 ),
		) );

		M::userFunction( 'switch_to_blog', array(
			'times'  => 2,
		) );

		M::userFunction( 'restore_current_blog', array(
			'times'  => 2,
		) );

		M::userFunction( 'user_can', array(
			'args'   => array( 5, 'edit_posts' ),
			'return' => true,
		) );

		M::userFunction( 'Airstory\Connection\has_connection', array(
			'args'            => array( 5 ),
			'return_in_order' => array( true, false ),
		) );

		$this->assertEquals( array(
			array(
				'id'        => 1,
				'title'     => 'First site',
				'connected' => true,
			),
			array(
				'id'        => 2,
				'title'     => 'Second site',
				'connected' => false,
			),
		), get_available_blogs( 5 ) );
	}

	public function testGetAvailableBlogsFiltersOutSitesWhereUserCannotCreatePosts() {
		$site1 = new \stdClass;
		$site1->blogname = 'First site';
		$site2 = new \stdClass;
		$site2->blogname = 'Second site';

		M::userFunction( 'get_blogs_of_user', array(
			'return' => array( '1' => $site1, '2' => $site2 ),
		) );

		M::userFunction( 'switch_to_blog' );
		M::userFunction( 'restore_current_blog' );

		M::userFunction( 'user_can', array(
			'return_in_order' => array( true, false ),
		) );

		$this->assertCount( 1, get_available_blogs( 5 ) );
	}
}
