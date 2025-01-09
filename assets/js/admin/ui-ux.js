jQuery(document).ready(function($) {
    // Initialize UI components
    const initUI = () => {
        initAccessibilitySettings();
        initFeedbackWidget();
        initNotifications();
        initSkipLinks();
        initFocusManagement();
    };

    // Accessibility Settings
    const initAccessibilitySettings = () => {
        // Font size control
        $('select[name="htk_font_size"]').on('change', function() {
            const fontSize = $(this).val();
            $('body').removeClass('htk-font-small htk-font-medium htk-font-large')
                    .addClass(`htk-font-${fontSize}`);
            
            // Save setting via AJAX
            saveSetting('htk_font_size', fontSize);
        });

        // High contrast toggle
        $('input[name="htk_high_contrast"]').on('change', function() {
            const highContrast = $(this).prop('checked');
            $('body').toggleClass('htk-high-contrast', highContrast);
            
            // Save setting via AJAX
            saveSetting('htk_high_contrast', highContrast);
        });
    };

    // Feedback Widget
    const initFeedbackWidget = () => {
        const $widget = $('.htk-feedback-widget');
        const $toggle = $('.htk-feedback-toggle');
        const $form = $('.htk-feedback-form');
        
        $toggle.on('click', function() {
            $form.toggleClass('active');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.htk-feedback-widget').length) {
                $form.removeClass('active');
            }
        });

        $('#htk-feedback-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'htk_submit_feedback');
            formData.append('nonce', htkUI.nonce);

            $.ajax({
                url: htkUI.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification(htkUI.strings.feedbackSuccess, 'success');
                        $form.removeClass('active');
                        e.target.reset();
                    } else {
                        showNotification(response.data || htkUI.strings.feedbackError, 'error');
                    }
                },
                error: function() {
                    showNotification(htkUI.strings.feedbackError, 'error');
                }
            });
        });
    };

    // Notification System
    const showNotification = (message, type = 'info') => {
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
    };

    // Skip Links
    const initSkipLinks = () => {
        $('.htk-skip-link').on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).attr('href');
            $(targetId).attr('tabindex', '-1').focus();
        });
    };

    // Focus Management
    const initFocusManagement = () => {
        // Handle modal focus trap
        $('.htk-modal').on('show', function() {
            const $modal = $(this);
            const $focusableElements = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const $firstFocusable = $focusableElements.first();
            const $lastFocusable = $focusableElements.last();

            $firstFocusable.focus();

            $modal.on('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === $firstFocusable[0]) {
                            e.preventDefault();
                            $lastFocusable.focus();
                        }
                    } else {
                        if (document.activeElement === $lastFocusable[0]) {
                            e.preventDefault();
                            $firstFocusable.focus();
                        }
                    }
                }
            });
        });
    };

    // Save Settings Helper
    const saveSetting = (setting, value) => {
        $.ajax({
            url: htkUI.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_save_setting',
                nonce: htkUI.nonce,
                setting: setting,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    showNotification(__('Settings saved successfully', 'htk'), 'success');
                } else {
                    showNotification(__('Failed to save settings', 'htk'), 'error');
                }
            }
        });
    };

    // Initialize everything
    initUI();
}); 