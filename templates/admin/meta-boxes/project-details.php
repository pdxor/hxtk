<?php
/**
 * Template for project details meta box
 *
 * @var int $hackathon_id Associated hackathon ID
 * @var string $github_url GitHub repository URL
 * @var string $demo_url Demo/live project URL
 * @var array $team_members Team members array
 */

defined('ABSPATH') || exit;

// Get all hackathons
$hackathons = get_posts([
    'post_type' => 'hackathon',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post_status' => 'any'
]);
?>

<div class="htk-meta-box htk-project-details">
    <div class="htk-field-row">
        <label for="htk_hackathon_id"><?php esc_html_e('Associated Hackathon', 'htk'); ?></label>
        <select id="htk_hackathon_id" name="htk_hackathon_id" class="htk-select">
            <option value=""><?php esc_html_e('Select a hackathon', 'htk'); ?></option>
            <?php foreach ($hackathons as $hackathon) : ?>
                <option value="<?php echo esc_attr($hackathon->ID); ?>" 
                        <?php selected($hackathon_id, $hackathon->ID); ?>>
                    <?php echo esc_html($hackathon->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="htk-field-row">
        <label for="htk_github_url"><?php esc_html_e('GitHub Repository URL', 'htk'); ?></label>
        <input type="url" 
               id="htk_github_url" 
               name="htk_github_url" 
               value="<?php echo esc_url($github_url); ?>"
               class="widefat"
               placeholder="<?php esc_attr_e('https://github.com/username/repository', 'htk'); ?>">
        <p class="description">
            <?php esc_html_e('Link to the project\'s GitHub repository', 'htk'); ?>
        </p>
    </div>

    <div class="htk-field-row">
        <label for="htk_demo_url"><?php esc_html_e('Demo URL', 'htk'); ?></label>
        <input type="url" 
               id="htk_demo_url" 
               name="htk_demo_url" 
               value="<?php echo esc_url($demo_url); ?>"
               class="widefat"
               placeholder="<?php esc_attr_e('https://example.com/demo', 'htk'); ?>">
        <p class="description">
            <?php esc_html_e('Link to the live demo or deployed project', 'htk'); ?>
        </p>
    </div>

    <div class="htk-field-row">
        <label><?php esc_html_e('Team Members', 'htk'); ?></label>
        <div class="htk-project-team-members">
            <?php if (!empty($team_members)) : ?>
                <?php foreach ($team_members as $member) : 
                    $user = get_user_by('id', $member['id']);
                    if (!$user) continue;
                ?>
                    <div class="htk-team-member" data-id="<?php echo esc_attr($member['id']); ?>">
                        <div class="htk-team-member-avatar">
                            <?php echo get_avatar($member['id'], 40); ?>
                        </div>
                        <div class="htk-team-member-info">
                            <span class="htk-team-member-name">
                                <?php echo esc_html($user->display_name); ?>
                            </span>
                            <span class="htk-team-member-role">
                                <?php echo esc_html($member['role']); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="description">
                    <?php esc_html_e('No team members assigned yet. Team members are inherited from the associated hackathon.', 'htk'); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.htk-meta-box {
    padding: 12px;
}

.htk-field-row {
    margin-bottom: 15px;
}

.htk-field-row:last-child {
    margin-bottom: 0;
}

.htk-field-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.htk-select {
    min-width: 200px;
}

.description {
    margin-top: 5px;
    color: #666;
    font-style: italic;
}

.htk-project-team-members {
    margin-top: 10px;
}

.htk-team-member {
    display: flex;
    align-items: center;
    padding: 10px;
    margin-bottom: 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

.htk-team-member:last-child {
    margin-bottom: 0;
}

.htk-team-member-avatar {
    margin-right: 10px;
}

.htk-team-member-avatar img {
    border-radius: 50%;
}

.htk-team-member-info {
    flex: 1;
}

.htk-team-member-name {
    display: block;
    font-weight: 600;
}

.htk-team-member-role {
    display: block;
    color: #666;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    const hackathonSelect = $('#htk_hackathon_id');
    const teamMembersContainer = $('.htk-project-team-members');

    // Update team members when hackathon changes
    hackathonSelect.on('change', function() {
        const hackathonId = $(this).val();
        
        if (!hackathonId) {
            teamMembersContainer.html(
                '<p class="description"><?php esc_html_e('No team members assigned yet. Team members are inherited from the associated hackathon.', 'htk'); ?></p>'
            );
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'htk_get_hackathon_team_members',
                nonce: '<?php echo wp_create_nonce("htk_get_hackathon_team_members"); ?>',
                hackathon_id: hackathonId
            },
            success: function(response) {
                if (response.success && response.data) {
                    teamMembersContainer.empty();
                    response.data.forEach(function(member) {
                        const memberHtml = `
                            <div class="htk-team-member" data-id="${member.id}">
                                <div class="htk-team-member-avatar">
                                    <img src="${member.avatar}" alt="" width="40" height="40">
                                </div>
                                <div class="htk-team-member-info">
                                    <span class="htk-team-member-name">${member.name}</span>
                                    <span class="htk-team-member-role">${member.role}</span>
                                </div>
                            </div>
                        `;
                        teamMembersContainer.append(memberHtml);
                    });
                }
            }
        });
    });

    // Validate GitHub URL format
    $('#htk_github_url').on('change', function() {
        const url = $(this).val();
        if (url && !url.match(/^https:\/\/github\.com\/[\w-]+\/[\w-]+$/)) {
            alert('<?php esc_html_e('Please enter a valid GitHub repository URL.', 'htk'); ?>');
            $(this).focus();
        }
    });
});
</script> 