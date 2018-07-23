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
 * @requires extension dom
 */
class FormattingTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'formatting.php',
	);

	public function setUp() {
		parent::setUp();

		M::userFunction( 'wp_parse_url', array(
			'return' => function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			}
		) );
	}

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

	/**
	 * @dataProvider foreignLanguageProvider
	 *
	 * @link https://github.com/liquidweb/airstory-wp/issues/44
	 */
	public function testGetBodyContentsWithAccentedLanguages( $content ) {
		$content = '<p>' . $content . '</p>';

		$this->assertEquals( $content, get_body_contents( $content ) );
	}

	/**
	 * Sample paragraphs in a number of different languages, to ensure that characters aren't being
	 * butchered by get_body_contents().
	 *
	 * @link http://randomtextgenerator.com/
	 * @link http://justanotherfoundry.com/generator
	 * @link http://german-ipsum.herokuapp.com/
	 * @link http://es.lipsum.com/
	 */
	public function foreignLanguageProvider() {
		return array(
			'Chinese (Han)' => array( '玉，不題 父親回衙 汗流如雨 冒認收了. 此是後話 饒爾去罷」 也懊悔不了 ，愈聽愈惱. 在一處 後竊聽 ﻿白圭志 分得意 以測機 危德至. 去 關雎 耳 意 矣 事. 關雎 矣 誨 耳 意 事 曰：. 耳 曰： ，可 」 去. 分得意 意 第十一回 樂而不淫 曰： 己轉身 事 矣 覽 在一處 後竊聽 誨 出. 也懊悔不了 此是後話 饒爾去罷」. 樂而不淫 訖乃返 建章曰：. 父親回衙 冒認收了 汗流如雨 玉，不題. 出 誨 曰： ，可 關雎 」. 事 關雎 覽 去 ，可 」 誨 耳. 玉，不題 矣 意 耳 吉安而來 曰： 覽 父親回衙 冒認收了.'),
			'German' => array( 'Deutsches Ipsum Dolor quo Kartoffelkopf posidonium über adhuc Wurst sadipscing Mesut Özil at, Bildung mei Hackfleisch gloriatur. Reise virtute Polizei per Currywurst At Freude schöner Götterfunken scaevola Lukas Podolski An Mozart malorum Heisenberg ius' ),
			'Hebrew' => array( 'הכוח עול בני רות שסוף שולח רגע נשק. יד אח להצעתו די הִנָּם סר ול על ואנשים. הטבק דומם שאול וחרץ התנו בסוס. =יחידת ראו כאל ירק אין עוז שעה כפר באה. הַסֻּלָּם לְשָׁלוֹם וְתַבִּיט הַיְקָרָה רִגְשׁוֹת. פיו וגם כפר יתד לחש =יחידת פרק.' ),
			'Icelandic' => array( 'Fraust viðast hvensi lauði tir vo afti það komist auður, þykipt að við ti ofanin út mika.' ),
			'Japanese' => array( '. 復讐者」. 第三章 第九章 第八章 第七章 第四章. .復讐者」 伯母さん. 復讐者」 伯母さん. 伯母さん 復讐者」 . 第十三章 第十一章 手配書 第十四章 第十六章. 第十七章 第十三章 第十二章 第十八章. 復讐者」 . 第十九章 第十三章 第十八章. 第十章 第六章 第三章 .伯母さん 復讐者」 .' ),
			'Russian' => array( 'Яд от защититель сокрушеньи Тя страданьем простирают со. . ﻿кто шум дел дни дев. Под бранят Зри Зло кою нуждам зрелой Муж. Ея Вы ах По. Увенчанна Создателю щит сил чуд бед Наш изъяснить ухищренье Они иго вседневно.' ),
			'Spanish' => array( 'Lorem Ipsum ha sido el texto de relleno estándar de las industrias desde el año 1500, cuando un impresor (N. del T. persona que se dedica a la imprenta) desconocido usó una galería de textos y los mezcló de tal manera que logró hacer un libro de textos especimen.' ),
		);
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

	/**
	 * @runInSeparateProcess Or risk the libxml error buffer getting all kinds of screwy.
	 */
	public function testGetBodyContentsWithInvalidHTMLBody() {
		$response = <<<EOT
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title></title>
<body>
	<h1>Bad heading</div>
</body>
</html>
EOT;

		$this->expectException('PHPUnit_Framework_Error_Warning');
		$this->assertEmpty( get_body_contents( $response ) );
	}

	public function testGetBodyContentsDoesNotButcherEmoji() {
		$emoji = '<p>emoji: 😉</p>';

		$this->assertEquals( $emoji, get_body_contents( $emoji ), 'Multi-byte characters like emoji appear to be encoded improperly.' );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/28
	 */
	public function testGetBodyContentsHandlesNonBreakingSpacesCorrectly() {
		$response = '<p>This&nbsp;paragraph&nbsp;has&nbsp;non-breaking&nbsp;spaces&nbsp;between&nbsp;each&nbsp;word.</p>';

		$this->assertEquals( html_entity_decode( $response ), get_body_contents( $response ) );
	}

	public function testSideloadSingleImage() {
		$url  = 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';
		$meta = array(
			'some-key'  => 'some-value',
			'empty-key' => null,
		);

		M::userFunction( 'download_url', array(
			'args'   => array( $url ),
			'return' => '_tmpfile',
		) );

		M::userFunction( 'media_handle_sideload', array(
			'args'   => array( array( 'name' => 'image.jpg', 'tmp_name' => '_tmpfile' ), 123 ),
			'return' => 42,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => false,
		) );

		M::userFunction( 'add_post_meta', array(
			'times'  => 1,
			'args'   => array( 42, '_airstory_origin', $url ),
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 1,
			'args'   => array( 42, 'some-key', 'some-value' ),
		) );

		M::userFunction( 'update_post_meta', array(
			'times'  => 0,
			'args'   => array( 42, 'empty-key', null ),
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'esc_url_raw' );

		M::expectAction( 'airstory_sideload_single_image', $url, 123, $meta );

		sideload_single_image( $url, 123, $meta );
	}

	public function testSideloadSingleImageReturnsEarlyIfNotUrl() {
		$url = 'this is not a url, are you crazy?';

		$this->assertEquals( 0, sideload_single_image( $url ), 'Without a URL, we have no reason to sideload' );
	}

	/**
	 * @expectedException        PHPUnit_Framework_Error_Warning
	 * @expectedExceptionMessage Error Message
	 */
	public function testSideloadSingleImageReturnsEarlyIfSideloadFails() {
		$url   = 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';
		$error = Mockery::mock( 'WP_Error' )->makePartial();
		$error->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Error Message' );

		M::userFunction( 'download_url', array(
			'args'   => array( $url ),
			'return' => '_tmpfile',
		) );

		M::userFunction( 'media_handle_sideload', array(
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'return_in_order' => array( false, true ),
		) );

		M::passthruFunction( 'esc_html' );
		M::passthruFunction( 'esc_url_raw' );

		$this->assertEquals( 0, sideload_single_image( $url, 123 ) );
	}

	/**
	 * @expectedException        PHPUnit_Framework_Error_Warning
	 * @expectedExceptionMessage Error Message
	 */
	public function testSideloadSingleImageReturnsEarlyIfDownloadUrlFails() {
		$url   = 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';
		$error = Mockery::mock( 'WP_Error' )->makePartial();
		$error->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Error Message' );

		M::userFunction( 'download_url', array(
			'args'   => array( $url ),
			'return' => $error,
		) );

		M::userFunction( 'media_handle_sideload', array(
			'times'  => 0,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		M::passthruFunction( 'esc_html' );
		M::passthruFunction( 'esc_url_raw' );

		$this->assertEquals( 0, sideload_single_image( $url, 123 ) );
	}

	/**
	 * Media sideloading outside of the wp-admin context requires several files be included.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/media_sideload_image#Notes
	 *
	 * @runInSeparateProcess
	 * @expectedException PHPUnit_Framework_Error_Warning
	 */
	public function testSideloadSingleImageLoadsMediaDependencies() {
		$error = Mockery::mock( 'WP_Error' )->makePartial();
		$error->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Error Message' );

		M::userFunction( 'download_url', array(
			'return' => $error,
		) );

		M::userFunction( 'is_wp_error', array(
			'return' => true,
		) );

		M::passthruFunction( 'esc_url_raw' );

		sideload_single_image( 'http://example.com/image.jpg' );

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

	public function testSideloadAllImages() {
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

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 1,
			'args'   => array( 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 123, array(
				'_wp_attachment_image_alt' => 'my alt text',
			) ),
			'return' => 42,
		) );

		M::userFunction( 'wp_update_post', array(
			'times'  => 1,
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::userFunction( 'wp_get_attachment_url', array(
			'args'   => array( 42 ),
			'return' => 'https://example.com/image.jpg',
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 1, sideload_all_images( 123 ) );
	}

	public function testSideloadAllImagesDeduplicatesMatches() {
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

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'return' => 42,
		) );

		M::userFunction( 'wp_get_attachment_url', array(
			'return' => 'https://example.com/image.jpg',
		) );

		M::userFunction( 'wp_update_post', array(
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 2, sideload_all_images( 123 ) );
	}

	public function testSideloadAllImagesHandlesMultipleImages() {
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

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'           => 2,
			'return_in_order' => array( 42, 43 ),
		) );

		M::userFunction( 'wp_update_post', array(
			'return' => function ( $post ) use ( $expected ) {
				if ( $expected !== $post->post_content ) {
					$this->fail( 'Expected image replacement did not occur!' );
				}
			},
		) );

		M::userFunction( 'wp_get_attachment_url', array(
			'return_in_order' => array(
				'https://example.com/image.jpg',
				'https://example.com/image2.jpg',
			),
		) );

		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 2, sideload_all_images( 123 ) );
	}

	public function testSideloadAllImagesReturnsEarlyIfInvalidPostID() {
		M::userFunction( 'get_post', array(
			'return' => null,
		) );

		$this->assertEquals( 0, sideload_all_images( 123 ) );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testSideloadAllImagesSupportsCloudinaryURLs() {
		$content = '<p><img src="https://res.cloudinary.com/airstory/image/upload/c_scale,w_0.1/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg" alt="my alt text" /></p>';
		$expected = '<p><img src="https://example.com/image.jpg" alt="my alt text"></p>';

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'args'   => array( 123 ),
			'return' => $post,
		) );

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 1,
			'args'   => array( 'https://res.cloudinary.com/airstory/image/upload/c_scale,w_0.1/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 123, '*' ),
			'return' => 42,
		) );

		M::userFunction( 'wp_update_post', array(
			'times'  => 1,
		) );

		M::userFunction( 'wp_get_attachment_url', array(
			'return' => 'https://example.com/image.jpg',
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		$this->assertEquals( 1, sideload_all_images( 123 ) );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testSideloadAllImagesSupportsUserProvidedDomains() {
		$content = '<p><img src="https://someimagehost.com/image.jpg" alt="my alt text" /></p>';
		$expected = '<p><img src="https://example.com/image.jpg" alt="my alt text"></p>';

		$post = new \stdClass;
		$post->post_content = $content;

		M::userFunction( 'get_post', array(
			'args'   => array( 123 ),
			'return' => $post,
		) );

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 1,
			'return' => 42,
		) );

		M::userFunction( 'wp_update_post' );

		M::userFunction( 'wp_get_attachment_url', array(
			'return' => 'https://example.com/image.jpg',
		) );

		M::passthruFunction( 'esc_url' );
		M::passthruFunction( 'sanitize_text_field' );

		M::onFilter( 'airstory_sideload_image_domains' )
			->with( array( 'images.airstory.co', 'res.cloudinary.com' ) )
			->reply( array( 'someimagehost.com' ) );

		$this->assertEquals( 1, sideload_all_images( 123 ) );
	}

	/**
	 * @dataProvider foreignLanguageProvider
	 *
	 * @link https://github.com/liquidweb/airstory-wp/issues/61
	 */
	public function testSideloadAllImagesWithAccentedLanguages( $content ) {
		$post = new \stdClass;
		$post->post_content = '<p>' . $content . '</p>';

		M::userFunction( 'get_post', array(
			'return' => $post,
		) );

		// The $content contains no images, so there should be nothing changed.
		M::userFunction( 'wp_update_post', array(
			'times'  => 0,
		) );

		$this->assertEquals( 0, sideload_all_images( 123 ) );
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

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testRetrieveOriginalMedia() {
		$url = 'https://images.airstory.co/c_scale,w_0.1/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 1,
			'args'   => array( 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 1, array() ),
		) );

		retrieve_original_media( $url, 1, array() );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testRetrieveOriginalMediaCloudinary() {
		$url = 'https://res.cloudinary.com/airstory/image/upload/c_scale,w_0.1/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 1,
			'args'   => array( 'https://res.cloudinary.com/airstory/image/upload/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg', 1, array() ),
		) );

		retrieve_original_media( $url, 1, array() );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testRetrieveOriginalMediaReturnsEarlyIfNotCloudinaryMedia() {
		$url = 'https://example.com/airstory/image/upload/c_scale,w_0.1/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 0,
		) );

		retrieve_original_media( $url, 1, array() );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testRetrieveOriginalMediaReturnsEarlyIfAlreadyOriginalImage() {
		$url = 'https://images.airstory.co/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 0,
		) );

		retrieve_original_media( $url, 1, array() );
	}

	/**
	 * @link https://github.com/liquidweb/airstory-wp/issues/27
	 */
	public function testRetrieveOriginalMediaReturnsEarlyIfAlreadyOriginalImageCloudinary() {
		$url = 'https://res.cloudinary.com/airstory/image/upload/v1/prod/iXXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX/image.jpg';

		M::userFunction( __NAMESPACE__ . '\sideload_single_image', array(
			'times'  => 0,
		) );

		retrieve_original_media( $url, 1, array() );
	}

	public function testFormatLibXMLError() {
		$error = new \libXMLError();
		$error->level   = LIBXML_ERR_ERROR;
		$error->code    = 76; // XML_ERR_TAG_NAME_MISMATCH.
		$error->column  = 23;
		$error->message = 'Unexpected end tag : div';
		$error->file    = 'some-file.html';
		$error->line    = 5;

		$this->assertEquals(
			'[LibXML Error] There was a problem parsing the document: "Unexpected end tag : div".'
			. PHP_EOL . '- some-file.html line 5, column 23. XML error code 76.',
			format_libxml_error( $error )
		);
	}

	public function testFormatLibXMLErrorWithInvalidError() {
		$this->assertEmpty( format_libxml_error( array() ) );
	}
}
