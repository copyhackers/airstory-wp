<?php
/**
 * Tests for the plugin's core functionality.
 *
 * @package Airstory
 */

namespace Airstory\Core;

use WP_Mock as M;
use Mockery;
use WP_Query;

class CoreTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'core.php',
		'tools.php',
	);

	public function testGetCurrentDraft() {
		$meta_query = array(
			'_airstory_project_id'  => 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
			'_airstory_document_id' => 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
		);

		WP_Query::$__results = array( 123 );

		M::userFunction( 'wp_parse_args', array(
			'return_arg' => 1,
		) );

		$this->assertEquals( 123, get_current_draft( $meta_query['_airstory_project_id'], $meta_query['_airstory_document_id'] ) );

		// Disect key parts of the resulting query args.
		$this->assertEquals( array( 'draft', 'pending' ), WP_Query::$__query['post_status'], 'get_current_draft() should never retrieve a published post ID' );
		$this->assertEquals( 'ids', WP_Query::$__query['fields'], 'get_current_draft() should only query post IDs' );
		$this->assertEquals( 'date', WP_Query::$__query['orderby'], 'get_current_draft() should only query post IDs' );
		$this->assertEquals( 'ASC', WP_Query::$__query['order'], 'get_current_draft() should only query post IDs' );

		// Break down the meta query
		$this->assertCount( count( $meta_query ), WP_Query::$__query['meta_query'] );

		foreach ( WP_Query::$__query['meta_query'] as $query ) {
			$this->assertEquals( $meta_query[ $query['key'] ], $query['value'] );
		}

		// Reset the WP_Query mock.
		WP_Query::tearDown();
	}

	public function testGetCurrentDraftReturns0IfNoDraftFound() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		WP_Query::$__results = array();

		M::userFunction( 'wp_parse_args', array(
			'return_arg' => 1,
		) );

		$this->assertEquals( 0, get_current_draft( $project, $document ) );

		// Reset the WP_Query mock.
		WP_Query::tearDown();
	}

	public function testCheckForMissingRequirements() {
		global $pagenow;

		$pagenow = 'plugins.php';

		if ( ! defined( 'AIRSTORY_DIR' ) ) {
			define( 'AIRSTORY_DIR', '/path/to' );
		}

		M::userFunction( 'Airstory\Tools\check_compatibility', array(
			'return' => array( 'compatible' => false ),
		) );

		M::expectActionAdded( 'admin_notices', __NAMESPACE__ . '\notify_user_of_missing_requirements' );

		check_for_missing_requirements();
	}

	public function testCheckForMissingRequirementsRequirementsOnlyFiresOnPluginPage() {
		global $pagenow;

		$pagenow = 'not-plugins.php';

		M::userFunction( 'Airstory\Tools\check_compatibility', array(
			'times' => 0,
		) );

		check_for_missing_requirements();
	}

	public function testCreateDocument() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		$doc = new \stdClass;
		$doc->title = 'My sample document';

		$api = Mockery::mock( 'Airstory\API' )->makePartial();
		$api->shouldReceive( 'get_document' )
			->once()
			->andReturn( $doc );
		$api->shouldReceive( 'get_document_content' )
			->once()
			->andReturn( 'My document body' );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_insert_post', array(
			'times'  => 1,
			'return' => function ( $post ) {
				if ( ! is_int( $post['post_author'] ) ) {
					$this->fail( 'The author ID should be explicitly cast as an integer' );
				}

				return 123;
			},
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_project_id', $project, true ),
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 123, '_airstory_document_id', $document, true ),
		) );

		M::passthruFunction( 'sanitize_text_field' );
		M::passthruFunction( 'wp_kses_post' );

		M::onFilter( 'airstory_before_insert_content' )
			->with( 'My document body' )
			->reply( 'My filtered body' );

		M::expectAction( 'airstory_import_post', 123 );

		$this->assertEquals( 123, create_document( $api, $project, $document ) );
	}

	public function testCreateDocumentSetsAuthorId() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		$doc = new \stdClass;
		$doc->title = 'My sample document';

		$api = Mockery::mock( 'Airstory\API' )->makePartial();
		$api->shouldReceive( 'get_document' )->andReturn( $doc );
		$api->shouldReceive( 'get_document_content' )->andReturn( 'My document body' );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_insert_post', array(
			'times'  => 1,
			'return' => function ( $post ) {
				if ( 5 !== $post['post_author'] ) {
					$this->fail( 'The author ID is not being set' );
				}

				return 123;
			},
		) );

		M::userFunction( 'add_post_meta' );
		M::passthruFunction( 'sanitize_text_field' );
		M::passthruFunction( 'wp_kses_post' );

		$this->assertEquals( 123, create_document( $api, $project, $document, 5 ) );
	}

	public function testCreateDocumentFailsToGetDocument() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		$error = new \WP_Error;

		$api = Mockery::mock( 'Airstory\API' )->makePartial();
		$api->shouldReceive( 'get_document' )
			->once()
			->andReturn( $error );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		$this->assertSame( $error, create_document( $api, $project, $document ) );
	}

	public function testCreateDocumentFailsToGetDocumentContent() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		$doc   = new \stdClass;
		$doc->title = 'My sample document';
		$error = new \WP_Error;

		$api = Mockery::mock( 'Airstory\API' )->makePartial();
		$api->shouldReceive( 'get_document' )
			->once()
			->andReturn( $doc );
		$api->shouldReceive( 'get_document_content' )
			->once()
			->andReturn( $error );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $doc ),
			'return' => false,
		) );

		M::userFunction( 'is_wp_error', array(
			'args'   => array( $error ),
			'return' => true,
		) );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertSame( $error, create_document( $api, $project, $document ) );
	}

	public function testUpdateDocument() {
		$project  = 'pXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
		$document = 'dXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

		$api = Mockery::mock( 'Airstory\API' )->makePartial();
		$api->shouldReceive( 'get_document_content' )
			->once()
			->andReturn( 'My document body' );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'wp_update_post', array(
			'times'  => 1,
			'return' => function ( $args ) {
				if ( 123 !== $args['ID'] ) {
					$this->fail( 'The post ID must be passed in to wp_update_post' );

				} elseif ( 'My filtered body' !== $args['post_content'] ) {
					$this->fail( 'The post content does not appear to be filtered by airstory_before_insert_content' );
				}

				return 123;
			},
		) );

		M::passthruFunction( 'sanitize_text_field' );
		M::passthruFunction( 'wp_kses_post' );

		M::onFilter( 'airstory_before_insert_content' )
			->with( 'My document body' )
			->reply( 'My filtered body' );

		M::expectAction( 'airstory_update_post', 123 );

		$this->assertEquals( 123, update_document( $api, $project, $document, 123 ) );
	}
}
