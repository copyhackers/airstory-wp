<?php
/**
 * Core functionality for Airstory.
 *
 * @package Airstory.
 */

namespace Airstory\Core;

use Airstory;

/**
 * Load the plugin textdomain.
 */
function load_textdomain() {
	load_plugin_textdomain( 'airstory', false, basename( AIRSTORY_DIR ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\load_textdomain' );

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
