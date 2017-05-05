<?php
/**
 * Tests for the webhook endpoint.
 *
 * @package Airstory
 */

namespace Airstory\Webhook;

use WP_Mock as M;
use Mockery;

class WebhookTest extends \Airstory\TestCase {

	protected $testFiles = array(
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
			->with( 'id' )
			->andReturn( 5 );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'project' )
			->andReturn( $project );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'document' )
			->andReturn( $document );

		M::userFunction( 'Credentials\Connection\get_token', array(
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

	public function testHandleWebhookUpdatesExistingDocs() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$request = Mockery::mock( 'WP_REST_Request' )->makePartial();
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'id' )
			->andReturn( 5 );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'project' )
			->andReturn( $project );
		$request->shouldReceive( 'get_param' )
			->once()
			->with( 'document' )
			->andReturn( $document );

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
		$request->shouldReceive( 'get_param' )->with( 'id' )->andReturn( 5 );
		$request->shouldReceive( 'get_param' )->with( 'project' )->andReturn( $project );
		$request->shouldReceive( 'get_param' )->with( 'document' )->andReturn( $document );
		$wp_error = Mockery::mock( 'WP_Error' );

		M::userFunction( 'Airstory\Core\get_current_draft', array(
			'return' => 0,
		) );

		M::userFunction( 'Airstory\Core\create_document', array(
			'return' => $wp_error,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertEquals( $wp_error, handle_webhook( $request ) );
	}
}
