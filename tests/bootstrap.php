<?php
/**
 * Bootstrap the test suite.
 *
 * @package Airstory
 */

if ( ! defined( 'PROJECT_ROOT' ) ) {
	define( 'PROJECT_ROOT', dirname( __DIR__ ) );
}

if ( ! defined( 'PROJECT' ) ) {
	define( 'PROJECT', PROJECT_ROOT . '/includes/' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/test-tools/dummy-files/' );
}

if ( ! defined( 'AUTH_KEY' ) ) {
	define( 'AUTH_KEY', 'the AUTH_KEY from wp-config.php' );
}

if ( ! file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	throw new PHPUnit_Framework_Exception(
		'ERROR: You must use Composer to install the test suite\'s dependencies!' . PHP_EOL
	);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test-tools/TestCase.php';
require_once ABSPATH . 'wp-includes/class-wp-query.php';
require_once ABSPATH . 'wp-includes/class-wp-user-query.php';
require_once ABSPATH . 'wp-includes/class-wp-error.php';

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();
WP_Mock::tearDown();
