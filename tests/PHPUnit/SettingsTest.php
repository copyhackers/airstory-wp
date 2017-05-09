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

	public function testRenderProfileSettingsOnlyShowsSiteListIfUserIsMemberOfMoreThanOneSite() {
		$user = new \stdClass;
		$user->ID = 5;

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

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_token', 123 ),
			'return' => 'my-old-token',
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

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_token', 123 ),
			'return' => 'my-old-token',
		) );

		M::userFunction( 'Airstory\Connection\set_connected_blogs', array(
			'times'  => 1,
			'args'   => array( 123, array() ),
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_profile', true ),
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
