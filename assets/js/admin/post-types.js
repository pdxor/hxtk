jQuery(document).ready(function($) {
    // Media uploader for team logo
    let mediaUploader;
    
    $('.htk-upload-logo').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const previewContainer = button.closest('.htk-media-upload').find('.htk-media-preview');
        const inputField = button.closest('.htk-media-upload').find('input[name="team_logo"]');
        const removeButton = button.siblings('.htk-remove-logo');

        // If media uploader exists, open it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create new media uploader
        mediaUploader = wp.media({
            title: htkAdmin.strings.selectLogo,
            button: {
                text: htkAdmin.strings.useLogo
            },
            multiple: false
        });

        // When image is selected
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update preview
            previewContainer.html(`<img src="${attachment.url}" alt="">`);
            
            // Update hidden input
            inputField.val(attachment.id);
            
            // Show remove button
            removeButton.show();
        });

        mediaUploader.open();
    });

    // Remove logo
    $('.htk-remove-logo').on('click', function() {
        const button = $(this);
        const container = button.closest('.htk-media-upload');
        
        container.find('.htk-media-preview').empty();
        container.find('input[name="team_logo"]').val('');
        button.hide();
    });

    // Team member selection
    $('.htk-add-member').on('click', function() {
        const button = $(this);
        const container = button.siblings('.htk-team-members');

        // Open participant selector modal
        openParticipantSelector(function(participant) {
            // Add selected participant to the team
            const memberHtml = `
                <div class="htk-team-member">
                    <input type="hidden" name="team_ids[]" value="${participant.id}">
                    <span>${participant.title}</span>
                    <button type="button" class="button htk-remove-member">
                        ${htkAdmin.strings.remove}
                    </button>
                </div>
            `;
            container.append(memberHtml);
        });
    });

    // Remove team member
    $(document).on('click', '.htk-remove-member', function() {
        $(this).closest('.htk-team-member').remove();
    });

    // Participant selector modal
    function openParticipantSelector(callback) {
        const modal = $(`
            <div class="htk-modal">
                <div class="htk-modal-content">
                    <div class="htk-modal-header">
                        <h2>${htkAdmin.strings.selectParticipant}</h2>
                        <button type="button" class="htk-modal-close">×</button>
                    </div>
                    <div class="htk-modal-body">
                        <input type="text" class="htk-participant-search" placeholder="${htkAdmin.strings.searchParticipants}">
                        <div class="htk-participant-list"></div>
                    </div>
                </div>
            </div>
        `).appendTo('body');

        // Load participants
        loadParticipants();

        // Handle search
        let searchTimeout;
        modal.find('.htk-participant-search').on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            searchTimeout = setTimeout(() => loadParticipants(searchTerm), 300);
        });

        // Handle participant selection
        modal.on('click', '.htk-participant-item', function() {
            const participant = {
                id: $(this).data('id'),
                title: $(this).text()
            };
            callback(participant);
            modal.remove();
        });

        // Close modal
        modal.find('.htk-modal-close').on('click', function() {
            modal.remove();
        });

        // Load participants via AJAX
        function loadParticipants(search = '') {
            const list = modal.find('.htk-participant-list');
            list.html('<div class="htk-loading">Loading...</div>');

            $.ajax({
                url: htkAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'htk_get_participants',
                    nonce: htkAdmin.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success) {
                        list.empty();
                        if (response.data.length) {
                            response.data.forEach(function(participant) {
                                list.append(`
                                    <div class="htk-participant-item" data-id="${participant.ID}">
                                        ${participant.post_title}
                                    </div>
                                `);
                            });
                        } else {
                            list.html(`<div class="htk-no-results">${htkAdmin.strings.noParticipants}</div>`);
                        }
                    }
                },
                error: function() {
                    list.html(`<div class="htk-error">${htkAdmin.strings.errorLoading}</div>`);
                }
            });
        }
    }
}); 