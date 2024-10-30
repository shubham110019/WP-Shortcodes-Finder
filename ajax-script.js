jQuery(document).ready(function($) {
    $('#sf_shortcode_form').on('submit', function(e) {
        e.preventDefault(); // Prevent the default form submission

        var shortcode = $('#sf_shortcode').val(); // Get selected shortcode

        // Show loading spinner
        $('#sf_loading').show();
        $('#sf_results').html(''); // Clear previous results

        $.ajax({
            type: 'POST',
            url: sf_ajax_object.ajax_url,
            data: {
                action: 'sf_get_shortcode_usage',
                shortcode: shortcode
            },
            success: function(response) {
                // Hide loading spinner
                $('#sf_loading').hide();

                // Display results
                if (response.success) {
                    $('#sf_results').html(response.data); // Update the results container
                } else {
                    $('#sf_results').html('<p>' + response.data + '</p>'); // Display error message
                }
            },
            error: function() {
                // Hide loading spinner and show error message
                $('#sf_loading').hide();
                $('#sf_results').html('<p>An error occurred. Please try again.</p>');
            }
        });
    });
});
