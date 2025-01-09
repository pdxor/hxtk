jQuery(document).ready(function($) {
    // Mentor Request Modal Management
    const $modal = $('#htk-mentor-modal');
    const $form = $('#htk-mentor-request-form');
    
    // Open modal when clicking request button
    $('.htk-request-mentor').on('click', function() {
        const mentorId = $(this).data('mentor-id');
        $('#mentor_id').val(mentorId);
        $modal.show();
    });
    
    // Close modal
    $('.htk-modal-close').on('click', function() {
        $modal.hide();
        $form[0].reset();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('htk-modal')) {
            $modal.hide();
            $form[0].reset();
        }
    });
    
    // Handle mentor request submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'htk_submit_mentor_request');
        formData.append('nonce', htkResources.nonce);
        
        $.ajax({
            url: htkResources.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(htkResources.strings.requestSuccess, 'success');
                    $modal.hide();
                    $form[0].reset();
                } else {
                    showNotification(response.data || htkResources.strings.requestFail, 'error');
                }
            },
            error: function() {
                showNotification(htkResources.strings.requestFail, 'error');
            }
        });
    });
    
    // Resource filtering
    let currentFilter = 'all';
    
    $('.htk-resource-filter').on('click', function() {
        const filter = $(this).data('filter');
        currentFilter = filter;
        
        $('.htk-resource-filter').removeClass('active');
        $(this).addClass('active');
        
        if (filter === 'all') {
            $('.htk-resource-category').show();
        } else {
            $('.htk-resource-category').hide();
            $(`.htk-resource-category[data-category="${filter}"]`).show();
        }
    });
    
    // Resource search
    $('#htk-resource-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.htk-resource-item').each(function() {
            const $item = $(this);
            const title = $item.find('h3').text().toLowerCase();
            const description = $item.find('p').text().toLowerCase();
            
            if (title.includes(searchTerm) || description.includes(searchTerm)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    });
    
    // Notification system
    function showNotification(message, type = 'info') {
        const $notification = $('<div>', {
            class: `htk-notification ${type}`,
            text: message
        });
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.addClass('show');
        }, 100);
        
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Resource link tracking
    $('.htk-resource-item').on('click', function() {
        const resourceName = $(this).find('h3').text();
        const resourceUrl = $(this).attr('href');
        
        // Track resource usage (you can implement analytics here)
        console.log(`Resource clicked: ${resourceName} (${resourceUrl})`);
    });
}); 