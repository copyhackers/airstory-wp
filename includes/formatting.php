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
	$body = $doc->saveHTML( $doc->getElementsByTagName( 'body' )->item( 0 ) );

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
