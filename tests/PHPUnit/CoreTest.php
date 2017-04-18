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

	function testCheckDependenciesVerifiesOAuthPluginIsActive() {
		M::userFunction( 'is_plugin_active', array(
			'args'   => array( 'rest-api-oauth1/oauth-server.php' ),
			'times'  => 1,
			'return' => false,
		) );

		M::expectActionAdded( 'admin_notices', __NAMESPACE__ . '\notify_dependencies_not_installed' );

		check_dependencies();
	}
}
