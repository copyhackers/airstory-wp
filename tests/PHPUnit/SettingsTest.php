<?php
/**
 * Tests for the plugin settings.
 *
 * @package Airstory
 */

namespace Airstory\Settings;

use WP_Mock as M;
use Mockery;
use WP_Error;

class SettingsTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'connection.php',
		'settings.php',
		'tools.php',
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

	public function testShowUserConnectionNotice() {
		$user = new \stdClass;
		$user->ID = 5;

		M::userFunction( __NAMESPACE__ . '\get_user_data', array(
			'times'  => 1,
			'args'   => array( 5, 'welcome_message_seen', false ),
			'return' => false,
		) );

		M::userFunction( 'wp_get_current_user', array(
			'return' => $user,
		) );

		M::userFunction( 'get_edit_user_link', array(
			'return' => 'http://example.com/profile.php',
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'esc_html_e' );
		M::passthruFunction( 'wp_kses_post' );

		$this->expectOutputRegex( '@http://example.com/profile.php@' );

		show_user_connection_notice();
	}

	public function testShowUserConnectionNoticeDisplaysNothingIfTheUserHasAlreadySeenTheMessage() {
		$user = new \stdClass;
		$user->ID = 5;

		M::userFunction( __NAMESPACE__ . '\get_user_data', array(
			'times'  => 1,
			'args'   => array( 5, 'welcome_message_seen', false ),
			'return' => true,
		) );

		M::userFunction( 'wp_get_current_user', array(
			'return' => $user,
		) );

		$this->expectOutputString( '' );

		show_user_connection_notice();
	}

	public function testRenderProfileSettings() {
		$user = new \stdClass;
		$user->ID = 5;

		M::userFunction( 'Airstory\Tools\check_compatibility', array(
			'return' => array( 'compatible' => true ),
		) );

		M::userFunction( 'get_user_meta', array(
			'return' => null,
		) );

		M::userFunction( 'wp_nonce_field', array(
			'times'  => 1,
			'args'   => array( 'airstory-profile', '_airstory_nonce' ),
		) );

		$this->expectOutputRegex( '/\<h2 id="airstory"\>/', 'The section heading should have an explicit #airstory ID attribute.' );
		$this->expectOutputRegex( '/\<input name="airstory-token"/', 'When get_user_meta returns empty, the user should be shown the airstory-token input.' );

		render_profile_settings( $user );
	}

	public function testRenderProfileSettingsReturnsEarlyIfDependenciesNotMet() {
		$user = new \stdClass;
		$user->ID = 5;

		M::userFunction( 'Airstory\Tools\check_compatibility', array(
			'return' => array( 'compatible' => false ),
		) );

		$this->expectOutputString( '' );

		render_profile_settings( $user );
	}

	public function testRenderProfileSettingsOnlyShowsSiteListIfUserIsMemberOfMoreThanOneSite() {
		$user = new \stdClass;
		$user->ID = 5;

		M::userFunction( 'Airstory\Tools\check_compatibility', array(
			'return' => array( 'compatible' => true ),
		) );

		M::userFunction( __NAMESPACE__ . '\get_available_blogs', array(
			'return' => array( 1 ),
		) );

		M::passthruFunction( 'esc_html' );
		M::passthruFunction( 'wp_nonce_field' );

		ob_start();
		render_profile_settings( $user );

		$this->assertFalse(
			strpos( $this->getActualOutput(), 'name="airstory-sites[]"' ),
			'The list of sites should only be shown if the user is a member of more than one site'
		);
		ob_end_clean();
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

		M::userFunction( 'Airstory\Credentials\set_token', array(
			'times'  => 1,
			'args'   => array( 123, 'my-secret-token' ),
			'return' => true,
		) );

		M::userFunction( 'is_multisite', array(
			'return' => false,
		) );

		M::expectAction( 'airstory_user_connect', 123 );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertTrue( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsWithSiteList() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
			'airstory-token'  => 'my-secret-token',
			'airstory-sites'  => array( 1, 2, 3 ),
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => true,
		) );

		M::userFunction( 'get_user_option', array(
			'return' => 'my-old-token',
		) );

		M::userFunction( 'Airstory\Credentials\set_token', array(
			'return' => true,
		) );

		M::userFunction( 'is_multisite', array(
			'return' => true,
		) );

		M::userFunction( 'Airstory\Connection\set_connected_blogs', array(
			'times'  => 1,
			'args'   => array( 123, array( 1, 2, 3 ) ),
		) );

		M::expectAction( 'airstory_user_connect', 123 );

		M::passthruFunction( 'absint' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertTrue( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsDoesntLoopThroughSitesIfUserIsOnlyMemberOfOne() {
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

		M::userFunction( 'get_user_option', array(
			'return' => 'my-old-token',
		) );

		M::userFunction( 'Airstory\Credentials\set_token', array(
			'return' => true,
		) );

		M::userFunction( 'is_multisite', array(
			'return' => true,
		) );

		M::userFunction( 'Airstory\Connection\set_connected_blogs', array(
			'times'  => 0,
		) );

		M::expectAction( 'airstory_user_connect', 123 );

		M::passthruFunction( 'absint' );
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

	public function testSaveProfileSettingsReturnsEarlyIfTokenIsEmptyAndNotDisconnect() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
			'airstory-token'  => '',
		);

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => false,
		) );

		$this->assertFalse( save_profile_settings( 123 ), 'The function should return early if there is no token to save and we\'re not disconnecting' );
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

		M::userFunction( 'Airstory\Connection\set_connected_blogs', array(
			'times'  => 1,
			'args'   => array( 123, array() ),
		) );

		M::userFunction( 'Airstory\Credentials\clear_token', array(
			'times'  => 1,
			'args'   => array( 123 ),
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_data', true ),
			'return' => true,
		) );

		M::expectAction( 'airstory_user_disconnect', 123 );

		$this->assertTrue( save_profile_settings( 123 ) );
	}

	public function testSaveProfileSettingsCatchesWPErrors() {
		$_POST = array(
			'_airstory_nonce' => 'abc123',
			'airstory-token'  => 'my-secret-token',
		);
		$error = new WP_Error;

		M::userFunction( 'wp_verify_nonce', array(
			'return' => true,
		) );

		M::userFunction( 'current_user_can', array(
			'return' => true,
		) );

		M::userFunction( 'Airstory\Credentials\set_token', array(
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $error ),
			'return' => true,
		) );

		M::expectActionAdded( 'user_profile_update_errors', __NAMESPACE__ . '\profile_error_save_token' );

		$this->assertSame( $error, save_profile_settings( 123 ) );
	}

	public function testProfileErrorSaveToken() {
		$errors = Mockery::mock( 'WP_Error' )->makePartial();
		$errors->shouldReceive( 'add' )->once()->with( 'airstory-save-token', Mockery::any() );

		profile_error_save_token( $errors );
	}

	public function testGetAvailableBlogs() {
		$site1 = new \stdClass;
		$site1->blogname = 'First site';
		$site2 = new \stdClass;
		$site2->blogname = 'Second site';

		M::userFunction( 'is_multisite', array(
			'return' => true,
		) );

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

		M::userFunction( 'is_multisite', array(
			'return' => true,
		) );

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

	public function testGetAvailableBlogsReturnsEarlyIfNotMultisite() {
		M::userFunction( 'is_multisite', array(
			'return' => false,
		) );

		M::userFunction( 'get_blogs_of_user', array(
			'times'  => 0,
		) );

		$this->assertEquals( array(), get_available_blogs( 5 ) );
	}
}
