<?php
/**
 * Tests for the webhook endpoint.
 *
 * @package Airstory
 */

namespace Airstory\Webhook;

use WP_Mock as M;
use Mockery;
use WP_Error;

class WebhookTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'class-api.php',
		'webhook.php',
	);

	public function testRegisterWebhookEndpoint() {
		M::userFunction( 'register_rest_route', array(
			'times'  => 1,
			'return' => function ( $namespace, $route, $args ) {
				if ( 'airstory/v1' !== $namespace ) {
					$this->fail( 'Airstory functionality is expected to be within the "airstory/v1" namespace' );

				} elseif ( '/webhook' !== $route ) {
					$this->fail( 'The Airstory webhook should be at route /webhook' );

				} elseif ( 'POST' !== $args['methods'] ) {
					$this->fail( 'The Airstory webhook should only be available via HTTP POST requests' );
				}
			},
		) );

		register_webhook_endpoint();
	}

	public function testHandleWebhook() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$request  = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'identifier' )
			->andReturn( 5 );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'project' )
			->andReturn( $project );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'document' )
			->andReturn( $document );

		M::expectFilterAdded( 'rest_pre_serve_request', __NAMESPACE__ . '\override_cors_headers' );

		M::userFunction( 'Airstory\Credentials\get_token', array(
			'args'   => array( 5 ),
			'return' => uniqid(),
		) );

		M::userFunction( 'Airstory\Core\get_current_draft', array(
			'args'   => array( $project, $document ),
			'return' => 0,
		) );

		M::userFunction( 'Airstory\Core\create_document', array(
			'args'   => array( M\Functions::type( 'Airstory\API' ), $project, $document, 5 ),
			'return' => 123,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'add_query_arg', array(
			'return' => 'edit?id=123',
		) );

		M::userFunction( 'admin_url', array(
			'return' => 'http://example.com/edit?id=123',
		) );

		$response = handle_webhook( $request );

		$this->assertEquals( $project, $response['project'], 'The project UUID should be included in the return value' );
		$this->assertEquals( $document, $response['document'], 'The document UUID should be included in the return value' );
		$this->assertEquals( 123, $response['post_id'], 'The post ID should be included in the return value' );
		$this->assertEquals( 'http://example.com/edit?id=123', $response['edit_url'], 'The post edit URL should be returned' );
	}

	public function testHandleWebhookChecksThatIdentifierIsSet() {
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( null );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( 'project' );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( 'doc' );

		$this->assertInstanceOf( 'WP_Error', handle_webhook( $request ) );
	}

	public function testHandleWebhookChecksThatProjectIsSet() {
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( null );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( 'doc' );

		$this->assertInstanceOf( 'WP_Error', handle_webhook( $request ) );
	}

	public function testHandleWebhookChecksThatDocumentIsSet() {
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( 'project' );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( null );

		$this->assertInstanceOf( 'WP_Error', handle_webhook( $request ) );
	}

	public function testHandleWebhookCatchesWPErrorsWhenRetrievingUserToken() {
		$error = new WP_Error;

		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( 'project' );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( 'doc' );

		M::userFunction( 'Airstory\Credentials\get_token', array(
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $error ),
			'return' => true,
		) );

		$this->assertEquals( $error, handle_webhook( $request ) );
	}

	public function testHandleWebhookReturnsWPErrorWhenUserTokenIsEmpty() {
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( 'project' );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( 'doc' );

		M::userFunction( 'Airstory\Credentials\get_token', array(
			'return' => '',
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		$response = handle_webhook( $request );

		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 'airstory-missing-token', $response->get_error_code() );
	}

	public function testHandleWebhookUpdatesExistingDocs() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( $project );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( $document );

		M::userFunction( 'Airstory\Core\get_current_draft', array(
			'return' => 123,
		) );

		M::userFunction( 'Airstory\Core\update_document', array(
			'args'   => array( M\Functions::type( 'Airstory\API' ), $project, $document, 123 ),
			'return' => 123,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'add_query_arg' );
		M::userFunction( 'admin_url' );

		$response = handle_webhook( $request );
	}

	public function testHandleWebhookHandlesWPErrors() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$request  = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )->with( 'identifier' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( $project );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( $document );
		$wp_error = new WP_Error;

		M::userFunction( 'Airstory\Credentials\get_token', array(
			'return' => 'user-token',
		) );

		M::userFunction( 'Airstory\Core\get_current_draft', array(
			'return' => 0,
		) );

		M::userFunction( 'Airstory\Core\create_document', array(
			'return' => $wp_error,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( 'user-token' ),
			'return' => false,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $wp_error ),
			'return' => true,
		) );

		$this->assertEquals( $wp_error, handle_webhook( $request ) );
	}

	/**
	 * @runInSeparateProcess
	 * @requires extension xdebug
	 */
	public function testOverrideCorsHeaders() {
		$value = uniqid();

		$this->assertEquals(
			$value,
			override_cors_headers( $value ),
			'The $served value should not be modified.'
		);

		$this->assertContains(
			'Access-Control-Allow-Origin: https://app.airstory.co',
			xdebug_get_headers(),
			'The Access-Control-Allow-Origin header should be set.'
		);
	}

	/**
	 * @runInSeparateProcess
	 * @requires extension xdebug
	 */
	public function testOverrideCorsHeadersCanAcceptOtherOrigins() {
		M::onFilter( 'airstory_webhook_cors_origin' )
			->with( array( 'https://app.airstory.co' ) )
			->reply( array(
				'https://app.airstory.co',
				'https://example.com'
			) );

		override_cors_headers( false );

		$this->assertContains(
			'Access-Control-Allow-Origin: https://app.airstory.co https://example.com',
			xdebug_get_headers(),
			'The Access-Control-Allow-Origin header value should be filterable, and be capable of imploding an array.'
		);
	}
}
