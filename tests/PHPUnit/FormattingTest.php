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

	public function testSideloadImages() {
		$content = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's an image</h1>
<p><img src="https://example.com/image.jpg" alt="" /></p>
EOT;

		M::userFunction( 'media_sideload_image', array(
			'times'  => 1,
			'args'   => array( 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 0, null, 'src' ),
			'return' => 'https://example.com/image.jpg',
		) );

		$this->assertEquals( $expected, sideload_images( $content ) );
	}

	public function testSideloadImagesDeduplicatesMatches() {
		$content = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
<p><img src="https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="" /></p>
EOT;
		$expected = <<<EOT
<h1>Here's the same image twice</h1>
<p><img src="https://example.com/image.jpg" alt="" /></p>
<p><img src="https://example.com/image.jpg" alt="" /></p>
EOT;

		M::userFunction( 'media_sideload_image', array(
			'times'  => 1,
			'args'   => array( 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 0, null, 'src' ),
			'return' => 'https://example.com/image.jpg',
		) );

		$this->assertEquals( $expected, sideload_images( $content ) );
	}
}
