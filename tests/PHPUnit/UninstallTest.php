<?php
/**
 * Tests for the plugin's uninstallation routine.
 *
 * @package Airstory
 */

namespace Airstory\Core;

use WP_Mock as M;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 */
class UninstallTest extends \Airstory\TestCase {

	public function testUninstall() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->usermeta = 'my_table';
		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturnUsing( function ( $query ) {

				if ( false === strpos( $query, 'DELETE FROM my_table WHERE' ) ) {
					$this->fail( 'Uninstall query does not seem to target $wpdb->usermeta' );
				}

				$known_keys = array( '_airstory_token', '_airstory_iv', '_airstory_profile' );

				foreach ( $known_keys as $key ) {
					if ( false === strpos( $query, "'$key'" ) ) {
						$this->fail( 'Uninstall query is not removing meta_key: ' . $key );
					}
				}
			} );

		define( 'WP_UNINSTALL_PLUGIN', true );

		include PROJECT_ROOT . '/uninstall.php';
	}

	public function testUninstallVerifiesUninstallPluginConstant() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->shouldReceive( 'query' )->never();

		$this->assertFalse( defined( 'WP_UNINSTALL_PLUGIN' ), 'Verify PHPUnit configuration, nothing should have defined this constant' );

		include PROJECT_ROOT . '/uninstall.php';
	}
}
