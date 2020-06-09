/**
 * Scripting for the sunset notice.
 *
 * @package Airstory
 * @author  Liquid Web
 */
/* global ajaxurl */

( function ($) {
	'use strict';

	$(document.getElementById('airstory-sunset-notice'))
		.on('click', '.notice-dismiss', function () {
			$.post(ajaxurl, {
				action: 'airstory-dismiss-sunset-notice'
			});
		});

} )(jQuery);
