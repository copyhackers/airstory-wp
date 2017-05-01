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

define( 'AIRSTORY_INC', __DIR__ . '/includes' );

require_once AIRSTORY_INC . '/async-tasks.php';
require_once AIRSTORY_INC . '/class-api.php';
require_once AIRSTORY_INC . '/core.php';
require_once AIRSTORY_INC . '/formatting.php';
require_once AIRSTORY_INC . '/credentials.php';
require_once AIRSTORY_INC . '/webhook.php';
