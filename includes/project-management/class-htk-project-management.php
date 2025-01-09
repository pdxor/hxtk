<?php
namespace HTK\ProjectManagement;

class HTK_Project_Management {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_project_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_project_assets'));
        add_action('wp_ajax_htk_save_task', array($this, 'ajax_save_task'));
        add_action('wp_ajax_htk_update_task_status', array($this, 'ajax_update_task_status'));
        add_action('wp_ajax_htk_save_idea', array($this, 'ajax_save_idea'));
        add_action('wp_ajax_htk_vote_idea', array($this, 'ajax_vote_idea'));
        add_action('wp_ajax_htk_save_timeline_event', array($this, 'ajax_save_timeline_event'));
    }

    public function add_project_menu() {
        add_submenu_page(
            'htk-admin',
            __('Project Management', 'htk'),
            __('Project Management', 'htk'),
            'manage_options',
            'htk-project-management',
            array($this, 'render_project_page')
        );
    }

    public function enqueue_project_assets($hook) {
        if ('htk-1-0_page_htk-project-management' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'htk-project-css',
            HTK_PLUGIN_URL . 'assets/css/admin/project-management.css',
            array(),
            HTK_VERSION
        );

        wp_enqueue_script(
            'htk-project-js',
            HTK_PLUGIN_URL . 'assets/js/admin/project-management.js',
            array('jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker'),
            HTK_VERSION,
            true
        );

        wp_localize_script('htk-project-js', 'htkProject', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_project_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'htk'),
                'taskSaved' => __('Task saved successfully!', 'htk'),
                'ideaSaved' => __('Idea saved successfully!', 'htk'),
                'eventSaved' => __('Timeline event saved successfully!', 'htk'),
                'error' => __('An error occurred. Please try again.', 'htk')
            )
        ));
    }

    public function render_project_page() {
        $hackathon_id = isset($_GET['hackathon_id']) ? absint($_GET['hackathon_id']) : 0;
        if (!$hackathon_id) {
            $this->render_hackathon_selector();
            return;
        }

        ?>
        <div class="wrap htk-project-management">
            <h1><?php _e('Project Management', 'htk'); ?></h1>

            <div class="htk-project-nav">
                <button type="button" class="htk-nav-item active" data-tab="tasks">
                    <?php _e('Tasks', 'htk'); ?>
                </button>
                <button type="button" class="htk-nav-item" data-tab="ideas">
                    <?php _e('Ideas', 'htk'); ?>
                </button>
                <button type="button" class="htk-nav-item" data-tab="timeline">
                    <?php _e('Timeline', 'htk'); ?>
                </button>
            </div>

            <div class="htk-project-content">
                <!-- Tasks Section -->
                <div class="htk-tab-content active" id="tasks">
                    <?php $this->render_tasks_section($hackathon_id); ?>
                </div>

                <!-- Ideas Section -->
                <div class="htk-tab-content" id="ideas">
                    <?php $this->render_ideas_section($hackathon_id); ?>
                </div>

                <!-- Timeline Section -->
                <div class="htk-tab-content" id="timeline">
                    <?php $this->render_timeline_section($hackathon_id); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_hackathon_selector() {
        $hackathons = get_posts(array(
            'post_type' => 'hackathon',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        ?>
        <div class="wrap">
            <h1><?php _e('Select Hackathon Project', 'htk'); ?></h1>
            
            <div class="htk-hackathon-grid">
                <?php foreach ($hackathons as $hackathon) : ?>
                    <div class="htk-hackathon-card">
                        <?php if (has_post_thumbnail($hackathon->ID)) : ?>
                            <div class="htk-card-image">
                                <?php echo get_the_post_thumbnail($hackathon->ID, 'medium'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="htk-card-content">
                            <h2><?php echo esc_html($hackathon->post_title); ?></h2>
                            <p><?php echo wp_trim_words($hackathon->post_content, 20); ?></p>
                            <a href="<?php echo add_query_arg('hackathon_id', $hackathon->ID); ?>" 
                               class="button button-primary">
                                <?php _e('Manage Project', 'htk'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function render_tasks_section($hackathon_id) {
        $tasks = $this->get_tasks($hackathon_id);
        $statuses = array('todo', 'in_progress', 'review', 'done');
        ?>
        <div class="htk-tasks-container">
            <div class="htk-tasks-header">
                <h2><?php _e('Tasks', 'htk'); ?></h2>
                <button type="button" class="button htk-add-task">
                    <?php _e('Add Task', 'htk'); ?>
                </button>
            </div>

            <div class="htk-task-board">
                <?php foreach ($statuses as $status) : ?>
                    <div class="htk-task-column" data-status="<?php echo esc_attr($status); ?>">
                        <h3><?php echo esc_html($this->get_status_label($status)); ?></h3>
                        <div class="htk-task-list">
                            <?php
                            foreach ($tasks as $task) {
                                if ($task->status === $status) {
                                    $this->render_task_card($task);
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Task Form Modal -->
        <div id="htk-task-modal" class="htk-modal">
            <div class="htk-modal-content">
                <div class="htk-modal-header">
                    <h2><?php _e('Task Details', 'htk'); ?></h2>
                    <button type="button" class="htk-modal-close">×</button>
                </div>
                <div class="htk-modal-body">
                    <form id="htk-task-form">
                        <input type="hidden" name="hackathon_id" value="<?php echo esc_attr($hackathon_id); ?>">
                        <input type="hidden" name="task_id" value="">
                        
                        <div class="htk-form-row">
                            <label for="task_title"><?php _e('Title', 'htk'); ?></label>
                            <input type="text" id="task_title" name="title" required>
                        </div>

                        <div class="htk-form-row">
                            <label for="task_description"><?php _e('Description', 'htk'); ?></label>
                            <textarea id="task_description" name="description" rows="4"></textarea>
                        </div>

                        <div class="htk-form-row">
                            <label for="task_assigned_to"><?php _e('Assign To', 'htk'); ?></label>
                            <select id="task_assigned_to" name="assigned_to">
                                <option value=""><?php _e('Select Team Member', 'htk'); ?></option>
                                <?php
                                $team_ids = get_post_meta($hackathon_id, '_team_ids', true);
                                if ($team_ids) {
                                    foreach ($team_ids as $member_id) {
                                        $member = get_post($member_id);
                                        if ($member) {
                                            printf(
                                                '<option value="%d">%s</option>',
                                                $member_id,
                                                esc_html($member->post_title)
                                            );
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="htk-form-row">
                            <label for="task_due_date"><?php _e('Due Date', 'htk'); ?></label>
                            <input type="text" id="task_due_date" name="due_date" class="htk-datepicker">
                        </div>

                        <div class="htk-form-row">
                            <label for="task_priority"><?php _e('Priority', 'htk'); ?></label>
                            <select id="task_priority" name="priority">
                                <option value="low"><?php _e('Low', 'htk'); ?></option>
                                <option value="medium"><?php _e('Medium', 'htk'); ?></option>
                                <option value="high"><?php _e('High', 'htk'); ?></option>
                            </select>
                        </div>

                        <div class="htk-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Save Task', 'htk'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_ideas_section($hackathon_id) {
        $ideas = $this->get_ideas($hackathon_id);
        ?>
        <div class="htk-ideas-container">
            <div class="htk-ideas-header">
                <h2><?php _e('Ideas', 'htk'); ?></h2>
                <button type="button" class="button htk-add-idea">
                    <?php _e('Add Idea', 'htk'); ?>
                </button>
            </div>

            <div class="htk-ideas-grid">
                <?php foreach ($ideas as $idea) : ?>
                    <div class="htk-idea-card" data-id="<?php echo esc_attr($idea->id); ?>">
                        <h3><?php echo esc_html($idea->title); ?></h3>
                        <p><?php echo esc_html($idea->description); ?></p>
                        <div class="htk-idea-meta">
                            <span class="htk-votes">
                                <button type="button" class="htk-vote-btn">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                </button>
                                <span class="htk-vote-count"><?php echo esc_html($idea->votes); ?></span>
                            </span>
                            <span class="htk-submitted-by">
                                <?php
                                $user = get_userdata($idea->submitted_by);
                                echo esc_html($user ? $user->display_name : __('Unknown', 'htk'));
                                ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Idea Form Modal -->
        <div id="htk-idea-modal" class="htk-modal">
            <div class="htk-modal-content">
                <div class="htk-modal-header">
                    <h2><?php _e('Add Idea', 'htk'); ?></h2>
                    <button type="button" class="htk-modal-close">×</button>
                </div>
                <div class="htk-modal-body">
                    <form id="htk-idea-form">
                        <input type="hidden" name="hackathon_id" value="<?php echo esc_attr($hackathon_id); ?>">
                        
                        <div class="htk-form-row">
                            <label for="idea_title"><?php _e('Title', 'htk'); ?></label>
                            <input type="text" id="idea_title" name="title" required>
                        </div>

                        <div class="htk-form-row">
                            <label for="idea_description"><?php _e('Description', 'htk'); ?></label>
                            <textarea id="idea_description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="htk-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Submit Idea', 'htk'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_timeline_section($hackathon_id) {
        $events = $this->get_timeline_events($hackathon_id);
        ?>
        <div class="htk-timeline-container">
            <div class="htk-timeline-header">
                <h2><?php _e('Project Timeline', 'htk'); ?></h2>
                <button type="button" class="button htk-add-event">
                    <?php _e('Add Event', 'htk'); ?>
                </button>
            </div>

            <div class="htk-timeline">
                <?php foreach ($events as $event) : ?>
                    <div class="htk-timeline-event" data-id="<?php echo esc_attr($event->id); ?>">
                        <div class="htk-event-marker"></div>
                        <div class="htk-event-content">
                            <h3><?php echo esc_html($event->title); ?></h3>
                            <p><?php echo esc_html($event->description); ?></p>
                            <div class="htk-event-meta">
                                <span class="htk-event-date">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event->start_date))); ?>
                                </span>
                                <span class="htk-event-type">
                                    <?php echo esc_html($this->get_event_type_label($event->event_type)); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Event Form Modal -->
        <div id="htk-event-modal" class="htk-modal">
            <div class="htk-modal-content">
                <div class="htk-modal-header">
                    <h2><?php _e('Add Timeline Event', 'htk'); ?></h2>
                    <button type="button" class="htk-modal-close">×</button>
                </div>
                <div class="htk-modal-body">
                    <form id="htk-event-form">
                        <input type="hidden" name="hackathon_id" value="<?php echo esc_attr($hackathon_id); ?>">
                        
                        <div class="htk-form-row">
                            <label for="event_title"><?php _e('Title', 'htk'); ?></label>
                            <input type="text" id="event_title" name="title" required>
                        </div>

                        <div class="htk-form-row">
                            <label for="event_description"><?php _e('Description', 'htk'); ?></label>
                            <textarea id="event_description" name="description" rows="4"></textarea>
                        </div>

                        <div class="htk-form-row">
                            <label for="event_type"><?php _e('Event Type', 'htk'); ?></label>
                            <select id="event_type" name="event_type">
                                <option value="milestone"><?php _e('Milestone', 'htk'); ?></option>
                                <option value="deadline"><?php _e('Deadline', 'htk'); ?></option>
                                <option value="meeting"><?php _e('Meeting', 'htk'); ?></option>
                                <option value="other"><?php _e('Other', 'htk'); ?></option>
                            </select>
                        </div>

                        <div class="htk-form-row">
                            <label for="event_start_date"><?php _e('Start Date', 'htk'); ?></label>
                            <input type="text" id="event_start_date" name="start_date" class="htk-datepicker" required>
                        </div>

                        <div class="htk-form-row">
                            <label for="event_end_date"><?php _e('End Date', 'htk'); ?></label>
                            <input type="text" id="event_end_date" name="end_date" class="htk-datepicker">
                        </div>

                        <div class="htk-form-actions">
                            <button type="submit" class="button button-primary">
                                <?php _e('Save Event', 'htk'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    // Helper methods for database operations
    private function get_tasks($hackathon_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_tasks';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE hackathon_id = %d ORDER BY priority DESC",
            $hackathon_id
        ));
    }

    private function get_ideas($hackathon_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_ideas';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE hackathon_id = %d ORDER BY votes DESC",
            $hackathon_id
        ));
    }

    private function get_timeline_events($hackathon_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_timeline_events';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE hackathon_id = %d ORDER BY start_date ASC",
            $hackathon_id
        ));
    }

    // AJAX handlers
    public function ajax_save_task() {
        check_ajax_referer('htk_project_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $task_data = array(
            'hackathon_id' => absint($_POST['hackathon_id']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'assigned_to' => absint($_POST['assigned_to']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'priority' => sanitize_text_field($_POST['priority']),
            'status' => 'todo'
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_tasks';
        
        if ($wpdb->insert($table_name, $task_data)) {
            wp_send_json_success(array(
                'message' => __('Task saved successfully!', 'htk'),
                'task_id' => $wpdb->insert_id
            ));
        }

        wp_send_json_error(__('Failed to save task.', 'htk'));
    }

    public function ajax_update_task_status() {
        check_ajax_referer('htk_project_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $task_id = absint($_POST['task_id']);
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_tasks';
        
        if ($wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $task_id)
        )) {
            wp_send_json_success(__('Task status updated.', 'htk'));
        }

        wp_send_json_error(__('Failed to update task status.', 'htk'));
    }

    public function ajax_save_idea() {
        check_ajax_referer('htk_project_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $idea_data = array(
            'hackathon_id' => absint($_POST['hackathon_id']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'submitted_by' => get_current_user_id(),
            'votes' => 0
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_ideas';
        
        if ($wpdb->insert($table_name, $idea_data)) {
            wp_send_json_success(array(
                'message' => __('Idea saved successfully!', 'htk'),
                'idea_id' => $wpdb->insert_id
            ));
        }

        wp_send_json_error(__('Failed to save idea.', 'htk'));
    }

    public function ajax_vote_idea() {
        check_ajax_referer('htk_project_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $idea_id = absint($_POST['idea_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_ideas';
        
        if ($wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET votes = votes + 1 WHERE id = %d",
            $idea_id
        ))) {
            wp_send_json_success(__('Vote recorded.', 'htk'));
        }

        wp_send_json_error(__('Failed to record vote.', 'htk'));
    }

    public function ajax_save_timeline_event() {
        check_ajax_referer('htk_project_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied.', 'htk'));
        }

        $event_data = array(
            'hackathon_id' => absint($_POST['hackathon_id']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'event_type' => sanitize_text_field($_POST['event_type']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date'])
        );

        global $wpdb;
        $table_name = $wpdb->prefix . 'htk_timeline_events';
        
        if ($wpdb->insert($table_name, $event_data)) {
            wp_send_json_success(array(
                'message' => __('Event saved successfully!', 'htk'),
                'event_id' => $wpdb->insert_id
            ));
        }

        wp_send_json_error(__('Failed to save event.', 'htk'));
    }

    // Helper methods for labels
    private function get_status_label($status) {
        $labels = array(
            'todo' => __('To Do', 'htk'),
            'in_progress' => __('In Progress', 'htk'),
            'review' => __('Review', 'htk'),
            'done' => __('Done', 'htk')
        );
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    private function get_event_type_label($type) {
        $labels = array(
            'milestone' => __('Milestone', 'htk'),
            'deadline' => __('Deadline', 'htk'),
            'meeting' => __('Meeting', 'htk'),
            'other' => __('Other', 'htk')
        );
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    private function render_task_card($task) {
        ?>
        <div class="htk-task-card" data-id="<?php echo esc_attr($task->id); ?>">
            <h4><?php echo esc_html($task->title); ?></h4>
            <p><?php echo esc_html($task->description); ?></p>
            <div class="htk-task-meta">
                <?php if ($task->assigned_to) : 
                    $user = get_userdata($task->assigned_to);
                    ?>
                    <span class="htk-assigned-to">
                        <?php echo esc_html($user ? $user->display_name : __('Unassigned', 'htk')); ?>
                    </span>
                <?php endif; ?>
                <?php if ($task->due_date) : ?>
                    <span class="htk-due-date">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($task->due_date))); ?>
                    </span>
                <?php endif; ?>
                <span class="htk-priority htk-priority-<?php echo esc_attr($task->priority); ?>">
                    <?php echo esc_html(ucfirst($task->priority)); ?>
                </span>
            </div>
        </div>
        <?php
    }
} 