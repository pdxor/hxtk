jQuery(document).ready(function($) {
    // Tab Navigation
    $('.htk-nav-item').on('click', function() {
        const $this = $(this);
        const tabId = $this.data('tab');
        
        // Update active tab
        $('.htk-nav-item').removeClass('active');
        $this.addClass('active');
        
        // Show selected content
        $('.htk-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    // Task Management
    initTaskBoard();
    
    function initTaskBoard() {
        // Initialize sortable task lists
        $('.htk-task-list').sortable({
            connectWith: '.htk-task-list',
            placeholder: 'htk-task-placeholder',
            handle: 'h4',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
            },
            stop: function(e, ui) {
                const taskId = ui.item.data('id');
                const newStatus = ui.item.closest('.htk-task-column').data('status');
                updateTaskStatus(taskId, newStatus);
            }
        }).disableSelection();

        // Task modal
        const $taskModal = $('#htk-task-modal');
        const $taskForm = $('#htk-task-form');

        // Initialize datepicker
        $('.htk-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0
        });

        // Open task modal
        $('.htk-add-task').on('click', function() {
            $taskForm[0].reset();
            $taskForm.find('input[name="task_id"]').val('');
            $taskModal.show();
        });

        // Edit task
        $(document).on('click', '.htk-task-card', function() {
            const taskId = $(this).data('id');
            loadTaskDetails(taskId);
        });

        // Close modal
        $('.htk-modal-close').on('click', function() {
            $(this).closest('.htk-modal').hide();
        });

        // Save task
        $taskForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'htk_save_task');
            formData.append('nonce', htkProject.nonce);

            $.ajax({
                url: htkProject.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $taskModal.hide();
                        location.reload(); // Refresh to show new task
                    } else {
                        alert(htkProject.strings.error);
                    }
                },
                error: function() {
                    alert(htkProject.strings.error);
                }
            });
        });
    }

    function updateTaskStatus(taskId, newStatus) {
        $.ajax({
            url: htkProject.ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_update_task_status',
                nonce: htkProject.nonce,
                task_id: taskId,
                status: newStatus
            },
            error: function() {
                alert(htkProject.strings.error);
                location.reload(); // Reload on error to restore original state
            }
        });
    }

    function loadTaskDetails(taskId) {
        // This would typically load task details via AJAX
        // For now, we'll just show the modal
        $('#htk-task-modal').show();
    }

    // Ideas Management
    initIdeasSection();
    
    function initIdeasSection() {
        const $ideaModal = $('#htk-idea-modal');
        const $ideaForm = $('#htk-idea-form');

        // Open idea modal
        $('.htk-add-idea').on('click', function() {
            $ideaForm[0].reset();
            $ideaModal.show();
        });

        // Save idea
        $ideaForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'htk_save_idea');
            formData.append('nonce', htkProject.nonce);

            $.ajax({
                url: htkProject.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $ideaModal.hide();
                        location.reload();
                    } else {
                        alert(htkProject.strings.error);
                    }
                },
                error: function() {
                    alert(htkProject.strings.error);
                }
            });
        });

        // Vote on idea
        $('.htk-vote-btn').on('click', function() {
            const $btn = $(this);
            const $card = $btn.closest('.htk-idea-card');
            const ideaId = $card.data('id');
            
            $.ajax({
                url: htkProject.ajaxurl,
                type: 'POST',
                data: {
                    action: 'htk_vote_idea',
                    nonce: htkProject.nonce,
                    idea_id: ideaId
                },
                success: function(response) {
                    if (response.success) {
                        const $count = $btn.siblings('.htk-vote-count');
                        $count.text(parseInt($count.text()) + 1);
                    }
                }
            });
        });
    }

    // Timeline Management
    initTimelineSection();
    
    function initTimelineSection() {
        const $eventModal = $('#htk-event-modal');
        const $eventForm = $('#htk-event-form');

        // Open event modal
        $('.htk-add-event').on('click', function() {
            $eventForm[0].reset();
            $eventModal.show();
        });

        // Initialize datepickers
        $('#event_start_date, #event_end_date').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            onSelect: function(selectedDate) {
                const option = this.id === 'event_start_date' ? 'minDate' : 'maxDate';
                const instance = $(this).data('datepicker');
                const date = $.datepicker.parseDate(
                    instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
                    selectedDate,
                    instance.settings
                );
                
                if (this.id === 'event_start_date') {
                    $('#event_end_date').datepicker('option', 'minDate', date);
                } else {
                    $('#event_start_date').datepicker('option', 'maxDate', date);
                }
            }
        });

        // Save event
        $eventForm.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'htk_save_timeline_event');
            formData.append('nonce', htkProject.nonce);

            $.ajax({
                url: htkProject.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $eventModal.hide();
                        location.reload();
                    } else {
                        alert(htkProject.strings.error);
                    }
                },
                error: function() {
                    alert(htkProject.strings.error);
                }
            });
        });
    }

    // General modal handling
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('htk-modal')) {
            $('.htk-modal').hide();
        }
    });

    // Escape key closes modals
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') {
            $('.htk-modal').hide();
        }
    });
}); 