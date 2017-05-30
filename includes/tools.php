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
	$compatibility = check_compatibility();
?>

	<div class="wrap">
		<h1><?php echo esc_html( _x( 'Airstory', 'tools page heading', 'airstory' ) ); ?></h1>
		<p><?php esc_html_e( 'This page contains useful information for integrating Airstory into WordPress.', 'airstory' ); ?></p>

		<h2><?php echo esc_html( _x( 'Compatibility', 'tools page heading', 'airstory' ) ); ?></h2>
		<p class="description"><?php esc_html_e( 'Ensure your WordPress installation has everything it needs to work with Airstory.', 'airstory' ); ?></p>

		<table class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( _x( 'Dependency', 'compatibility table heading', 'airstory' ) ); ?></th>
					<th scope="col" colspan="2"><?php echo esc_html( _x( 'Status', 'compatibility table heading', 'airstory' ) ); ?></th>
				</tr>
			</thead>
			<tbody>

				<tr>
					<td><?php esc_html_e( 'PHP Version >= 5.3', 'airstory' ); ?></td>
					<td><?php echo esc_html( sprintf( __( 'Version %s', 'airstory' ), PHP_VERSION ) ); ?></td>
					<td><?php render_status_icon( $compatibility['details']['php'] ); ?></td>
				</tr>
				<?php unset( $compatibility['details']['php'] ); ?>

				<?php foreach ( array_keys( $compatibility['details'] ) as $ext ) : // Everything left is an extension. ?>

					<tr>
						<td><?php echo esc_html( sprintf( __( 'PHP Extension: %s', 'airstory' ), $ext ) ); ?></td>
						<td>
							<?php if ( $compatibility['details'][ $ext ] ) : ?>
								<?php esc_html_e( 'Extension loaded', 'airstory' ); ?>
							<?php else : ?>
								<strong><?php echo esc_html( sprintf( __( 'The %s extension is missing!', 'airstory' ), $ext ) ); ?></strong>
							<?php endif; ?>
						</td>
						<td><?php render_status_icon( $compatibility['details'][ $ext ] ); ?></td>
					</tr>

				<?php endforeach; ?>
		</table>
	</div>

<?php
}

/**
 * Render a check mark or "X" corresponding to the boolean value passed to the function.
 *
 * @param mixed $status The state of the option — TRUE will create a check mark, FALSE will produce
 *                      an "X". Non-Boolean values will be cast as Booleans.
 */
function render_status_icon( $status ) {

	if ( (bool) $status ) {
		$icon = 'yes';
		$msg  = _x( 'Passed', 'dependency check status', 'airstory' );

	} else {
		$icon = 'no';
		$msg  = _x( 'Failed', 'dependency check status', 'airstory' );
	}

	echo wp_kses_post( sprintf(
		'<span class="dashicons dashicons-%s"></span><span class="screen-reader-text">%s</span>',
		esc_attr( $icon ),
		esc_html( $msg )
	) );
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

	// PHP Version.
	$compatibility['details']['php'] = version_compare( PHP_VERSION, '5.3.0', '>=' );

	if ( ! $compatibility['details']['php'] ) {
		$compatibility['compatible'] = false;
	}

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
