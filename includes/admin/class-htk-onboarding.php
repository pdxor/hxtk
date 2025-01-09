<?php
namespace HTK\Admin;

class HTK_Onboarding {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_onboarding_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_onboarding_assets'));
    }

    public function add_onboarding_menu() {
        add_submenu_page(
            'htk-admin',
            __('Onboarding', 'htk'),
            __('Onboarding', 'htk'),
            'manage_options',
            'htk-onboarding',
            array($this, 'render_onboarding_page')
        );
    }

    public function enqueue_onboarding_assets($hook) {
        if ('htk-1-0_page_htk-onboarding' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'htk-onboarding-css',
            HTK_PLUGIN_URL . 'assets/css/admin/onboarding.css',
            array(),
            HTK_VERSION
        );

        wp_enqueue_script(
            'htk-onboarding-js',
            HTK_PLUGIN_URL . 'assets/js/admin/onboarding.js',
            array('jquery'),
            HTK_VERSION,
            true
        );

        wp_localize_script('htk-onboarding-js', 'htkOnboarding', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('htk_onboarding_nonce')
        ));
    }

    public function render_onboarding_page() {
        ?>
        <div class="wrap htk-onboarding">
            <h1><?php _e('MIT Reality Hack Onboarding', 'htk'); ?></h1>

            <div class="htk-onboarding-container">
                <!-- Getting Started Section -->
                <div class="htk-section htk-getting-started">
                    <h2><?php _e('Getting Started', 'htk'); ?></h2>
                    <div class="htk-progress-tracker">
                        <div class="htk-progress-bar">
                            <div class="htk-progress" style="width: 0%"></div>
                        </div>
                        <span class="htk-progress-text">0% Complete</span>
                    </div>

                    <div class="htk-steps">
                        <?php $this->render_onboarding_steps(); ?>
                    </div>
                </div>

                <!-- Rules & Guidelines -->
                <div class="htk-section htk-rules">
                    <h2><?php _e('Rules & Guidelines', 'htk'); ?></h2>
                    <?php $this->render_rules_guidelines(); ?>
                </div>

                <!-- Judging Criteria -->
                <div class="htk-section htk-judging">
                    <h2><?php _e('Judging Criteria', 'htk'); ?></h2>
                    <?php $this->render_judging_criteria(); ?>
                </div>

                <!-- FAQ Section -->
                <div class="htk-section htk-faq">
                    <h2><?php _e('Frequently Asked Questions', 'htk'); ?></h2>
                    <?php $this->render_faq(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_onboarding_steps() {
        $steps = array(
            array(
                'id' => 'setup-profile',
                'title' => __('Set Up Your Profile', 'htk'),
                'description' => __('Create your participant profile with your skills and interests.', 'htk'),
                'action_url' => admin_url('post-new.php?post_type=hackathon_participant'),
                'action_text' => __('Create Profile', 'htk')
            ),
            array(
                'id' => 'join-team',
                'title' => __('Join or Create a Team', 'htk'),
                'description' => __('Find teammates or start your own team.', 'htk'),
                'action_url' => admin_url('post-new.php?post_type=hackathon'),
                'action_text' => __('Team Management', 'htk')
            ),
            array(
                'id' => 'project-setup',
                'title' => __('Set Up Your Project', 'htk'),
                'description' => __('Initialize your project and set up version control.', 'htk'),
                'action_url' => '#',
                'action_text' => __('Project Setup', 'htk')
            ),
            array(
                'id' => 'resources-review',
                'title' => __('Review Available Resources', 'htk'),
                'description' => __('Explore development tools and learning materials.', 'htk'),
                'action_url' => admin_url('admin.php?page=htk-resources'),
                'action_text' => __('View Resources', 'htk')
            )
        );

        foreach ($steps as $step) {
            ?>
            <div class="htk-step" data-step="<?php echo esc_attr($step['id']); ?>">
                <div class="htk-step-header">
                    <div class="htk-step-number"></div>
                    <h3><?php echo esc_html($step['title']); ?></h3>
                </div>
                <div class="htk-step-content">
                    <p><?php echo esc_html($step['description']); ?></p>
                    <a href="<?php echo esc_url($step['action_url']); ?>" class="button button-primary">
                        <?php echo esc_html($step['action_text']); ?>
                    </a>
                </div>
            </div>
            <?php
        }
    }

    private function render_rules_guidelines() {
        $rules = array(
            array(
                'title' => __('Code of Conduct', 'htk'),
                'content' => __('All participants must adhere to the MIT Reality Hack Code of Conduct, promoting an inclusive and respectful environment.', 'htk')
            ),
            array(
                'title' => __('Project Requirements', 'htk'),
                'content' => __('Projects must be original work created during the hackathon period. Use of open-source libraries and assets is allowed with proper attribution.', 'htk')
            ),
            array(
                'title' => __('Team Formation', 'htk'),
                'content' => __('Teams should consist of 2-5 members. Cross-disciplinary collaboration is encouraged.', 'htk')
            ),
            array(
                'title' => __('Intellectual Property', 'htk'),
                'content' => __('Participants retain rights to their intellectual property. Projects should be open source unless otherwise specified.', 'htk')
            )
        );

        echo '<div class="htk-rules-list">';
        foreach ($rules as $rule) {
            ?>
            <div class="htk-rule-item">
                <h4><?php echo esc_html($rule['title']); ?></h4>
                <p><?php echo esc_html($rule['content']); ?></p>
            </div>
            <?php
        }
        echo '</div>';
    }

    private function render_judging_criteria() {
        $criteria = array(
            array(
                'title' => __('Innovation', 'htk'),
                'weight' => '25%',
                'description' => __('Originality and creativity of the solution.', 'htk')
            ),
            array(
                'title' => __('Technical Achievement', 'htk'),
                'weight' => '25%',
                'description' => __('Complexity and technical sophistication of the implementation.', 'htk')
            ),
            array(
                'title' => __('Design & UX', 'htk'),
                'weight' => '25%',
                'description' => __('User experience, interface design, and overall polish.', 'htk')
            ),
            array(
                'title' => __('Impact & Viability', 'htk'),
                'weight' => '25%',
                'description' => __('Potential impact and practical applicability of the solution.', 'htk')
            )
        );

        echo '<div class="htk-criteria-grid">';
        foreach ($criteria as $criterion) {
            ?>
            <div class="htk-criterion">
                <div class="htk-criterion-header">
                    <h4><?php echo esc_html($criterion['title']); ?></h4>
                    <span class="htk-weight"><?php echo esc_html($criterion['weight']); ?></span>
                </div>
                <p><?php echo esc_html($criterion['description']); ?></p>
            </div>
            <?php
        }
        echo '</div>';
    }

    private function render_faq() {
        $faqs = array(
            array(
                'question' => __('What should I bring to the hackathon?', 'htk'),
                'answer' => __('Bring your laptop, charger, and any VR/AR equipment you\'d like to use. We\'ll provide the rest!', 'htk')
            ),
            array(
                'question' => __('How are teams formed?', 'htk'),
                'answer' => __('You can form teams beforehand or join team formation activities at the start of the event.', 'htk')
            ),
            array(
                'question' => __('What if I\'m new to XR development?', 'htk'),
                'answer' => __('We welcome developers of all skill levels! Check out our Resources section for learning materials.', 'htk')
            ),
            array(
                'question' => __('Is there a code template to start with?', 'htk'),
                'answer' => __('Yes, check the Development section for starter templates and boilerplate code.', 'htk')
            )
        );

        echo '<div class="htk-faq-list">';
        foreach ($faqs as $faq) {
            ?>
            <div class="htk-faq-item">
                <h4 class="htk-question"><?php echo esc_html($faq['question']); ?></h4>
                <div class="htk-answer">
                    <p><?php echo esc_html($faq['answer']); ?></p>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    }
} 