<?php
/**
 * Tests for the plugin's compatibility layers.
 *
 * @package Airstory
 */

namespace Airstory\Compatibility;

use WP_Mock as M;

class CompatibilityTest extends \Airstory\TestCase {

	protected $testFiles = array(
		'compatibility.php',
	);

	public function testWpSpamShieldWhitelistWebhook() {
		$_SERVER['REQUEST_URI'] = '/wp-json/airstory/v1/webhook';

		M::userFunction( 'rest_get_url_prefix', [
			'return_in_order' => [ 'wp-json', 'some-other-value' ],
		] );
		M::passthruFunction( 'untrailingslashit' );

		$this->assertTrue( wpspamshield_whitelist_webhook( false ) );
		$this->assertFalse( wpspamshield_whitelist_webhook( false ) );
	}
}
