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
}
