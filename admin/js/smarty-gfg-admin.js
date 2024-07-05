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

        $('#smarty-gfg-delete-logs-button').on('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete all logs?')) {
                $.post(
                    smartyFeedGenerator.ajaxUrl,
                    {
                        action: 'smarty_gfg_clear_logs',
                        nonce: smartyFeedGenerator.nonce,
                    },
                    function(response) {
                        if (response.success) {
                            alert('Logs cleared.');
                            location.reload();
                        } else {
                            alert('Failed to clear logs.');
                        }
                    }
                );
            }
        });
        
        $('.smarty-gfg-convert-images-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            //console.log('Convert Images button clicked');

            var button = $(this);
            button.attr('disabled', true);
            //console.log('Button disabled');

            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'smarty_gfg_convert_images',
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
        $('.smarty-gfg-convert-all-images-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            //console.log('Convert All Images button clicked');

            var button = $(this);
            button.attr('disabled', true);
            //console.log('Button disabled');

            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'smarty_gfg_convert_all_webp_images_to_png',
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

        $('.smarty-gfg-generate-feed-button').on('click', function (e) {
            e.preventDefault(); // Prevent the default form submission
            //console.log('Generate Feed button clicked');

            var action = $(this).data('feed-action');
            //console.log('Feed action:', action);
            var redirectUrl = '';

            switch (action) {
                case 'gfg_generate_google_products_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-google-products-feed/';
                    break;
                case 'gfg_generate_google_reviews_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-google-reviews-feed/';
                    break;
                case 'gfg_generate_google_csv_export':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-google-csv-export/';
                    break;
                case 'gfg_generate_bing_products_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-bing-products-feed/';
                    break;
                case 'gfg_generate_bing_txt_feed':
                    redirectUrl = smartyFeedGenerator.siteUrl + '/smarty-bing-txt-feed/';
                    break;
                default:
                    alert('Invalid action.');
                    //console.log('Invalid action:', action);
                    return;
            }

            //console.log('Opening URL:', redirectUrl);
            window.open(redirectUrl, '_blank');
        });

        $('.smarty-gfg-excluded-categories, .smarty-gfg-excluded-destination, .smarty-gfg-included-destination, .smarty-gfg-excluded-countries, .smarty-gfg-condition, .smarty-gfg-size-system, .smarty-gfg-reviews-ratings, .smarty-select2-ajax').select2({
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
                        action: 'smarty_gfg_load_google_categories',
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
            allowClear: true,
            placeholder: "Select a Google Category",
            minimumInputLength: 1,
            width: 'resolve'
        });

        function updateCustomLabelInputs() {
            $('.smarty-gfg-custom-label-logic ').each(function() {
                var logic = $(this).val();
                var inputField = $(this).closest('tr').find('.custom-label-input');
                var label = $(this).closest('tr').find('td:first').text().trim().toLowerCase().replace(/\s+/g, '_');
                var days = $('input[name="smarty_' + label + '_days"]').val();
                var value = $('input[name="smarty_' + label + '_value"]').val();
                var categories = $('select[name="smarty_' + label + '_categories[]"]').val() || [];

                switch (logic) {
                    case 'older_than_days':
                    case 'not_older_than_days':
                    case 'most_ordered_days':
                        inputField.html('<input type="number" name="smarty_' + label + '_days" value="' + (days || '') + '" class="small-text" />');
                        break;
                    case 'high_rating_value':
                        inputField.html('<label>...</label>');
                            break;
                    case 'has_sale_price':
                        inputField.html('<label>...</label>');
                        break;
                    case 'category':
                        inputField.html('<select name="smarty_' + label + '_categories[]" multiple="multiple" style="width:50%;"></select>');
                        populateCategories(label, categories);
                        break;
                    default:
                        inputField.html('<input type="text" name="smarty_' + label + '_default" value="' + (value || '') + '" class="regular-text" />');
                        break;
                }
            });
        }
        
        function populateCategories(label, selectedCategories) {
            var select = $('select[name="smarty_' + label + '_categories[]"]');
            $.ajax({
                url: smartyFeedGenerator.ajaxUrl,
                method: 'GET',
                data: {
                    action: 'smarty_gfg_get_woocommerce_categories',
                    nonce: smartyFeedGenerator.nonce
                },
                success: function(response) {
                    if (response.success) {
                        select.empty();
                        $.each(response.data, function(index, category) {
                            var selected = selectedCategories.includes(category.term_id.toString()) ? 'selected' : '';
                            select.append('<option value="' + category.term_id + '" ' + selected + '>' + category.name + '</option>');
                        });
                        select.select2();
                    }
                }
            });
        }
        
        updateCustomLabelInputs();

        $(document).on('change', '.smarty-gfg-custom-label-logic ', function() {
            updateCustomLabelInputs();
        });        

        //console.log('Setting up auto-hide for admin notices');
        setTimeout(function () {
            $(".smarty-gfg-auto-hide-notice").fadeTo(500, 0).slideUp(500, function () {
                $(this).remove();
                //console.log('Admin notice auto-hidden');
            });
        }, 3000);
    });
})(jQuery);
