<?php
/**
 * Plugin Name: Airstory
 * Plugin URI:  http://help.airstory.co/en/articles/890966-airstory-integrations
 * Description: Send your blog posts from Airstory writing software to WordPress for publication.
 * Version:     1.1.8
 * Author:      Airstory
 * Author URI:  https://airstory.co
 * Text Domain: airstory
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package Airstory
 */

namespace Airstory;

// Declare the canonical plugin version.
define( 'AIRSTORY_VERSION', '1.1.8' );

if ( ! defined( 'AIRSTORY_DIR' ) ) {
	define( 'AIRSTORY_DIR', __DIR__ );
}

require_once AIRSTORY_DIR . '/includes/async-tasks.php';
require_once AIRSTORY_DIR . '/includes/class-api.php';
require_once AIRSTORY_DIR . '/includes/connection.php';
require_once AIRSTORY_DIR . '/includes/compatibility.php';
require_once AIRSTORY_DIR . '/includes/core.php';
require_once AIRSTORY_DIR . '/includes/credentials.php';
require_once AIRSTORY_DIR . '/includes/formatting.php';
require_once AIRSTORY_DIR . '/includes/settings.php';
require_once AIRSTORY_DIR . '/includes/tools.php';
require_once AIRSTORY_DIR . '/includes/webhook.php';
