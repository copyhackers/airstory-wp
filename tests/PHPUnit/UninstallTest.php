<?php
/**
 * Tests for the plugin's uninstallation routine.
 *
 * @package Airstory
 */

namespace Airstory\Uninstall;

use WP_Mock as M;
use Mockery;
use Patchwork;

/**
 * @runTestsInSeparateProcesses
 */
class UninstallTest extends \Airstory\TestCase {

	protected $testFiles = [
		'connection.php',
	];

	public function tearDown() {
		Patchwork\undoAll();

		parent::tearDown();
	}

	public function testGetActiveSiteIds() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->usermeta = 'test_usermeta';
		$wpdb->base_prefix = 'test_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing( function ( $sql, $a, $b ) {
				$sql = preg_replace( '/\s+/', ' ', $sql ); // Remove newlines.

				if ( false === strpos( $sql, 'SELECT DISTINCT' ) ) {
					$this->fail( 'Limit SQL queries to distinct site IDs' );
				}

				// Verify the logic for the SUBSTRING fragment holds.
				$substring = "SUBSTRING( meta_key, LENGTH(%s) + 1, LENGTH(meta_key) - LENGTH('_airstory_target') - LENGTH(%s) - 1 )";

				if ( false === strpos( $sql, $sql ) ) {
					$this->fail( 'The substring should start one character beyond the length of $wpdb->base_prefix and exclude everything following the site ID' );
				}

				return 'PREPARED_SQL';
			} );
		$wpdb->shouldReceive( 'get_col' )
			->once()
			->with( 'PREPARED_SQL' )
			->andReturn( array( '', 2, 3 ) );

		$this->bootstrap();
		$this->assertEquals( array( 2, 3 ), get_active_site_ids() );
	}

	public function testDisconnectAllUsers() {
		\WP_User_Query::$__results = array( 1, 2, 3 );

		M::userFunction( 'Airstory\Connection\remove_connection', array(
			'times'  => 3,
			'return' => function () {
				\WP_User_Query::tearDown(); // Ensure when we run the query again we don't re-populate.
			}
		) );

		$this->bootstrap();
		disconnect_all_users();
	}

	public function testDisconnectAllUsersChunksUserQueries() {
		\WP_User_Query::$__results = array( 1, 2, 3 );

		M::userFunction( 'Airstory\Connection\remove_connection', array(
			'times'  => 6,
			'args'   => array( function ( $user_id ) {
				\WP_User_Query::tearDown();

				// Prime a second set of results.
				if ( 3 === $user_id ) {
					\WP_User_Query::$__results = array( 4, 5, 6 );
				}

				return true;
			} ),
		) );

		$this->bootstrap();
		disconnect_all_users();
	}

	public function testDeleteAirstoryData() {
		global $wpdb;

		$wpdb = Mockery::mock( 'WPDB' )->makePartial();
		$wpdb->usermeta = 'usermeta_table';
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( "DELETE FROM usermeta_table WHERE meta_key = '_airstory_data';" );

		$this->bootstrap();
		delete_airstory_data();
	}

	/**
	 * The testUninstall() set of functions test the procedural code executed when a user is
	 * uninstalling the Airstory plugin.
	 */
	public function testUninstall() {
		$function_calls = array(
			'get_active_site_ids'  => true,
			'disconnect_all_users' => true,
			'delete_airstory_data' => true,
		);
		M::userFunction( 'is_main_site', array(
			'return' => true,
		) );

		Patchwork\replace( __NAMESPACE__ . '\get_active_site_ids', function () use ( &$function_calls ) {
			unset( $function_calls['get_active_site_ids'] );

			return array();
		} );

		Patchwork\replace( __NAMESPACE__ . '\disconnect_all_users', function () use ( &$function_calls ) {
			unset( $function_calls['disconnect_all_users'] );
		} );

		Patchwork\replace( __NAMESPACE__ . '\delete_airstory_data', function () use ( &$function_calls ) {
			unset( $function_calls['delete_airstory_data'] );
		} );

		define( 'WP_UNINSTALL_PLUGIN', true );

		include PROJECT_ROOT . '/uninstall.php';

		$this->assertEmpty( $function_calls );
	}

	public function testUninstallSwitchesToMainSite() {
		M::userFunction( 'is_main_site', array(
			'return' => false,
		) );

		M::userFunction( 'get_network', array(
			'return' => (object) array( 'site_id' => 7 ),
		) );

		M::userFunction( 'switch_to_blog', array(
			'times'  => 1,
			'args'   => array( 7 ),
		) );

		M::userFunction( 'restore_current_blog', array(
			'times'  => 1,
		) );

		Patchwork\replace( __NAMESPACE__ . '\get_active_site_ids', function () {
			return array();
		} );

		Patchwork\replace( __NAMESPACE__ . '\disconnect_all_users', function () {} );
		Patchwork\replace( __NAMESPACE__ . '\delete_airstory_data', function () {} );

		define( 'WP_UNINSTALL_PLUGIN', true );

		include PROJECT_ROOT . '/uninstall.php';
	}

	public function testUninstallLoopsThroughActiveSites() {
		M::userFunction( 'is_main_site', array(
			'return' => true,
		) );

		Patchwork\replace( __NAMESPACE__ . '\get_active_site_ids', function () {
			return array( 2, 3 );
		} );

		Patchwork\replace( __NAMESPACE__ . '\disconnect_all_users', function () {} );
		Patchwork\replace( __NAMESPACE__ . '\delete_airstory_data', function () {} );

		M::userFunction( 'switch_to_blog', array(
			'times'  => 2,
		) );

		M::userFunction( 'restore_current_blog', array(
			'times'  => 2,
		) );

		define( 'WP_UNINSTALL_PLUGIN', true );

		include PROJECT_ROOT . '/uninstall.php';
	}

	public function testUninstallVerifiesUninstallPluginConstant() {
		$this->assertFalse(
			defined( 'WP_UNINSTALL_PLUGIN' ),
			'Verify PHPUnit configuration, nothing should have defined this constant'
		);

		M::userFunction( 'is_main_site', array(
			'times' => 0,
		) );

		include PROJECT_ROOT . '/uninstall.php';
	}

	/**
	 * Bootstrap the uninstall process.
	 *
	 * This should only be used for testing the functions within the file, as it short-circuits the
	 * WP_UNINSTALL_PLUGIN constant check.
	 */
	protected function bootstrap() {
		define( 'WP_UNINSTALL_PLUGIN', false );
		include PROJECT_ROOT . '/uninstall.php';
	}
}
