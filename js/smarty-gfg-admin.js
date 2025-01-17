jQuery(document).ready(function($) {
    $('.smarty-convert-images-button').on('click', function (e) {
        e.preventDefault(); // Prevent the default form submission

        var button = $(this);
        button.attr('disabled', true);

        $.ajax({
            url: smartyGoogleFeedGenerator.ajaxUrl,
            method: 'POST',
            data: {
                action: 'smarty_convert_images',
                nonce: smartyGoogleFeedGenerator.nonce
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

    $('.smarty-generate-feed-button').on('click', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var action = $(this).data('feed-action');
        var redirectUrl = '';

        switch (action) {
            case 'generate_product_feed':
                redirectUrl = smartyGoogleFeedGenerator.siteUrl + '/smarty-google-feed/';
                break;
            case 'generate_reviews_feed':
                redirectUrl = smartyGoogleFeedGenerator.siteUrl + '/smarty-google-reviews-feed/';
                break;
            case 'generate_csv_export':
                redirectUrl = smartyGoogleFeedGenerator.siteUrl + '/smarty-csv-export/';
                break;
            default:
                alert('Invalid action.');
                return;
        }

        window.open(redirectUrl, '_blank');
    });

    $('.smarty-excluded-categories').select2({
        width: '100%' // need to override the changed default
    });

    // Initialize Select2 with AJAX for the Google Product Category select element
    $('.smarty-select2-ajax').select2({
        ajax: {
            url: smartyGoogleFeedGenerator.ajaxUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    action: 'smarty_load_google_categories',
                    nonce: smartyGoogleFeedGenerator.nonce
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

    // Handle tab switching
    $(".smarty-gfg-nav-tab").click(function (e) {
        e.preventDefault();
        $(".smarty-gfg-nav-tab").removeClass("smarty-gfg-nav-tab-active");
        $(this).addClass("smarty-gfg-nav-tab-active");

        $(".smarty-gfg-tab-content").removeClass("active");
        $($(this).attr("href")).addClass("active");
    });

    // Load README.md
    $("#smarty-gfg-load-readme-btn").click(function () {
        const $content = $("#smarty-gfg-readme-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyGoogleFeedGenerator.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_gfg_load_readme",
                nonce: smartyGoogleFeedGenerator.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading README.md</p>");
                }
            },
        });
    });

    // Load CHANGELOG.md
    $("#smarty-gfg-load-changelog-btn").click(function () {
        const $content = $("#smarty-gfg-changelog-content");
        $content.html("<p>Loading...</p>");

        $.ajax({
            url: smartyGoogleFeedGenerator.ajaxUrl,
            type: "POST",
            data: {
                action: "smarty_gfg_load_changelog",
                nonce: smartyGoogleFeedGenerator.nonce,
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    $content.html(response.data);
                } else {
                    $content.html("<p>Error loading CHANGELOG.md</p>");
                }
            },
        });
    });
});
