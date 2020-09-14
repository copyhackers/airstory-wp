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
 * Register the tools scripting.
 */
function register_tools_script() {
	wp_register_script(
		'airstory-tools',
		plugins_url( 'assets/js/tools.js', __DIR__ ),
		null,
		AIRSTORY_VERSION,
		true
	);

	wp_localize_script(
		'airstory-tools', 'airstoryTools', array(
			'restApiUrl'  => get_rest_url( null, '/airstory/v1', 'https' ),
			'statusIcons' => array(
				'loading' => '',
				'success' => render_status_icon( true, false ),
				'failure' => render_status_icon( false, false ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_tools_script' );

/**
 * Render the content for the "Airstory" tools page.
 */
function render_tools_page() {
	wp_enqueue_script( 'airstory-tools' );
	$compatibility = check_compatibility();

	// phpcs:disable Generic.WhiteSpace.ScopeIndent
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
		<p class="description">
			<?php
				echo esc_html(
					sprintf(
						/* Translators: %1$s is the current plugin version. */
						__( 'Version %1$s', 'airstory' ),
						AIRSTORY_VERSION
					)
				);
			?>
		</p>
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
					<td><?php esc_html_e( 'PHP 5.3 or higher', 'airstory' ); ?></td>
					<td>
						<?php
							echo esc_html(
								sprintf(
									/* Translators: %1$s is the current PHP version. */
									_x( 'Version %1$s', 'PHP version', 'airstory' ),
									PHP_VERSION
								)
							);
						?>
					</td>
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
						<td>
							<?php
								echo esc_html(
									sprintf(
										/* Translators: %1$s represents the PHP extension name. */
										__( 'PHP Extension: %1$s', 'airstory' ),
										$ext
									)
								);
							?>
						</td>
						<td>
							<?php if ( $compatibility['details'][ $ext ] ) : ?>
								<?php esc_html_e( 'Extension loaded', 'airstory' ); ?>
							<?php else : ?>
								<strong>
									<?php
										echo esc_html(
											sprintf(
												/* Translators: %1$s is the name of the missing PHP extension. */
												__( 'The %1$s extension is missing!', 'airstory' ),
												$ext
											)
										);
									?>
								</strong>
							<?php endif; ?>
						</td>
						<td><?php render_status_icon( $compatibility['details'][ $ext ] ); ?></td>
					</tr>

				<?php endforeach; ?>

				<tr>
					<td><?php esc_html_e( 'WordPress REST API', 'airstory' ); ?></td>
					<td><?php esc_html_e( 'Is this site\'s REST API accessible via HTTPS?', 'airstory' ); ?></td>
					<td id="airstory-restapi-check">
						<img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" alt="<?php esc_attr_e( 'Loading', 'airstory' ); ?>" width="20" />
					</td>
				</tr>

				<tr>
					<td><?php esc_html_e( 'Airstory connection', 'airstory' ); ?></td>
					<td><?php esc_html_e( 'Can this site communicate with Airstory?', 'airstory' ); ?></td>
					<td id="airstory-connection-check">
						<img src="<?php echo esc_url( admin_url( 'images/spinner-2x.gif' ) ); ?>" alt="<?php esc_attr_e( 'Loading', 'airstory' ); ?>" width="20" />
					</td>
				</tr>
			</tbody>
		</table>

		<?php if ( ! $compatibility['compatible'] ) : ?>

			<h3><?php esc_html_e( 'It appears you\'re missing one or more dependencies!', 'airstory' ); ?></h3>
			<p><?php echo wp_kses_post( __( 'PHP, the programming language behind WordPress, <a href="https://en.wikipedia.org/wiki/List_of_PHP_extensions">has a number of extensions available</a> to enable new or improve existing functionality.', 'airstory' ) ); ?></p>
			<p><?php esc_html_e( 'The Airstory WordPress plugin leverages several common PHP extensions, all of which are typically enabled by default (or at least available) across most web hosts. If one of the extensions above is listed as being missing and you\'re unsure how to activate it yourself, please contact your host for support.', 'airstory' ); ?></p>
			<p><?php esc_html_e( 'Airstory also uses Secure Socket Layers (SSL) to send data back and forth between your site and Airstory, so your site must be accessible over HTTPS. Fortunately, SSL certificates can be generated by your host, typically at no cost to you.', 'airstory' ); ?></p>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Get Support', 'airstory' ); ?></h2>
		<p><?php echo wp_kses_post( __( 'The Airstory WordPress plugin is backed by <a href="https://www.liquidweb.com/support/heroic-promise.html">Liquid Web\'s Heroic Support</a>; if you run into any issues when working with the Airstory plugin, please <a href="https://wordpress.org/support/plugin/airstory" target="_blank">feel free to open an issue in the WordPress.org plugin repo</a>.', 'airstory' ) ); ?></p>
		<p><?php esc_html_e( 'To help troubleshoot any issues, please include the following report in your support request:', 'airstory' ); ?></p>
		<textarea class="large-text code" rows="15" cols="50" readonly="readonly" onclick="this.focus(); this.select()"><?php echo esc_html( get_support_details() ); ?></textarea>
	</div>

<?php
	// phpcs:enable Generic.WhiteSpace.ScopeIndent
}

/**
 * Render a check mark or "X" corresponding to the boolean value passed to the function.
 *
 * @param mixed $status The state of the option — TRUE will create a check mark, FALSE will produce
 *                      an "X". Non-Boolean values will be cast as Booleans.
 * @param bool  $echo   Optional. Output the icon directly to the browser, or return it? Default
 *                      is true.
 * @return void|string  Depending on $echo, either the function will return nothing or return a
 *                      string containing the status icon.
 */
function render_status_icon( $status, $echo = true ) {

	if ( (bool) $status ) {
		$icon = 'yes';
		$msg  = _x( 'Passed', 'dependency check status', 'airstory' );

	} else {
		$icon = 'no';
		$msg  = _x( 'Failed', 'dependency check status', 'airstory' );
	}

	$output = sprintf(
		'<span class="dashicons dashicons-%s"></span><span class="screen-reader-text">%s</span>',
		esc_attr( $icon ),
		esc_html( $msg )
	);

	if ( ! $echo ) {
		return $output;
	}

	echo wp_kses_post( $output );
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
	$extensions = array( 'dom', 'openssl' );

	// Mcrypt is required for PHP < 7.0.
	if ( version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
		$extensions[] = 'mcrypt';
	} else {
		$compatibility['details']['mcrypt'] = true;
	}

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

	$response = wp_remote_request(
		get_rest_url( null, '/airstory/v1', 'https' ), array(
			'method'    => 'HEAD',
			'sslverify' => false,
		)
	);

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

/**
 * Build a system environment report for submission along with any bug reports.
 *
 * This is largely based on (a simplified version) of Easy Digital Downloads' edd_tools_sysinfo_get()
 * reporting function. Available for use in this plugin under the MIT license with permission from
 * copyright holder Pippin Williamson.
 *
 * @link https://github.com/easydigitaldownloads/easy-digital-downloads
 * @link https://github.com/copyhackers/airstory-wp/pull/35#discussion_r119388840
 *
 * @global $wpdb
 *
 * @return string A formatted text report on important details about the system status.
 */
function get_support_details() {
	global $wpdb;

	$report = '### Begin System Info ###' . PHP_EOL;

	// Basic details about the site.
	$report .= PHP_EOL . '-- Site Info' . PHP_EOL . PHP_EOL;
	$report .= 'Site URL:            ' . esc_url( site_url() ) . PHP_EOL;
	$report .= 'Home URL:            ' . esc_url( home_url() ) . PHP_EOL;
	$report .= 'Multisite:           ' . ( is_multisite() ? 'Yes' : 'No' ) . PHP_EOL;

	// What little user information might be relevant.
	$report .= PHP_EOL . '-- User' . PHP_EOL . PHP_EOL;
	$report .= 'User-Agent:          ' . ( empty( $_SERVER['HTTP_USER_AGENT'] ) ? '(empty)' : esc_html( $_SERVER['HTTP_USER_AGENT'] ) ) . PHP_EOL;

	// Basic WordPress configuration.
	$theme_data   = wp_get_theme();
	$theme        = sprintf( '%s %s', $theme_data->Name, $theme_data->Version );
	$parent_theme = '(none)';

	if ( ! empty( $theme_data->Template ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		$parent_theme      = sprintf( '%s %s', $parent_theme_data->Name, $parent_theme_data->Version );
	}

	$report .= PHP_EOL . '-- WordPress Configuration' . PHP_EOL . PHP_EOL;
	$report .= 'Version:             ' . get_bloginfo( 'version' ) . PHP_EOL;
	$report .= 'Language:            ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . PHP_EOL;
	$report .= 'Permalink Structure: ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . PHP_EOL;
	$report .= 'Theme:               ' . esc_html( $theme ) . PHP_EOL;
	$report .= 'Parent Theme:        ' . esc_html( $parent_theme ) . PHP_EOL;
	$report .= 'ABSPATH:             ' . ABSPATH . PHP_EOL;
	$report .= 'WP_DEBUG:            ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' ) . PHP_EOL;
	$report .= 'Memory Limit:        ' . WP_MEMORY_LIMIT . PHP_EOL;

	// Airstory-specific configuration.
	$compatibility = check_compatibility();

	$report .= PHP_EOL . '-- Airstory Configuration' . PHP_EOL . PHP_EOL;
	$report .= 'Version:                  ' . AIRSTORY_VERSION . PHP_EOL;
	$report .= 'Requirements:' . PHP_EOL;
	$report .= '- PHP >= 5.3         ' . ( $compatibility['details']['php'] ? 'PASS' : 'FAIL' ) . PHP_EOL;
	$report .= '- HTTPS support      ' . ( $compatibility['details']['https'] ? 'PASS' : 'FAIL' ) . PHP_EOL;
	$report .= '- DOM Extension      ' . ( $compatibility['details']['dom'] ? 'PASS' : 'FAIL' ) . PHP_EOL;
	$report .= '- Mcrypt Extension   ' . ( $compatibility['details']['mcrypt'] ? 'PASS' : 'FAIL' ) . PHP_EOL;
	$report .= '- OpenSSL Extension  ' . ( $compatibility['details']['openssl'] ? 'PASS' : 'FAIL' ) . PHP_EOL;

	// Plugins running on the site.
	$all_plugins    = get_plugins();
	$mu_plugins     = get_mu_plugins();
	$active_plugins = get_option( 'active_plugins', array() );
	$plugin_updates = get_plugin_updates();

	if ( 0 < count( $mu_plugins ) ) {
		$report .= PHP_EOL . '-- WordPress Plugins: Must-Use' . PHP_EOL . PHP_EOL;

		foreach ( $mu_plugins as $mu_plugin ) {
			$report .= sprintf( '- %s: %s', $mu_plugin['Name'], $mu_plugin['Version'] ) . PHP_EOL;
		}
	}

	$report .= PHP_EOL . '-- WordPress Plugins: Active' . PHP_EOL . PHP_EOL;
	foreach ( $all_plugins as $path => $plugin ) {
		if ( ! in_array( $path, $active_plugins, true ) ) {
			continue;
		}

		$report .= sprintf(
			'- %s: %s%s',
			$plugin['Name'],
			$plugin['Version'],
			array_key_exists( $path, $plugin_updates ) ? ' (update available)' : ''
		) . PHP_EOL;
	}

	$report .= PHP_EOL . '-- WordPress Plugins: Inactive' . PHP_EOL . PHP_EOL;
	foreach ( $all_plugins as $path => $plugin ) {
		if ( in_array( $path, $active_plugins, true ) ) {
			continue;
		}

		$report .= sprintf(
			'- %s: %s%s',
			$plugin['Name'],
			$plugin['Version'],
			array_key_exists( $path, $plugin_updates ) ? ' (update available)' : ''
		) . PHP_EOL;
	}

	if ( is_multisite() ) {
		$ms_all_plugins    = wp_get_active_network_plugins();
		$ms_active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		$report .= PHP_EOL . '-- WordPress Plugins: Network Active' . PHP_EOL . PHP_EOL;
		foreach ( $ms_all_plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_ms_plugins ) ) {
				continue;
			}

			$plugin  = get_plugin_data( $plugin_path );
			$report .= sprintf(
				'- %s: %s%s',
				$plugin['Name'],
				$plugin['Version'],
				array_key_exists( $path, $plugin_updates ) ? ' (update available)' : ''
			) . PHP_EOL;
		}
	}

	// Web server details.
	$report .= PHP_EOL . '-- Server Configuration' . PHP_EOL . PHP_EOL;
	$report .= 'PHP Version:         ' . PHP_VERSION . PHP_EOL;
	$report .= 'MySQL Version:       ' . $wpdb->db_version() . PHP_EOL;
	$report .= 'Web Server:          ' . $_SERVER['SERVER_SOFTWARE'] . PHP_EOL;
	$report .= 'Libxml version       ' . LIBXML_DOTTED_VERSION . PHP_EOL;

	// PHP Configuration.
	$report .= PHP_EOL . '-- PHP Configuration' . PHP_EOL . PHP_EOL;
	$report .= 'Memory Limit:        ' . ini_get( 'memory_limit' ) . PHP_EOL;
	$report .= 'Upload Max Size:     ' . ini_get( 'upload_max_filesize' ) . PHP_EOL;
	$report .= 'Post Max Size:       ' . ini_get( 'post_max_size' ) . PHP_EOL;
	$report .= 'Upload Max Filesize: ' . ini_get( 'upload_max_filesize' ) . PHP_EOL;
	$report .= 'Time Limit:          ' . ini_get( 'max_execution_time' ) . PHP_EOL;
	$report .= 'Max Input Vars:      ' . ini_get( 'max_input_vars' ) . PHP_EOL;
	$report .= 'Display Errors:      ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . PHP_EOL;

	return $report . PHP_EOL . '### End System Info ###';
}
