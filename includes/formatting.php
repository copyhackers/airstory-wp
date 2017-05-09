<?php
/**
 * Formatters and other helpers to prepare Airstory content for WordPress.
 *
 * @package Airstory.
 */

namespace Airstory\Formatting;

use Airstory;

/**
 * Reduce the contents of an exported document to the contents of the <body> element.
 *
 * By default, the Airstory API will include a full HTML document, including the <!DOCTYPE>, <html>
 * tags, and more. WordPress only needs the actual post contents, so we'll load the provided HTML
 * into DOMDocument, reduce it to the <body>, then manually strip the opening and closing <body />.
 *
 * @param string $content The full HTML document contents.
 * @return string The contents of the document's <body> node.
 */
function get_body_contents( $content ) {
	$use_internal = libxml_use_internal_errors( true );

	$doc = new \DOMDocument( '1.0', 'UTF-8' );
	$doc->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NODEFDTD );

	// Will retrieve the entire <body> node.
	$body_node = $doc->getElementsByTagName( 'body' );

	if ( 0 === $body_node->length ) {
		return '';
	}

	$body = $doc->saveHTML( $body_node->item( 0 ) );

	// If an error occurred while parsing the data, return an empty string.
	if ( libxml_get_errors() ) {
		$body = '';
	}

	// Reset the original error handling approach for libxml.
	libxml_clear_errors();
	libxml_use_internal_errors( $use_internal );

	// If the body's empty at this point, no further work is necessary.
	if ( empty( $body ) ) {
		return $body;
	}

	// Strip opening and trailing <body> tags (plus any whitespace).
	$body = preg_replace( '/\<body\>\s*/i', '', $body );
	$body = preg_replace( '/\s*\<\/body\>/i', '', $body );

	return $body;
}
add_filter( 'airstory_before_insert_content', __NAMESPACE__ . '\get_body_contents', 1 );

/**
 * Sideload a single image from a remote URL.
 *
 * @param string $url      The remote URL for the image.
 * @param int    $post_id  Optional. The post the newly-uploaded image should be attached to.
 *                         Default is 0 (unattached).
 * @param array  $metadata Optional. Additional post meta keys to assign once the attachment post
 *                         has been created. These keys and values are assumed to be sanitized.
 *                         Default is an empty array.
 */
function sideload_image( $url, $post_id = 0, $metadata = array() ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp_file   = download_url( esc_url_raw( $url ) );
	$file_array = array(
		'name'     => basename( $url ),
		'tmp_name' => $tmp_file,
	);

	// Something went wrong downloading the image.
	if ( is_wp_error( $tmp_file ) ) {
		@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine
		trigger_error( esc_html( $tmp_file->get_error_message() ), E_USER_WARNING );

		return 0;
	}

	// Sideload the media.
	$image_id = media_handle_sideload( $file_array, $post_id );

	if ( is_wp_error( $image_id ) ) {
		@unlink( $file_array['tmp_name'] ); // @codingStandardsIgnoreLine
		trigger_error( esc_html( $image_id->get_error_message() ), E_USER_WARNING );

		return 0;
	}

	/*
	 * Finally, store post meta. We'll always set _airstory_origin (the original image URL), but any
	 * non-empty values in $metadata will also be set.
	 */
	add_post_meta( $image_id, '_airstory_origin', esc_url( $url ) );

	foreach ( (array) $metadata as $meta_key => $meta_value ) {
		if ( ! empty( $meta_value ) ) {
			update_post_meta( $image_id, $meta_key, $meta_value );
		}
	}

	return $image_id;
}

/**
 * Sideload media referenced from within the Airstory content.
 *
 * While this could be a good use for DOMDocument, that extension can get rather finicky. As we're
 * only replacing links to https://images.airstory.co, we can safely accomplish this with regex.
 *
 * @param int $post_id The ID of the post to scan for media to sideload.
 * @return int The number of replacements made.
 */
function sideload_images( $post_id ) {
	$post = get_post( $post_id );

	// Return early (with "0" replacements) if no matching post was found.
	if ( ! $post ) {
		return 0;
	}

	/*
	 * Use DOMDocument to find all images in the post content.
	 *
	 * To avoid DOMDocument::saveHTML() from destroying the inner contents, we'll temporarily inject
	 * a generic <div>.
	 */
	$use_internal = libxml_use_internal_errors( true );
	$body         = new \DOMDocument;
	$body->loadHTML( '<div>' . $post->post_content . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED );
	$images       = $body->getElementsByTagName( 'img' );
	$pattern      = '/^https?:\/\/images.airstory.co\//i';
	$replaced     = array();
	$replacements = 0;

	// Ensure media that gets sideloaded has the post_author set to the current user.
	add_filter( 'wp_insert_attachment_data', __NAMESPACE__ . '\set_attachment_author' );

	foreach ( $images as $image ) {
		$src = $image->getAttribute( 'src' );

		// Skip this image if it isn't Airstory-hosted media.
		if ( ! preg_match( $pattern, $src ) ) {
			continue;
		}

		// Ensure we only sideload each piece of media once.
		if ( isset( $replaced[ $src ] ) ) {
			$local_url = $replaced[ $src ];

		} else {
			$image_id = sideload_image( $src, $post_id, array(
				'_wp_attachment_image_alt' => sanitize_text_field( $image->getAttribute( 'alt' ) ),
			) );

			if ( ! $image_id ) {
				continue;
			}

			$local_url = wp_get_attachment_url( $image_id );

			// The most stressful of stress cases: the image that we just uploaded doesn't exist?
			if ( ! $local_url ) {
				continue;
			}

			// Store the new local URL, in case this image is used again.
			$replaced[ $src ] = $local_url;
		}

		$image->setAttribute( 'src', $local_url );
		$replacements++;
	}

	// If an error occurred while parsing the data, abort!
	if ( libxml_get_errors() ) {
		return 0;
	}

	// Reset the original error handling approach for libxml.
	libxml_clear_errors();
	libxml_use_internal_errors( $use_internal );

	// Save down the replacements.
	$content = strip_wrapping_div( $body->saveHTML( $body->getElementsByTagName( 'div' )->item( 0 ) ) );

	// If changes have been made, update the post.
	if ( $content !== $post->post_content ) {
		$post->post_content = $content;
		wp_update_post( $post );
	}

	return (int) $replacements;
}
add_action( 'airstory_import_post', __NAMESPACE__ . '\sideload_images' );

/**
 * Strip the <div> that Airstory wraps around the outer content by default.
 *
 * While this _could_ be targeted in get_body_contents(), this <div> may not be 100% consistent, so
 * breaking it out here can help should we ever need to remove it.
 *
 * @param string $content The post content, which may or may not be wrapped in an unnecessary div.
 * @return string The filtered $content, sans <div>.
 */
function strip_wrapping_div( $content ) {

	// Match any content inside a <div> with no attributes that wraps the entire $content.
	$regex = '/^\<div\>([\s\S]+)\<\/div\>$/i';

	return trim( preg_replace( $regex, '$1', trim( $content ) ) );
}
add_filter( 'airstory_before_insert_content', __NAMESPACE__ . '\strip_wrapping_div' );

/**
 * When inserting new attachments, set the post_author to match the current user.
 *
 * @param array $post The attachment's post object.
 * @return The $post array, with post_author set to match the author of the attachment's parent.
 */
function set_attachment_author( $post ) {
	if ( ! empty( $post['post_author'] ) ) {
		return $post;
	}

	$parent_post = get_post( $post['post_parent'] );

	if ( ! $parent_post ) {
		return $post;
	}

	$post['post_author'] = (int) $parent_post->post_author;

	return $post;
}
