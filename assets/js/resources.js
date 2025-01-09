jQuery(document).ready(function($) {
    // Handle mentor request form submission
    $('#htk-mentor-request-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'htk_submit_mentor_request',
            nonce: htkResources.nonce,
            area_of_interest: $('#area-of-interest').val(),
            experience_level: $('#experience-level').val(),
            message: $('#message').val()
        };
        
        $.ajax({
            url: htkResources.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Clear form
                    $('#htk-mentor-request-form')[0].reset();
                    
                    // Show success message
                    const successMessage = $('<div class="htk-message success"></div>')
                        .text(response.data.message)
                        .insertBefore('#htk-mentor-request-form');
                    
                    // Remove message after 5 seconds
                    setTimeout(function() {
                        successMessage.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            },
            error: function() {
                // Show error message
                const errorMessage = $('<div class="htk-message error"></div>')
                    .text('An error occurred. Please try again.')
                    .insertBefore('#htk-mentor-request-form');
                
                setTimeout(function() {
                    errorMessage.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        });
    });

    // Resource filtering
    $('.htk-resource-filter').on('change', function() {
        const selectedType = $(this).val();
        
        if (selectedType === 'all') {
            $('.htk-resource-card').show();
        } else {
            $('.htk-resource-card').each(function() {
                const resourceType = $(this).find('.htk-resource-type').text();
                $(this).toggle(resourceType === selectedType);
            });
        }
    });
});