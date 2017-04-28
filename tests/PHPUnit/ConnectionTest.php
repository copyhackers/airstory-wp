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

	public function testGetConnectionUserId() {
		$connection_id = uniqid();
		WP_User_Query::$__results = array( 123 );

		$result = get_connection_user_id( $connection_id );

		// Ensure the result we get back matches what we fed into WP_User_Query::$__results.
		$this->assertEquals( 123, $result );

		// Check other arguments.
		$args = WP_User_Query::$__query;
		$this->assertEquals( array(
			'key'   => '_airstory_target',
			'value' => $connection_id,
		), $args['meta_query'], 'The WP_User_Query should look for a matching user based on the _airstory_target meta key' );
		$this->assertEquals( 1, $args['number'], 'The WP_User_Query should be limited to a single user' );
		$this->assertEquals( 'ID', $args['fields'], 'The WP_User_Query should only return user ID(s)' );
		$this->assertFalse( $args['count_total'], 'We\'re looking for a single user, no need to count the totals' );
	}

	public function testGetConnectionUserIdReturnsZeroIfNoMatchFound() {
		WP_User_Query::$__results = array(); // Ensure this is empty.

		$this->assertEquals( 0, get_connection_user_id( uniqid() ) );
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

		M::userFunction( 'update_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_profile', array( 'email' => 'test@example.com' ) ),
		) );

		M::userFunction( 'update_user_meta', array(
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

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_profile', true ),
			'return' => $profile,
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_target', true ),
			'return' => $connection_id,
		) );

		M::userFunction( 'delete_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_profile', array( 'email' => 'test@example.com' ) ),
		) );

		M::userFunction( 'delete_user_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_target', $connection_id ),
		) );

		M::expectAction( 'airstory_remove_connection', 123, $connection_id );

		remove_connection( 123 );
	}

	public function testRemoveConnectionOnlyDeletesIfItHasTheUserEmail() {
		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_profile', true ),
			'return' => array( 'email' => '' )
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_target', true ),
			'return' => uniqid(),
		) );

		M::userFunction( 'delete_user_meta', array(
			'times'  => 0,
		) );

		remove_connection( 123 );
	}

	public function testRemoveConnectionOnlyDeletesIfItHasTheConnectionID() {
		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_profile', true ),
			'return' => array( 'email' => 'test@example.com' ),
		) );

		M::userFunction( 'get_user_meta', array(
			'args'   => array( 123, '_airstory_target', true ),
			'return' => null,
		) );

		M::userFunction( 'delete_user_meta', array(
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
