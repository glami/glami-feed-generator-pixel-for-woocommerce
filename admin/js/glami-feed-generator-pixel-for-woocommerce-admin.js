(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function() {
		if ($('#glami_categories_map').length > 0) {
			$('#glami_categories_map').select2();
		}

		if ($('.glami-select').length > 0) {
			$(".glami-select").each(function () {
				$(this).select2();
			});
		}
	});
})( jQuery );



function generateGlamiFeed() {
	var $btn=jQuery('#woocommerce_glami-feed-generator-pixel-for-woocommerce_customize_button');
	$btn.prop( "disabled", true ).text("Generating feed...");
	jQuery('.glami-spinner .spinner').addClass( 'is-active');
	jQuery.ajax({
		type: "post",
		dataType: "json",
		url: glami_ajax_object.ajax_url,
		data: {
			action: 'glami_feed_run_ajax_event',
			nonce: glami_ajax_object.nonce
		},
		success: function(response){
			console.log(response);
			$btn.prop( "disabled", false );
			jQuery('.glami-spinner .spinner').removeClass( 'is-active');
			if (response.url) {
				$btn.text("Feed was generated!");
				jQuery('.glami-xml-ul code').text(response.url);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log(jqXHR);
			console.log(textStatus);
			console.log(errorThrown);
		}
	});
}
