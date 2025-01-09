<?php
/**
 * Post Types functionality
 */

namespace HTK\PostTypes;

use WP_Query;
use WP_Error;

/**
 * Class PostTypes
 * Handles custom post types and taxonomies
 */
class PostTypes {
    /**
     * Post type names
     */
    private const POST_TYPE_HACKATHON = 'hackathon';
    private const POST_TYPE_PROJECT = 'htk_project';

    /**
     * Taxonomy names
     */
    private const TAXONOMY_CATEGORY = 'hackathon_category';
    private const TAXONOMY_SKILL = 'hackathon_skill';
    private const TAXONOMY_TECHNOLOGY = 'hackathon_technology';

    /**
     * Initialize the post types
     */
    public function init(): void {
        // Register post types
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);

        // AJAX handlers
        add_action('wp_ajax_htk_search_participants', [$this, 'ajax_search_participants']);
        add_action('wp_ajax_htk_save_team_members', [$this, 'ajax_save_team_members']);
        add_action('wp_ajax_htk_get_project_details', [$this, 'ajax_get_project_details']);

        // Filters and columns
        add_filter('manage_' . self::POST_TYPE_HACKATHON . '_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_' . self::POST_TYPE_HACKATHON . '_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
    }

    /**
     * Register custom post types
     */
    public function register_post_types(): void {
        // Hackathon post type
        register_post_type(
            self::POST_TYPE_HACKATHON,
            [
                'labels' => [
                    'name' => __('Hackathons', 'htk'),
                    'singular_name' => __('Hackathon', 'htk'),
                    'add_new' => __('Add New', 'htk'),
                    'add_new_item' => __('Add New Hackathon', 'htk'),
                    'edit_item' => __('Edit Hackathon', 'htk'),
                    'new_item' => __('New Hackathon', 'htk'),
                    'view_item' => __('View Hackathon', 'htk'),
                    'search_items' => __('Search Hackathons', 'htk'),
                    'not_found' => __('No hackathons found', 'htk'),
                    'not_found_in_trash' => __('No hackathons found in trash', 'htk'),
                ],
                'public' => true,
                'has_archive' => true,
                'menu_icon' => 'dashicons-groups',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'rewrite' => ['slug' => 'hackathons'],
                'show_in_rest' => true,
                'capability_type' => 'post',
                'map_meta_cap' => true,
            ]
        );

        // Project post type
        register_post_type(
            self::POST_TYPE_PROJECT,
            [
                'labels' => [
                    'name' => __('Projects', 'htk'),
                    'singular_name' => __('Project', 'htk'),
                    'add_new' => __('Add New', 'htk'),
                    'add_new_item' => __('Add New Project', 'htk'),
                    'edit_item' => __('Edit Project', 'htk'),
                    'new_item' => __('New Project', 'htk'),
                    'view_item' => __('View Project', 'htk'),
                    'search_items' => __('Search Projects', 'htk'),
                    'not_found' => __('No projects found', 'htk'),
                    'not_found_in_trash' => __('No projects found in trash', 'htk'),
                ],
                'public' => true,
                'has_archive' => true,
                'menu_icon' => 'dashicons-portfolio',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
                'rewrite' => ['slug' => 'projects'],
                'show_in_rest' => true,
                'capability_type' => 'post',
                'map_meta_cap' => true,
            ]
        );
    }

    /**
     * Register custom taxonomies
     */
    public function register_taxonomies(): void {
        // Hackathon Category taxonomy
        register_taxonomy(
            self::TAXONOMY_CATEGORY,
            [self::POST_TYPE_HACKATHON],
            [
                'labels' => [
                    'name' => __('Categories', 'htk'),
                    'singular_name' => __('Category', 'htk'),
                    'search_items' => __('Search Categories', 'htk'),
                    'all_items' => __('All Categories', 'htk'),
                    'edit_item' => __('Edit Category', 'htk'),
                    'update_item' => __('Update Category', 'htk'),
                    'add_new_item' => __('Add New Category', 'htk'),
                    'new_item_name' => __('New Category Name', 'htk'),
                    'menu_name' => __('Categories', 'htk'),
                ],
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'hackathon-category'],
                'show_in_rest' => true,
            ]
        );

        // Skills taxonomy
        register_taxonomy(
            self::TAXONOMY_SKILL,
            [self::POST_TYPE_HACKATHON, self::POST_TYPE_PROJECT],
            [
                'labels' => [
                    'name' => __('Skills', 'htk'),
                    'singular_name' => __('Skill', 'htk'),
                    'search_items' => __('Search Skills', 'htk'),
                    'all_items' => __('All Skills', 'htk'),
                    'edit_item' => __('Edit Skill', 'htk'),
                    'update_item' => __('Update Skill', 'htk'),
                    'add_new_item' => __('Add New Skill', 'htk'),
                    'new_item_name' => __('New Skill Name', 'htk'),
                    'menu_name' => __('Skills', 'htk'),
                ],
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'skill'],
                'show_in_rest' => true,
            ]
        );

        // Technologies taxonomy
        register_taxonomy(
            self::TAXONOMY_TECHNOLOGY,
            [self::POST_TYPE_HACKATHON, self::POST_TYPE_PROJECT],
            [
                'labels' => [
                    'name' => __('Technologies', 'htk'),
                    'singular_name' => __('Technology', 'htk'),
                    'search_items' => __('Search Technologies', 'htk'),
                    'all_items' => __('All Technologies', 'htk'),
                    'edit_item' => __('Edit Technology', 'htk'),
                    'update_item' => __('Update Technology', 'htk'),
                    'add_new_item' => __('Add New Technology', 'htk'),
                    'new_item_name' => __('New Technology Name', 'htk'),
                    'menu_name' => __('Technologies', 'htk'),
                ],
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'technology'],
                'show_in_rest' => true,
            ]
        );
    }

    /**
     * Add meta boxes
     *
     * @param string $post_type Post type
     */
    public function add_meta_boxes(string $post_type): void {
        if ($post_type === self::POST_TYPE_HACKATHON) {
            add_meta_box(
                'htk_hackathon_details',
                __('Hackathon Details', 'htk'),
                [$this, 'render_hackathon_meta_box'],
                self::POST_TYPE_HACKATHON,
                'normal',
                'high'
            );

            add_meta_box(
                'htk_team_members',
                __('Team Members', 'htk'),
                [$this, 'render_team_members_meta_box'],
                self::POST_TYPE_HACKATHON,
                'normal',
                'high'
            );
        }

        if ($post_type === self::POST_TYPE_PROJECT) {
            add_meta_box(
                'htk_project_details',
                __('Project Details', 'htk'),
                [$this, 'render_project_meta_box'],
                self::POST_TYPE_PROJECT,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render hackathon meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_hackathon_meta_box(\WP_Post $post): void {
        wp_nonce_field('htk_hackathon_meta_box', 'htk_hackathon_meta_box_nonce');

        $start_date = get_post_meta($post->ID, '_htk_start_date', true);
        $end_date = get_post_meta($post->ID, '_htk_end_date', true);
        $location = get_post_meta($post->ID, '_htk_location', true);
        $max_participants = get_post_meta($post->ID, '_htk_max_participants', true);
        $status = get_post_meta($post->ID, '_htk_status', true);

        require HTK_PLUGIN_DIR . 'templates/admin/meta-boxes/hackathon-details.php';
    }

    /**
     * Render team members meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_team_members_meta_box(\WP_Post $post): void {
        wp_nonce_field('htk_team_members_meta_box', 'htk_team_members_meta_box_nonce');

        $team_members = get_post_meta($post->ID, '_htk_team_members', true);
        if (!is_array($team_members)) {
            $team_members = [];
        }

        require HTK_PLUGIN_DIR . 'templates/admin/meta-boxes/team-members.php';
    }

    /**
     * Render project meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_project_meta_box(\WP_Post $post): void {
        wp_nonce_field('htk_project_meta_box', 'htk_project_meta_box_nonce');

        $hackathon_id = get_post_meta($post->ID, '_htk_hackathon_id', true);
        $github_url = get_post_meta($post->ID, '_htk_github_url', true);
        $demo_url = get_post_meta($post->ID, '_htk_demo_url', true);
        $team_members = get_post_meta($post->ID, '_htk_team_members', true);

        require HTK_PLUGIN_DIR . 'templates/admin/meta-boxes/project-details.php';
    }

    /**
     * Save meta box data
     *
     * @param int $post_id Post ID
     */
    public function save_meta_boxes(int $post_id): void {
        // Check if our nonce is set
        if (!isset($_POST['htk_hackathon_meta_box_nonce']) && 
            !isset($_POST['htk_team_members_meta_box_nonce']) && 
            !isset($_POST['htk_project_meta_box_nonce'])) {
            return;
        }

        // Verify the nonce before proceeding
        if (isset($_POST['htk_hackathon_meta_box_nonce']) && 
            !wp_verify_nonce($_POST['htk_hackathon_meta_box_nonce'], 'htk_hackathon_meta_box')) {
            return;
        }

        if (isset($_POST['htk_team_members_meta_box_nonce']) && 
            !wp_verify_nonce($_POST['htk_team_members_meta_box_nonce'], 'htk_team_members_meta_box')) {
            return;
        }

        if (isset($_POST['htk_project_meta_box_nonce']) && 
            !wp_verify_nonce($_POST['htk_project_meta_box_nonce'], 'htk_project_meta_box')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (isset($_POST['post_type'])) {
            if ($_POST['post_type'] === self::POST_TYPE_HACKATHON) {
                if (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }
        }

        // Save hackathon details
        if (isset($_POST['htk_start_date'])) {
            update_post_meta($post_id, '_htk_start_date', sanitize_text_field($_POST['htk_start_date']));
        }
        if (isset($_POST['htk_end_date'])) {
            update_post_meta($post_id, '_htk_end_date', sanitize_text_field($_POST['htk_end_date']));
        }
        if (isset($_POST['htk_location'])) {
            update_post_meta($post_id, '_htk_location', sanitize_text_field($_POST['htk_location']));
        }
        if (isset($_POST['htk_max_participants'])) {
            update_post_meta($post_id, '_htk_max_participants', absint($_POST['htk_max_participants']));
        }
        if (isset($_POST['htk_status'])) {
            update_post_meta($post_id, '_htk_status', sanitize_text_field($_POST['htk_status']));
        }

        // Save team members
        if (isset($_POST['htk_team_members']) && is_array($_POST['htk_team_members'])) {
            $team_members = array_map(function($member) {
                return [
                    'id' => absint($member['id']),
                    'role' => sanitize_text_field($member['role'])
                ];
            }, $_POST['htk_team_members']);
            update_post_meta($post_id, '_htk_team_members', $team_members);
        }

        // Save project details
        if (isset($_POST['htk_hackathon_id'])) {
            update_post_meta($post_id, '_htk_hackathon_id', absint($_POST['htk_hackathon_id']));
        }
        if (isset($_POST['htk_github_url'])) {
            update_post_meta($post_id, '_htk_github_url', esc_url_raw($_POST['htk_github_url']));
        }
        if (isset($_POST['htk_demo_url'])) {
            update_post_meta($post_id, '_htk_demo_url', esc_url_raw($_POST['htk_demo_url']));
        }
    }

    /**
     * AJAX handler for participant search
     */
    public function ajax_search_participants(): void {
        check_ajax_referer('htk_search_participants', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        
        $users = new \WP_User_Query([
            'search' => "*{$search_term}*",
            'search_columns' => ['user_login', 'user_nicename', 'user_email', 'display_name'],
            'role__in' => ['administrator', 'hackathon_organizer', 'hackathon_participant'],
            'number' => 10,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ]);

        $results = array_map(function($user) {
            return [
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
                'email' => $user->user_email
            ];
        }, $users->get_results());

        wp_send_json_success($results);
    }

    /**
     * AJAX handler for saving team members
     */
    public function ajax_save_team_members(): void {
        check_ajax_referer('htk_save_team_members', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $team_members = json_decode(stripslashes($_POST['team_members'] ?? '[]'), true);

        if (!$post_id || !is_array($team_members)) {
            wp_send_json_error('Invalid data');
            return;
        }

        $sanitized_members = array_map(function($member) {
            return [
                'id' => absint($member['id']),
                'role' => sanitize_text_field($member['role'])
            ];
        }, $team_members);

        update_post_meta($post_id, '_htk_team_members', $sanitized_members);
        wp_send_json_success('Team members updated');
    }

    /**
     * AJAX handler for getting project details
     */
    public function ajax_get_project_details(): void {
        check_ajax_referer('htk_get_project_details', 'nonce');

        $project_id = absint($_POST['project_id'] ?? 0);
        if (!$project_id) {
            wp_send_json_error('Invalid project ID');
            return;
        }

        $project = get_post($project_id);
        if (!$project || $project->post_type !== self::POST_TYPE_PROJECT) {
            wp_send_json_error('Project not found');
            return;
        }

        $details = [
            'title' => get_the_title($project),
            'description' => get_the_excerpt($project),
            'github_url' => get_post_meta($project_id, '_htk_github_url', true),
            'demo_url' => get_post_meta($project_id, '_htk_demo_url', true),
            'team_members' => get_post_meta($project_id, '_htk_team_members', true),
            'technologies' => wp_get_post_terms($project_id, self::TAXONOMY_TECHNOLOGY, ['fields' => 'names']),
            'skills' => wp_get_post_terms($project_id, self::TAXONOMY_SKILL, ['fields' => 'names'])
        ];

        wp_send_json_success($details);
    }

    /**
     * Add custom columns to post type list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_custom_columns(array $columns): array {
        $new_columns = [
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'status' => __('Status', 'htk'),
            'start_date' => __('Start Date', 'htk'),
            'end_date' => __('End Date', 'htk'),
            'participants' => __('Participants', 'htk'),
            'location' => __('Location', 'htk'),
            'date' => $columns['date']
        ];
        return $new_columns;
    }

    /**
     * Render custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_custom_columns(string $column, int $post_id): void {
        switch ($column) {
            case 'status':
                $status = get_post_meta($post_id, '_htk_status', true);
                echo esc_html($status);
                break;

            case 'start_date':
                $start_date = get_post_meta($post_id, '_htk_start_date', true);
                echo esc_html($start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '—');
                break;

            case 'end_date':
                $end_date = get_post_meta($post_id, '_htk_end_date', true);
                echo esc_html($end_date ? date_i18n(get_option('date_format'), strtotime($end_date)) : '—');
                break;

            case 'participants':
                $team_members = get_post_meta($post_id, '_htk_team_members', true);
                $max_participants = get_post_meta($post_id, '_htk_max_participants', true);
                echo esc_html(sprintf(
                    '%d/%d',
                    is_array($team_members) ? count($team_members) : 0,
                    $max_participants ?: '∞'
                ));
                break;

            case 'location':
                $location = get_post_meta($post_id, '_htk_location', true);
                echo esc_html($location ?: '—');
                break;
        }
    }
} 