(function ($) {
	'use strict';

    /**
	 * All of the code for plugin admin JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed we will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables us to define handlers, for when the DOM is ready:
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
	 */

    $(document).ready(function ($) {
        //console.log('Document is ready');

        $('.smarty-convert-images-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            //console.log('Convert Images button clicked');

            var button = $(this);
            button.attr('disabled', true);
            //console.log('Button disabled');

            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'smarty_convert_images',
                    nonce: smartyFeedGenerator.nonce
                },
                success: function (response) {
                    //console.log('AJAX success:', response);
                    if (response.success) {
                        alert(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    //console.log('AJAX error:', error);
                    alert('AJAX Error: ' + error);
                },
                complete: function () {
                    button.attr('disabled', false);
                    //console.log('Button re-enabled');
                }
            });
        });

        // Handler for converting all images
        $('.smarty-convert-all-images-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            console.log('Convert All Images button clicked');

            var button = $(this);
            button.attr('disabled', true);
            console.log('Button disabled');

            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'smarty_convert_all_webp_images_to_png',
                    nonce: smartyFeedGenerator.nonce
                },
                success: function (response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        alert(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    console.log('AJAX error:', error);
                    alert('AJAX Error: ' + error);
                },
                complete: function () {
                    button.attr('disabled', false);
                    console.log('Button re-enabled');
                }
            });
        });

        $('.smarty-generate-feed-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            //console.log('Generate Feed button clicked');

            var action = $(this).data('feed-action');
            //console.log('Feed action:', action);
            var redirectUrl = '';

            switch (action) {
                case 'generate_product_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-google-feed/';
                    break;
                case 'generate_reviews_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-google-reviews-feed/';
                    break;
                case 'generate_csv_export':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-csv-export/';
                    break;
                default:
                    alert('Invalid action.');
                    //console.log('Invalid action:', action);
                    return;
            }

            //console.log('Opening URL:', redirectUrl);
            window.open(redirectUrl, '_blank');
        });

        //console.log('Initializing Select2 for .smarty-excluded-categories');
        $('.smarty-excluded-categories').select2({
            width: '100%' // need to override the changed default
        });

         //console.log('Initializing Select2 for .smarty-size-system');
        $('.smarty-size-system').select2({
            width: '100%' // need to override the changed default
        });

        //console.log('Initializing Select2 for .smarty-excluded-destination');
        $('.smarty-excluded-destination').select2({
            width: '100%' // need to override the changed default
        });

        //console.log('Initializing Select2 for .smarty-included-destination');
        $('.smarty-included-destination').select2({
            width: '100%' // need to override the changed default
        });

        //console.log('Initializing Select2 for .smarty-select2-ajax');
        $('.smarty-select2-ajax').select2({
            ajax: {
                url: smartyFeedGenerator.ajaxUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    //console.log('Select2 AJAX request params:', params);
                    return {
                        q: params.term, // search term
                        action: 'smarty_load_google_categories',
                        nonce: smartyFeedGenerator.nonce
                    };
                },
                processResults: function (data) {
                    //console.log('Select2 AJAX response data:', data);
                    return {
                        results: data.data
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            width: 'resolve'
        });

        //console.log('Setting up auto-hide for admin notices');
        setTimeout(function () {
            $(".smarty-auto-hide-notice").fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
                //console.log('Admin notice auto-hidden');
            });
        }, 3000);
    });
})(jQuery);
