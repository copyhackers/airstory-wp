<?php
/**
 * Tests for Airstory content formatters.
 *
 * @package Airstory
 */

namespace Airstory\Formatting;

use WP_Mock as M;
use Mockery;

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
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
EOT;
		// DOMDocument uses HTML5-style <img> elements, without the closing slash.
		$expected = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://example.com/image.jpg" alt=""></p>
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

		$this->assertEquals( 1, sideload_images( 123 ) );
	}

	public function testSideloadImagesDeduplicatesMatches() {
		$content = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://example.com/image.jpg" alt=""></p>
<p><img src="https://example.com/image.jpg" alt=""></p>
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

		$this->assertEquals( 2, sideload_images( 123 ) );
	}

	public function testSideloadImagesHandlesMultipleImages() {
		$content = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image2.jpg" alt="" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://example.com/image.jpg" alt=""></p>
<p><img src="https://example.com/image2.jpg" alt=""></p>
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

		$this->assertEquals( 2, sideload_images( 123 ) );
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
}
