/**
 * Scripting for the Tools > Airstory page.
 *
 * @package Airstory
 * @author  Liquid Web
 */
 /* global airstoryTools */

( function () {
	'use strict';

	/**
	 * Given a URL and a DOM element, send an asynchronous request to the URL to ensure
	 * this site can communicate.
	 *
	 * @param {string} url - The URL to request.
	 * @param {Element} el - The DOM element to be updated with the result.
	 */
	var checkConnectivity = function ( url, el ) {
		var xhr = new XMLHttpRequest();

		xhr.open( 'HEAD', url, true );
		xhr.onload = function () {
			el.innerHTML = airstoryTools.statusIcons.success;
		}

		xhr.onerror = function () {
			el.innerHTML = airstoryTools.statusIcons.failure;
		}

		xhr.send();
	};

	// Verify connectivity with this site's WP REST API.
	checkConnectivity( airstoryTools.restApiUrl, document.getElementById( 'airstory-restapi-check' ) );

	// Outbound communication with Airstory's API.
	checkConnectivity( 'https://api.airstory.co/v1', document.getElementById( 'airstory-connection-check' ) );

} )();
