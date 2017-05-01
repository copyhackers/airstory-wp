<?php
/**
 * Plugin Name: Airstory
 * Plugin URI:  http://www.airstory.co/integrations/
 * Description: Publish content from Airstory to WordPress.
 * Version:     0.1.0
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: airstory
 *
 * @package Airstory
 */

namespace Airstory;

define( 'AIRSTORY_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-api.php';
require_once __DIR__ . '/includes/core.php';
require_once __DIR__ . '/includes/credentials.php';
require_once __DIR__ . '/includes/webhook.php';
