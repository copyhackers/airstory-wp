<?php
/**
 * Tests for the connection functionality.
 *
 * @package Airstory
 */

namespace Airstory\Connection;

use WP_Mock as M;
use Mockery;
use Patchwork;
use WP_Error;
use WP_User_Query;

class ConnectionTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'connection.php',
	);

	public function tearDown() {
		Patchwork\undoAll();
		WP_User_Query::tearDown();

		parent::tearDown();
	}

	public function testGetUserProfile() {
		$response = new \stdClass;
		$response->id         = 'uXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$response->first_name = 'Leroy';
		$response->last_name  = 'Jenkins';
		$response->email      = 'leroy.jenkins@example.com';
		$response->created    = time();

		Patchwork\replace( 'Airstory\API::get_user', function () use ( $response ) {
			return $response;
		} );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals(
			array(
				'user_id'    => $response->id,
				'first_name' => $response->first_name,
				'last_name'  => $response->last_name,
				'email'      => $response->email,
			),
			get_user_profile(),
			'More data than is required appears to be leaking from the /user endpoint'
		);
	}

	public function testGetUserProfileReturnsEmptyArrayIfAPIResponseFails() {
		Patchwork\replace( 'Airstory\API::get_user', function () {
			return new WP_Error();
		} );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertEquals( array(), get_user_profile(), 'WP_Errors should produce empty profile arrays' );
	}

	public function testRegisterConnection() {
		Patchwork\replace( 'Airstory\API::post_target', function () {
			return 'connection-id';
		} );

		M::userFunction( __NAMESPACE__ . '\get_user_profile', array(
			'return' => array(
				'email' => 'test@example.com',
			),
		) );

		M::userFunction( 'get_bloginfo', array(
			'return' => 'My site',
		) );

		M::userFunction( 'get_rest_url', array(
			'return' => 'https://example.com/webhook',
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'update_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_profile', array( 'email' => 'test@example.com' ), true ),
		) );

		M::userFunction( 'update_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_target', 'connection-id' ),
		) );

		M::expectAction( 'airstory_register_connection', 123, 'connection-id', array(
			'identifier' => '123',
			'name'       => 'My site',
			'url'        => 'https://example.com/webhook',
		) );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 'connection-id', register_connection( 123 ) );
	}

	public function testRegisterConnectionReturnsEarlyIfNoProfileDataFound() {
		M::userFunction( __NAMESPACE__ . '\get_user_profile', array(
			'return' => array(),
		) );

		$this->assertNull( register_connection( 123 ) );
	}

	public function testRegisterConnectionHandlesWPErrors() {
		$response = new WP_Error();

		Patchwork\replace( 'Airstory\API::post_target', function () use ( $response ) {
			return $response;
		} );

		M::userFunction( __NAMESPACE__ . '\get_user_profile', array(
			'return' => array(
				'email' => 'test@example.com',
			),
		) );

		M::userFunction( 'get_bloginfo', array(
			'return' => 'My site',
		) );

		M::userFunction( 'get_rest_url', array(
			'return' => 'https://example.com/webhook',
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertNull( register_connection( 123 ) );
	}

	public function testRemoveConnection() {
		$connection_id = uniqid();
		$profile       = array(
			'email' => 'test@example.com',
		);

		Patchwork\replace( 'Airstory\API::delete_target', function () use ( $connection_id ) {
			return $connection_id;
		} );

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_profile', 123 ),
			'return' => $profile,
		) );

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_target', 123 ),
			'return' => $connection_id,
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_profile', true ),
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_target' ),
		) );

		M::expectAction( 'airstory_remove_connection', 123, $connection_id );

		remove_connection( 123 );
	}

	public function testRemoveConnectionOnlyDeletesIfItHasTheUserEmail() {
		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_profile', 123 ),
			'return' => array( 'email' => '' )
		) );

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_target', 123 ),
			'return' => uniqid(),
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 0,
		) );

		remove_connection( 123 );
	}

	public function testRemoveConnectionOnlyDeletesIfItHasTheConnectionID() {
		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_profile', 123 ),
			'return' => array( 'email' => 'test@example.com' ),
		) );

		M::userFunction( 'get_user_option', array(
			'args'   => array( '_airstory_target', 123 ),
			'return' => null,
		) );

		M::userFunction( 'delete_user_option', array(
			'times'  => 0,
		) );

		remove_connection( 123 );
	}
}

/**
 * Mock representation of the API class.
 */
class API {
	public static $response;

	public function __call( $name, $args ) {
		return self::$response;
	}
}
