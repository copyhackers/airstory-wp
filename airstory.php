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

require_once AIRSTORY_INC . '/class-api.php';
require_once AIRSTORY_INC . '/core.php';
require_once AIRSTORY_INC . '/formatting.php';
require_once AIRSTORY_INC . '/webhook.php';

/**
 * Load asynchronous tasks.
 *
 * To prevent locking the main thread and possibly hitting timeouts, the plugin leverages the
 * TechCrunch WP Asynchronous Tasks library. Actions that require some potentially expensive
 * processes (for instance, side-loading images) can instead be handled asynchronously, by using
 * the wp_async_{hook} pattern.
 *
 * @link https://github.com/techcrunch/wp-async-task
 */
require_once AIRSTORY_INC . '/lib/wp-async-task/wp-async-task.php';
require_once AIRSTORY_INC . '/async-tasks/import-post.php';

// Each task must be instantiated once.
new AsyncTasks\ImportPost();
