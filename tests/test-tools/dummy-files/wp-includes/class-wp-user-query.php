<?php
/**
 * Mock class for WP_User_Query.
 */

class WP_User_Query {

	public static $__query   = array();
	public static $__results = array();
	public static $__filter  = null;

	public $results = array();

	public function __construct( $query = null ) {
		self::$__query = $query;

		// If self::$__filter is callable, run results through that.
		if ( is_callable( self::$__filter ) ) {
			$filter = self::$__filter;
			$this->results = $filter( self::$__results, $query );

		} else {
			$this->results = self::$__results;
		}
	}

	// Used to reset between tests.
	public static function tearDown() {
		self::$__query   = array();
		self::$__results = array();
		self::$__filter  = null;
	}
}
