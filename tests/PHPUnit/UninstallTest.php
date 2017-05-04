<?php
/**
 * Tests for the plugin's uninstallation routine.
 *
 * @package Airstory
 */

namespace Airstory;

use WP_Mock as M;
use Mockery;

/**
 * @runTestsInSeparateProcesses
 */
class UninstallTest extends \Airstory\TestCase {

	protected $testFiles = [
		'connection.php',
	];

	public function testUninstall() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->usermeta = 'my_table';
		$wpdb->shouldReceive( 'get_col' )
			->times( 2 )
			->andReturn( array( 1, 2, 3 ), array() );
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

		M::userFunction( __NAMESPACE__ . '\Connection\remove_connection', array(
			'times' => 3,
		) );

		define( 'WP_UNINSTALL_PLUGIN', true );

		include PROJECT_ROOT . '/uninstall.php';
	}

	/**
	 * If we're chunking the query query and have 150 users connected to Airstory (IDs 1-150,
	 * conveniently), the query should be run three times: users 1-100, 101-150, then a final run to
	 * ensure there aren't any left.
	 */
	public function testUninstallChunksConnections() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->usermeta = 'my_table';
		$wpdb->shouldReceive( 'get_col' )
			->times( 3 )
			->andReturn( range( 1, 100 ), range( 101, 150 ), array() );
		$wpdb->shouldReceive( 'query' )->once();

		M::userFunction( __NAMESPACE__ . '\Connection\remove_connection', array(
			'times' => 150,
		) );

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
