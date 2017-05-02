<?php
/**
 * Core functionality for Airstory.
 *
 * @package Airstory.
 */

namespace Airstory\Core;

use Airstory;

/**
 * Verify whether or not the current environment meets plugin requirements.
 *
 * The requirements for the plugin should be documented in the plugin README, but include:
 *
 * - PHP >= 5.3        - Namespace support, though the plugin will fail before reaching this check
 *                       if namespaces are unsupported.
 * - dom extension     - Used by DOMDocument in formatters.php.
 * - mcrypt extension  - Used as a backup for older systems that don't support PHP 7's random_bytes().
 * - openssl extension - Used to securely encrypt Airstory credentials.
 *
 * @return bool True if all requirements are met, false otherwise.
 */
function check_requirements() {
	$requirements_met = true;

	// Find any missing extensions; $missing_exts will contain any that fail extension_loaded().
	$extensions = array( 'dom', 'mcrypt', 'openssl' );
	if ( array_filter( $extensions, 'extension_loaded' ) !== $extensions ) {
		$requirements_met = false;
	}

	return $requirements_met;
}

/**
 * Deactivate the plugin and notify the user if the plugin doesn't meet requirements.
 *
 * @global $pagenow
 */
function deactivate_if_missing_requirements() {
	global $pagenow;

	if ( 'plugins.php' !== $pagenow || check_requirements() ) {
		return;
	}

	// Deactivate the plugin.
	deactivate_plugins( plugin_basename( AIRSTORY_MAIN_FILE ) );
	unset( $_GET['activate'] );

	// Display a notice, informing the user why the plugin was deactivated.
	add_action( 'admin_notices', __NAMESPACE__ . '\notify_user_of_missing_requirements' );
}
add_action( 'admin_init', __NAMESPACE__ . '\deactivate_if_missing_requirements' );

/**
 * Notify the user of missing plugin requirements and direct them to more detailed information.
 *
 * @todo Fill in the FAQ URL.
 */
function notify_user_of_missing_requirements() {
?>

	<div class="notice notice-warning">
		<p><?php esc_html_e( 'The Airstory plugin is missing one or more of its dependencies, so it\'s automatically been deactivated.', 'airstory' ); ?></p>
		<p><?php echo wp_kses_post( __( 'For more information, <a href="#" target="_blank">please see the plugin\'s <abbr title="Frequently Asked Questions">FAQ</abbr></a>.', 'airstory' ) );?></p>
	</div>

<?php
}

/**
 * Given an Airstory project and document UUIDs, call out to the Airstory API and assemble a
 * WordPress post.
 *
 * @param Airstory\API $api         An instance of the Airstory API class.
 * @param string       $project_id  The Airstory project UUID.
 * @param string       $document_id The Airstory document UUID.
 * @return int|WP_Error The ID of the newly-created post or a WP_Error object if anything went
 *                      wrong during the creation of the post.
 */
function import_document( Airstory\API $api, $project_id, $document_id ) {
	$document = $api->get_document( $project_id, $document_id );

	// Something went wrong getting metadata about the document.
	if ( is_wp_error( $document ) ) {
		return $document;
	}

	// Begin assembling the post.
	$post = array(
		'post_title'   => sanitize_text_field( $document->title ),
		'post_status'  => 'draft',
		'post_type'    => 'post',
		'post_content' => '',
	);

	// Next, retrieve the post content.
	$contents = $api->get_document_content( $project_id, $document_id );

	// Unable to retrieve the rendered content.
	if ( is_wp_error( $contents ) ) {
		return $contents;
	}

	/**
	 * Filters the Airstory document content before inserting it into the wp_insert_post() array.
	 *
	 * @param string $document The compiled, HTML response from Airstory.
	 */
	$contents = apply_filters( 'airstory_before_insert_content', $contents );
	$post['post_content'] = wp_kses_post( $contents );

	/**
	 * Filter arguments for new posts from Airstory before they're inserted into the database.
	 *
	 * @see wp_insert_post()
	 *
	 * @param array $post An array of arguments for wp_insert_post().
	 */
	$post = apply_filters( 'airstory_before_insert_post', $post );

	// Finally, insert the post.
	return wp_insert_post( $post );
}

/**
 * Reduce the contents of an exported document to the contents of the <body> element.
 *
 * By default, the Airstory API will include a full HTML document, including the <!DOCTYPE>, <html>
 * tags, and more.
 *
 * @param string $document The full HTML document.
 * @return string $body The contents of the document's <body> node.
 *
 * @todo Use DOMDocument to filter down the $document contents.
 */
function get_body_contents( $document ) {
	return '';
}
