<?php
/**
 * Core functionality for Airstory.
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

	$doc = new \DOMDocument;
	$doc->loadHTML( $content, LIBXML_HTML_NODEFDTD );

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
	libxml_use_internal_errors( $use_internal );

	// Strip opening and trailing <body> tags (plus any whitespace).
	$body = preg_replace( '/\<body\>\s*/i', '', $body );
	$body = preg_replace( '/\s*\<\/body\>/i', '', $body );

	return $body;
}
add_filter( 'airstory_before_insert_content', __NAMESPACE__ . '\get_body_contents', 1 );

/**
 * Sideload media referenced from within the Airstory content.
 *
 * While this could be a good use for DOMDocument, that extension can get rather finicky. As we're
 * only replacing links to https://images.airstory.co, we can safely accomplish this with regex.
 *
 * @param string $content The post content.
 * @return string The filtered post contents.
 */
function sideload_images( $content ) {
	$pattern = '/["\'](https?:\/\/images.airstory.co\/[^"\']+)/i';
	preg_match_all( $pattern, $content, $matches );

	foreach ( array_unique( $matches['1'] ) as $remote ) {
		$local = media_sideload_image( esc_url_raw( $remote ), 0, null, 'src' );

		if ( is_wp_error( $local ) ) {
			continue;
		}

		$content = str_replace( $remote, $local, $content );
	}

	return $content;
}
