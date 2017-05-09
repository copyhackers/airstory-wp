<?php
/**
 * Tests for Airstory content formatters.
 *
 * @package Airstory
 */

namespace Airstory\Formatting;

use WP_Mock as M;
use Mockery;

/**
 * @require extension dom
 */
class FormattingTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'formatting.php',
	);

	public function testGetBodyContents() {
		$response = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
</head>
<body>
	<h1>This is some content</h1>
	<p>Our job is to clean it up.</p>
</body>
</html>
EOT;
		$expected = <<<EOT
<h1>This is some content</h1>
	<p>Our job is to clean it up.</p>
EOT;

		$this->assertEquals( $expected, get_body_contents( $response ) );
	}

	public function testGetBodyContentsWithOnlyBodyContents() {
		$response = <<<EOT
<h1>This is some content</h1>
<p>Our job is to clean it up.</p>
EOT;

		$this->assertEquals( $response, get_body_contents( $response ) );
	}

	public function testGetBodyContentsWithNoBodyTag() {
		$response = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
</head>
</html>
EOT;
		$this->assertEmpty( get_body_contents( $response ) );
	}

	/**
	 * @runInSeparateProcess Or risk the libxml error buffer getting all kinds of screwy.
	 */
	public function testGetBodyContentsWithInvalidHTML() {
		$response = <<<EOT
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
</body>
</html>
EOT;

		$this->assertEmpty( get_body_contents( $response ) );
	}

	public function testGetBodyContentsDoesNotButcherEmoji() {
		$emoji = '<p>emoji: 😉</p>';

		$this->assertEquals( $emoji, get_body_contents( $emoji ), 'Multi-byte characters like emoji appear to be encoded improperly.' );
	}

	public function testSideloadImages() {
		$content = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="my alt text" /></p>
EOT;
		// DOMDocument uses HTML5-style <img> elements, without the closing slash.
		$expected = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://example.com/image.jpg" alt="my alt text"></p>
EOT;

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'args'   => array( 123 ),
			'return' => $post,
		) );

		M::userFunction( 'media_sideload_image', array(
			'times'  => 1,
			'args'   => array( 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 123, null, 'src' ),
			'return' => 'https://example.com/image.jpg',
		) );

		M::userFunction( __NAMESPACE__ . '\get_attachment_id_by_url', array(
			'times'  => 1,
			'args'   => array( 'https://example.com/image.jpg' ),
			'return' => 125,
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_wp_attachment_image_alt', 'my alt text' ),
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_airstory_origin', 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg' ),
		) );

		M::userFunction( 'wp_update_post', array(
			'times'  => 1,
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 1, sideload_images( 123 ) );
	}

	public function testSideloadImagesDeduplicatesMatches() {
		$content = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="alt text" /></p>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="alt text" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://example.com/image.jpg" alt="alt text"></p>
<p><img src="https://example.com/image.jpg" alt="alt text"></p>
EOT;

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'return' => $post,
		) );

		M::userFunction( 'media_sideload_image', array(
			'times'  => 1,
			'return' => 'https://example.com/image.jpg',
		) );

		M::userFunction( __NAMESPACE__ . '\get_attachment_id_by_url', array(
			'return' => 125,
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_wp_attachment_image_alt', 'alt text' ),
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_airstory_origin', 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg' ),
		) );

		M::userFunction( 'wp_update_post', array(
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 2, sideload_images( 123 ) );
	}

	public function testSideloadImagesHandlesMultipleImages() {
		$content = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="alt text" /></p>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image2.jpg" alt="alt-alt text" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://example.com/image.jpg" alt="alt text"></p>
<p><img src="https://example.com/image2.jpg" alt="alt-alt text"></p>
EOT;

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'return' => $post,
		) );

		M::userFunction( 'media_sideload_image', array(
			'return' => function ( $image_url ) {
				return 'https://example.com/' . basename( $image_url );
			},
		) );

		M::userFunction( __NAMESPACE__ . '\get_attachment_id_by_url', array(
			'times'           => 2,
			'return_in_order' => array( 125, 126 ),
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_wp_attachment_image_alt', 'alt text' ),
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 126, '_wp_attachment_image_alt', 'alt-alt text' ),
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 125, '_airstory_origin', 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg' ),
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 126, '_airstory_origin', 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image2.jpg' ),
		) );

		M::userFunction( 'wp_update_post', array(
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 2, sideload_images( 123 ) );
	}

	public function testSideloadImagesDoesntUpdatePostMetaIfNoMatchingAttachmentIdWasFound() {
		$content = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="my alt text" /></p>
EOT;

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'return' => $post,
		) );

		M::userFunction( 'media_sideload_image', array(
			'return' => 'https://example.com/image.jpg',
		) );

		M::userFunction( __NAMESPACE__ . '\get_attachment_id_by_url', array(
			'return' => 0,
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 0,
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 0,
		) );

		M::userFunction( 'wp_update_post' );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		sideload_images( 123 );
	}

	/**
	 * Media sideloading outside of the wp-admin context requires several files be included.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/media_sideload_image#Notes
	 *
	 * @runInSeparateProcess
	 */
	public function testSideloadImagesLoadsMediaDependencies() {
		$post = new \stdClass;
		$post->post_content = 'nothing to do here';

		M::userFunction( 'get_post', array(
			'return' => $post,
		) );

		sideload_images( 123 );

		$required_files = array(
			ABSPATH . 'wp-admin/includes/media.php',
			ABSPATH . 'wp-admin/includes/file.php',
			ABSPATH . 'wp-admin/includes/image.php',
		);
		$included_files = get_included_files();

		foreach ( $required_files as $file ) {
			$this->assertTrue( in_array( $file, $included_files, true ), 'Missing required dependency for media sideloading: ' . $file );
		}
	}

	public function testSideloadImagesReturnsEarlyIfInvalidPostID() {
		M::userFunction( 'get_post', array(
			'return' => null,
		) );

		$this->assertEquals( 0, sideload_images( 123 ) );
	}

	public function testStripWrappingDiv() {
		$content = <<<EOT
<div>
	<h1>This is content</h1>
</div>
EOT;

		$this->assertEquals( '<h1>This is content</h1>', strip_wrapping_div( $content ) );
	}

	public function testStripWrappingDivAcrossMultipleLines() {
		$content = <<<EOT
<div>
	<h1>This is content</h1>
	<p>Blah blah blah</p>

	<h2>Here's some more!</h2>
</div>
EOT;
		$expected = <<<EOT
<h1>This is content</h1>
	<p>Blah blah blah</p>

	<h2>Here's some more!</h2>
EOT;

		$this->assertEquals( $expected, strip_wrapping_div( $content ) );
	}

	public function testStripWrappingDivDoesNotAffectRegularContent() {
		$content = '<h1>This is content</h1>';

		$this->assertEquals( $content, strip_wrapping_div( $content ) );
	}

	public function testStripWrappingDivDoesNotStripWithoutAMatchingCloseTag() {
		$content = <<<EOT
<div>
	<h1>This is content</h1>
EOT;

		$this->assertEquals( $content, strip_wrapping_div( $content ) );
	}

		public function testStripWrappingDivDoesNotAffectDivsWithAttributes() {
		$content = <<<EOT
<div class="significant">
	<h1>This is content</h1>
</div>
EOT;

		$this->assertEquals( $content, strip_wrapping_div( $content ) );
	}

	public function testStripWrappingDivOnlyStripsOneLevelOfDivs() {
		$content = <<<EOT
<div>
	<div><h1>This is content</h1></div>
</div>
EOT;

		$this->assertEquals( '<div><h1>This is content</h1></div>', strip_wrapping_div( $content ) );
	}

	public function testSetAttachmentAuthor() {
		$post = array( 'post_parent' => 123, 'post_author' => null );
		$parent = new \stdClass;
		$parent->post_author = 5;

		M::userFunction( 'get_post', array(
			'args'   => array( 123 ),
			'return' => $parent,
		) );

		$result = set_attachment_author( $post );

		$this->assertEquals( 5, $result['post_author'], 'If no post_author is set for the attachment, it should inherit from the parent post.' );
	}

	public function testSetAttachmentAuthorRespectsPopulatedPostAuthors() {
		$post = array( 'post_parent' => 123, 'post_author' => 2 );

		M::userFunction( 'get_post', array(
			'times'  => 0,
		) );

		$this->assertSame( $post, set_attachment_author( $post ), 'We should not override existing authors on attachments' );
	}

	public function testSetAttachmentAuthorReturnsEarlyIfNoPostParent() {
		$post = array( 'post_parent' => 123, 'unique' => uniqid() );

		M::userFunction( 'get_post', array(
			'return' => null,
		) );

		$this->assertSame( $post, set_attachment_author( $post ), 'Do not attempt to override the post_author if the post_parent either does not exist or doesn\'t have an author ID' );
	}

	public function testGetAttachmentIdByUrl() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->posts = 'my_posts_table';
		$wpdb->shouldReceive( 'get_var' )
			->once()
			->with( 'PREPARED SQL' )
			->andReturn( 42 );
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing( function ( $query, $url ) {
				if ( 'http://example.com/image.jpg' !== $url ) {
					$this->fail( 'The attachment URL should be passed to the database query' );
				}

				return 'PREPARED SQL';
			} );

		M::passthruFunction( 'esc_url_raw' );

		$this->assertEquals( 42, get_attachment_id_by_url( 'http://example.com/image.jpg' ) );
	}
}
