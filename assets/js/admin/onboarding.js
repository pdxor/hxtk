jQuery(document).ready(function($) {
    // Track completion progress
    function updateProgress() {
        const totalSteps = $('.htk-step').length;
        const completedSteps = $('.htk-step.completed').length;
        const progress = (completedSteps / totalSteps) * 100;
        
        $('.htk-progress').css('width', progress + '%');
        $('.htk-progress-text').text(Math.round(progress) + '% Complete');
    }

    // Mark steps as completed
    $('.htk-step').each(function(index) {
        const $step = $(this);
        const stepNumber = index + 1;
        
        // Add step number
        $step.find('.htk-step-number').text(stepNumber);
        
        // Check if step is completed (you might want to store this in user meta)
        if (localStorage.getItem('htk_step_' + $step.data('step'))) {
            $step.addClass('completed');
        }
    });

    // Update initial progress
    updateProgress();

    // Handle step completion
    $('.htk-step .button').on('click', function() {
        const $step = $(this).closest('.htk-step');
        const stepId = $step.data('step');
        
        // Store completion in localStorage (you might want to use AJAX to store in user meta)
        localStorage.setItem('htk_step_' + stepId, 'completed');
        
        $step.addClass('completed');
        updateProgress();
    });

    // FAQ Accordion
    $('.htk-question').on('click', function() {
        const $faqItem = $(this).closest('.htk-faq-item');
        const $answer = $faqItem.find('.htk-answer');
        
        if ($faqItem.hasClass('active')) {
            $faqItem.removeClass('active');
            $answer.slideUp();
        } else {
            $('.htk-faq-item.active .htk-answer').slideUp();
            $('.htk-faq-item.active').removeClass('active');
            
            $faqItem.addClass('active');
            $answer.slideDown();
        }
    });

    // Initialize tooltips
    $('[data-tooltip]').tooltip();
}); 