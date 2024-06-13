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

    $(document).ready(function($) {
        $('.smarty-convert-images-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission

            var button = $(this);
            button.attr('disabled', true);

            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'smarty_convert_images',
                    nonce: smartyFeedGenerator.nonce
                },
                success: function (response) {
                    if (response.success) {
                        alert(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
                complete: function () {
                    button.attr('disabled', false);
                }
            });
        });
    });

    $(document).ready(function($) {
        $('.smarty-generate-feed-button').on('click', function(e) {
            e.preventDefault(); // Prevent the default form submission

            var action = $(this).data('feed-action');
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
                    return;
            }

            window.open(redirectUrl, '_blank');
        });
    });

    $(document).ready(function($) {
        $('.smarty-excluded-categories').select2({
            width: '100%' // need to override the changed default
        });
    });

    // Initialize Select2 with AJAX for the Google Product Category select element
    $(document).ready(function($) {
        $('.smarty-select2-ajax').select2({
            ajax: {
                url: smartyFeedGenerator.ajaxUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        action: 'load_google_categories',
                        nonce: smartyFeedGenerator.nonce
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.data
                    };
                },
                cache: true
            },
            minimumInputLength: 1,
            width: 'resolve'
        });
    });

    // Auto-hide the admin notices
	$(document).ready(function($) {
		setTimeout(function() {
			$(".smarty-auto-hide-notice").fadeTo(500, 0).slideUp(500, function(){
				$(this).remove(); 
			});
		}, 3000);
	});
});
