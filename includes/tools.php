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

	<style type="text/css">
		#airstory-dependencies-list .dashicons-yes {
			color: #46b450;
		}
		#airstory-dependencies-list .dashicons-no {
			color: #d54e21;
		}
		#airstory-dependencies-list .dependency-unmet td {
			background-color: #fef7f1;
		}
	</style>

	<div class="wrap">
		<h1><?php echo esc_html( _x( 'Airstory', 'tools page heading', 'airstory' ) ); ?></h1>
		<p><?php esc_html_e( 'This page contains useful information for integrating Airstory into WordPress.', 'airstory' ); ?></p>

		<h2><?php echo esc_html( _x( 'Compatibility', 'tools page heading', 'airstory' ) ); ?></h2>
		<p class="description"><?php esc_html_e( 'Ensure your WordPress installation has everything it needs to work with Airstory.', 'airstory' ); ?></p>
		<br />
		<table id="airstory-dependencies-list" class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( _x( 'Dependency', 'compatibility table heading', 'airstory' ) ); ?></th>
					<th scope="col" colspan="2"><?php echo esc_html( _x( 'Status', 'compatibility table heading', 'airstory' ) ); ?></th>
				</tr>
			</thead>
			<tbody>

				<tr class="dependency-<?php echo esc_attr( $compatibility['details']['php'] ? 'met' : 'unmet' ); ?>">
					<td><?php esc_html_e( 'PHP Version >= 5.3', 'airstory' ); ?></td>
					<td><?php echo esc_html( sprintf( __( 'Version %s', 'airstory' ), PHP_VERSION ) ); ?></td>
					<td><?php render_status_icon( $compatibility['details']['php'] ); ?></td>
				</tr>
				<?php unset( $compatibility['details']['php'] ); ?>

				<tr class="dependency-<?php echo esc_attr( $compatibility['details']['https'] ? 'met' : 'unmet' ); ?>">
					<td><?php esc_html_e( 'HTTPS Support' ); ?></td>
					<td>
						<?php if ( $compatibility['details']['https'] ) : ?>
							<?php esc_html_e( 'This site supports HTTPS', 'airstory' ); ?>
						<?php else : ?>
							<strong><?php esc_html_e( 'Airstory is unable to talk to your site over HTTPS', 'airstory' ); ?></strong>
						<?php endif; ?>
					</td>
					<td><?php render_status_icon( $compatibility['details']['https'] ); ?></td>
				</tr>
				<?php unset( $compatibility['details']['https'] ); ?>

				<?php foreach ( array_keys( $compatibility['details'] ) as $ext ) : // Everything left is an extension. ?>

					<tr class="dependency-<?php echo esc_attr( $compatibility['details'][ $ext ] ? 'met' : 'unmet' ); ?>">
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
			</tbody>
		</table>

		<?php if ( ! $compatibility['compatible'] ) : ?>

			<h3><?php esc_html_e( 'It appears you\'re missing one or more dependencies!', 'airstory' ); ?></h3>
			<p><?php echo wp_kses_post( __( 'PHP, the programming language behind WordPress, <a href="https://en.wikipedia.org/wiki/List_of_PHP_extensions">has a number of extensions available</a> to enable new or improve existing functionality.', 'airstory' ) ); ?></p>
			<p><?php esc_html_e( 'The Airstory WordPress plugin leverages several common PHP extensions, all of which are typically enabled by default (or at least available) across most web hosts. If one of the extensions above is listed as being missing and you\'re unsure how to activate it yourself, please contact your host for support.', 'airstory' ); ?></p>
		<?php endif; ?>
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

	// HTTPS support.
	$compatibility['details']['https'] = verify_https_support();

	if ( ! $compatibility['details']['https'] ) {
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

/**
 * Verify whether or not the current site *supports* HTTPS.
 *
 * The is_ssl() WordPress function checks if the current request was made over HTTPS, but does not
 * take into account whether or not the request was made over HTTP but the site also supports SSL.
 *
 * For example, consider a site, example.com, that is running on both HTTP and HTTPS:
 * - Running is_ssl() on https://example.com will return TRUE.
 * - Running is_ssl() on http://example.com will return FALSE.
 *
 * If the site isn't already running on HTTPS, this function will attempt to ping the HTTPS version
 * and return whether or not it was reachable.
 *
 * @return bool True if the site is either already being served (or is at least accessible) over
 *              HTTPS, false otherwise.
 */
function verify_https_support() {
	if ( is_ssl() ) {
		return true;
	}

	$response = wp_remote_request( get_rest_url( null, '/airstory/v1', 'https' ), array(
		'method' => 'HEAD',
		'sslverify' => false,
	) );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	/*
	 * 200 is the only status code we're looking for here — redirecting to the HTTP version will
	 * produced mixed-content warnings within Airstory, and WP's default behavior is to not redirect
	 * REST API URIs to use the canonical domain/protocol.
	 */
	return 200 === wp_remote_retrieve_response_code( $response );
}
