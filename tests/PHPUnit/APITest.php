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
use WP_Error;

class APITest extends \Airstory\TestCase {

	protected $testFiles = array(
		'class-api.php',
	);

	public function setUp() {
		M::userFunction( 'wp_json_encode', array(
			'return' => function ( $data ) {
				return json_encode( $data );
			},
		) );

		parent::setUp();
	}

	public function testGetProject() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$result   = uniqid();
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/projects/' . $project )
			->andReturn( array( 'body' => "{\"$result\"}" ) );
		$instance->shouldReceive( 'decode_json_response' )->andReturn( $result );

		$this->assertEquals( $result, $instance->get_project( $project ) );
	}

	public function testGetDocument() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$result   = uniqid();
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/projects/' . $project . '/documents/' . $document )
			->andReturn( array( 'body' => "{\"$result\"}" ) );
		$instance->shouldReceive( 'decode_json_response' )->andReturn( $result );

		$this->assertEquals( $result, $instance->get_document( $project, $document ) );
	}

	public function testGetDocumentContent() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$result   = uniqid();
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/projects/' . $project . '/documents/' . $document . '/content' )
			->andReturn( array( 'body' => $result ) );

		M::userFunction( 'wp_remote_retrieve_body', array(
			'args'   => array( array( 'body' => $result ) ),
			'return' => $result,
		) );

		$this->assertEquals( $result, $instance->get_document_content( $project, $document ) );
	}

	public function testGetUser() {
		$result   = uniqid();
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/user' )
			->andReturn( array( 'body' => "{\"$result\"}" ) );
		$instance->shouldReceive( 'decode_json_response' )->andReturn( $result );

		$this->assertEquals( $result, $instance->get_user() );
	}

	public function testPostTarget() {
		$target   = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$email    = 'test@example.com';
		$data     = array(
			'identifier' => 123,
			'name'       => 'Test Site',
			'url'        => 'http://example.com/webhook',
		);
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->andReturn( array( 'headers' => array( 'link' => $target ) ) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		$this->assertEquals( $target, $instance->post_target( $email, $data ) );
	}

	public function testPostTargetReturnsWPErrors() {
		$error = new WP_Error;

		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->andReturn( $error );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertSame( $error, $instance->post_target( 'test@example.com', array() ) );
	}

	public function testPostTargetReturnsWPErrorIfLinkHeaderNotFound() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->andReturn( array( 'headers' => array() ) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		$response = $instance->post_target( 'test@example.com', array() );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'airstory-link', $response->get_error_code() );
	}

	public function testDeleteTarget() {
		$target   = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$email    = 'test@example.com';
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'make_authenticated_request' )
			->once()
			->with( '/users/' . $email . '/targets/' . $target, array( 'method' => 'DELETE' ) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		$this->assertEquals( $target, $instance->delete_target( $email, $target ) );
	}

	/**
	 * @runInSeparateProcess to avoid collision with other calls to get_token().
	 */
	public function testGetCredentials() {
		$instance = new API;
		$method   = new ReflectionMethod( $instance, 'get_credentials' );
		$method->setAccessible( true );
		$user     = new \stdClass;
		$user->ID = 123;

		M::userFunction( 'wp_get_current_user', array(
			'return' => $user,
		) );

		M::userFunction( 'Airstory\Credentials\get_token', array(
			'args'   => array( 123 ),
			'return' => 'my-unencrypted-token',
		) );

		$this->assertEquals( 'my-unencrypted-token', $method->invoke( $instance ) );
	}

	public function testMakeAuthenticatedRequest() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( 'abc123' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );
		$uniqid = uniqid();

		M::userFunction( 'wp_parse_args', array(
			'return' => array( 'headers' => array() ),
		) );

		M::userFunction( 'wp_remote_request', array(
			'times'  => 1,
			'return' => function ( $url, $args ) use ( $uniqid ) {
				if ( ! isset( $args['headers']['Authorization'] ) ) {
					$this->fail( 'Method is not injecting Authorization header' );

				} elseif ( 'Bearer=abc123' !== $args['headers']['Authorization'] ) {
					$this->fail( 'Response from get_credentials is not being set as the Authorization header' );
				}

				return array( 'body' => $uniqid );
			}
		) );

		$response = $method->invoke( $instance, '/some-route' );

		$this->assertEquals( array( 'body' => $uniqid ), $response );
	}

	public function testMakeAuthenticatedRequestWithArgs() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$instance->shouldReceive( 'get_credentials' )->andReturn( 'abc123' );
		$method   = new ReflectionMethod( $instance, 'make_authenticated_request' );
		$method->setAccessible( true );
		$uniqid = uniqid();

		M::userFunction( 'wp_parse_args', array(
			'return_arg' => 0,
		) );

		M::userFunction( 'wp_remote_request', array(
			'times'  => 1,
			'return' => function ( $url, $args ) use ( $uniqid ) {
				if ( 'POST' !== $args['method'] ) {
					$this->fail( 'User-provided args should take precedence over our defaults' );
				}

				return array( 'body' => $uniqid );
			}
		) );

		$response = $method->invoke( $instance, '/some-route', array( 'method' => 'POST', 'headers' => array() ) );

		$this->assertEquals( array( 'body' => $uniqid ), $response );
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

		M::userFunction( 'wp_parse_args', array(
			'return' => array( 'headers' => array() ),
		) );

		M::userFunction( 'wp_remote_request', array(
			'times'  => 1,
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $error ),
			'return' => true,
		) );

		$this->assertEquals( $error, $method->invoke( $instance, '/some-route' ) );
	}

	public function testDecodeJsonResponse() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$method   = new ReflectionMethod( $instance, 'decode_json_response' );
		$method->setAccessible( true );

		M::userFunction( 'wp_remote_retrieve_body', array(
			'return' => '{"foo": "bar"}',
		) );

		$response = $method->invoke( $instance, array( 'body' => '{"foo": "bar"}' ) );

		$this->assertEquals( 'bar', $response->foo );
	}

	public function testDecodeJsonResponseReturnsWPErrorOnParseError() {
		$instance = Mockery::mock( __NAMESPACE__ . '\API' )->shouldAllowMockingProtectedMethods()->makePartial();
		$method   = new ReflectionMethod( $instance, 'decode_json_response' );
		$method->setAccessible( true );

		M::userFunction( 'wp_remote_retrieve_body', array(
			'return' => '{this is "invalid" JSON}',
		) );

		$response = $method->invoke( $instance, array( 'body' => '{this is "invalid" JSON}' ) );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'airstory-invalid-json', $response->get_error_code() );
	}
}
