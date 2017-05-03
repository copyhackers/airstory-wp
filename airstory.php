<?php
/**
 * Plugin Name: Airstory
 * Plugin URI:  http://www.airstory.co/integrations/
 * Description: Publish content from Airstory to WordPress.
 * Version:     0.1.0
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: airstory
 * Domain Path: /languages
 *
 * @package Airstory
 */

namespace Airstory;

if ( ! defined( 'AIRSTORY_DIR' ) ) {
	define( 'AIRSTORY_DIR', __DIR__ );
}

require_once AIRSTORY_DIR . '/includes/async-tasks.php';
require_once AIRSTORY_DIR . '/includes/class-api.php';
require_once AIRSTORY_DIR . '/includes/connection.php';
require_once AIRSTORY_DIR . '/includes/core.php';
require_once AIRSTORY_DIR . '/includes/credentials.php';
require_once AIRSTORY_DIR . '/includes/formatting.php';
require_once AIRSTORY_DIR . '/includes/settings.php';
require_once AIRSTORY_DIR . '/includes/webhook.php';
