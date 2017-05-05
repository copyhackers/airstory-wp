<?php
/**
 * Mock class for WP_Query.
 */

class WP_Query {

	public static $__query   = array();
	public static $__results = array();

	public $posts = array();

	public function __construct( $query = null ) {
		self::$__query = $query;

		$this->posts = self::$__results;
	}

	// Used to reset between tests.
	public static function tearDown() {
		self::$__query   = array();
		self::$__results = array();
	}
}
