<?php
/**
 * Tests for the Airstory API wrapper.
 *
 * @package Airstory
 */

namespace Airstory;

use WP_Mock as M;
use Mockery;
use ReflectionMethod;

class APITest extends \Airstory\TestCase {

	protected $testFiles = array(
		'class-api.php',
	);

	public function testGetProject() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$result   = uniqid();
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/projects/' . $project )
			->andReturn( $result );

		$instance->get_project( $project );
	}

	public function testGetCredentials() {
		$this->markTestIncomplete();
	}

	public function testMakeAuthenticatedRequest() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( 'abc123' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );

		M::userFunction( 'wp_remote_get', array(
			'times'  => 1,
			'return' => function ( $url, $args ) {
				if ( ! isset( $args['headers']['Authorization'] ) ) {
					$this->fail( 'Method is not injecting Authorization header' );

				} elseif ( 'Bearer=abc123' !== $args['headers']['Authorization'] ) {
					$this->fail( 'Response from get_credentials is not being set as the Authorization header' );
				}
			}
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_remote_retrieve_body', array(
			'return' => '{"json":"string"}',
		) );

		$response = $method->invoke( $instance, '/some-route' );

		$this->assertEquals( 'string', $response->json );
	}

	public function testMakeAuthenticatedRequestThrowsWPErrorIfNoCredentialsAvailable() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( '' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );

		$response = $method->invoke( $instance, '/some-route' );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'airstory-missing-credentials', $response->get_error_code() );
	}

	public function testMakeAuthenticatedRequestReturnsWPHTTPError() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( 'abc123' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );
		$error    = new \WP_Error( 'code', 'Something went wrong' );

		M::userFunction( 'wp_remote_get', array(
			'times'  => 1,
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $error ),
			'return' => true,
		) );

		$this->assertEquals( $error, $method->invoke( $instance, '/some-route' ) );
	}

	public function testMakeAuthenticatedRequestReturnsWPErrorWhenJSONResponseIsInvalid() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( 'abc123' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );
		$error    = new \WP_Error( 'code', 'Something went wrong' );

		M::userFunction( 'wp_remote_get', array(
			'times'  => 1,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_remote_retrieve_body', array(
			'return' => '{this is "invalid" JSON}',
		) );

		$response = $method->invoke( $instance, '/some-route' );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'airstory-invalid-json', $response->get_error_code() );
	}
}
