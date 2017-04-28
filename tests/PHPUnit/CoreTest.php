<?php
/**
 * Tests for the plugin's core functionality.
 *
 * @package Airstory
 */

namespace Airstory\Core;

use WP_Mock as M;
use Mockery;

class CoreTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'core.php',
	);

	public function testImportDocument() {
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
			'return' => 123,
		) );

		M::passthruFunction( 'sanitize_text_field' );
		M::passthruFunction( 'wp_kses_post' );

		M::onFilter( 'airstory_before_insert_content' )
			->with( 'My document body' )
			->reply( 'My filtered body' );

		$this->assertEquals( 123, import_document( $api, $project, $document ) );
	}

	public function testImportDocumentFailsToGetDocument() {
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

		$this->assertSame( $error, import_document( $api, $project, $document ) );
	}

	public function testImportDocumentFailsToGetDocumentContent() {
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

		$this->assertSame( $error, import_document( $api, $project, $document ) );
	}
}
