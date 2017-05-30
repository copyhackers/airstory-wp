<?php
/**
 * Registers the Tools > Airstory page within WordPress.
 *
 * @package Airstory
 */

namespace Airstory\Tools;

/**
 * Register the "Airstory" page under the Tools heading.
 */
function register_menu_page() {
	add_submenu_page(
		'tools.php',
		_x( 'Airstory', 'tools page title', 'airstory' ),
		_x( 'Airstory', 'tools menu title', 'airstory' ),
		'manage_options',
		'airstory',
		__NAMESPACE__ . '\render_tools_page'
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\register_menu_page' );

/**
 * Render the content for the "Airstory" tools page.
 */
function render_tools_page() {
?>

	<div class="wrap">
		<h1><?php echo esc_html( _x( 'Airstory', 'tools page heading', 'airstory' ) ); ?></h1>
		<p><?php esc_html_e( 'This page contains useful information for integrating Airstory into WordPress.', 'airstory' ); ?></p>

		<h2><?php echo esc_html( _x( 'Compatibility', 'tools page heading', 'airstory' ) ); ?></h2>
		<p class="description"><?php esc_html_e( 'Ensure your WordPress installation has everything it needs to work with Airstory.', 'airstory' ); ?></p>

	</div>

<?php
}

/**
 * Verify the compatibility of the current environment with Airstory.
 *
 * The requirements for the plugin should be documented in the plugin README, but include:
 *
 * - PHP >= 5.3        - Namespace support, though the plugin will fail before reaching this check
 *                       if namespaces are unsupported.
 * - dom extension     - Used by DOMDocument in formatters.php.
 * - mcrypt extension  - Used as a backup for older systems that don't support PHP 7's random_bytes().
 * - openssl extension - Used to securely encrypt Airstory credentials.
 *
 * @return array {
 *   An array with two nodes, outlining any compatibility issues.
 *
 *   @var bool  $compatible Is the WordPress installation compatible with Airstory?
 *   @var array $details    An array of dependency checks as keys, matched to an array containing
 *                          'compatible' (boolean) and 'explanation' (string) values.
 * }
 */
function check_compatibility() {
	$compatibility = array(
		'compatible' => true,
		'details'    => array(),
	);

	// Check required PHP extensions.
	$extensions = array( 'dom', 'mcrypt', 'openssl' );

	foreach ( $extensions as $extension ) {
		$compatibility['details'][ $extension ] = extension_loaded( $extension );

		if ( ! $compatibility['details'][ $extension ] ) {
			$compatibility['compatible'] = false;
		}
	}

	return $compatibility;
}
