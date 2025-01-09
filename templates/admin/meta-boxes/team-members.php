<?php
/**
 * Template for team members meta box
 *
 * @var array $team_members Team members array
 */

defined('ABSPATH') || exit;
?>

<div class="htk-meta-box htk-team-members">
    <div class="htk-team-members-list">
        <?php if (!empty($team_members)) : ?>
            <?php foreach ($team_members as $member) : ?>
                <div class="htk-team-member" data-id="<?php echo esc_attr($member['id']); ?>">
                    <div class="htk-team-member-avatar">
                        <?php echo get_avatar($member['id'], 40); ?>
                    </div>
                    <div class="htk-team-member-info">
                        <span class="htk-team-member-name">
                            <?php echo esc_html(get_user_by('id', $member['id'])->display_name); ?>
                        </span>
                        <input type="text" 
                               class="htk-team-member-role" 
                               name="htk_team_members[<?php echo esc_attr($member['id']); ?>][role]" 
                               value="<?php echo esc_attr($member['role']); ?>"
                               placeholder="<?php esc_attr_e('Role', 'htk'); ?>">
                        <input type="hidden" 
                               name="htk_team_members[<?php echo esc_attr($member['id']); ?>][id]" 
                               value="<?php echo esc_attr($member['id']); ?>">
                    </div>
                    <button type="button" class="htk-remove-member button">
                        <?php esc_html_e('Remove', 'htk'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="htk-add-member-section">
        <input type="text" 
               id="htk_member_search" 
               class="htk-member-search" 
               placeholder="<?php esc_attr_e('Search users...', 'htk'); ?>">
        <button type="button" class="htk-add-member button button-primary">
            <?php esc_html_e('Add Member', 'htk'); ?>
        </button>
    </div>

    <div class="htk-member-search-results"></div>
</div>

<style>
.htk-team-members {
    padding: 12px;
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
    margin-bottom: 5px;
}

.htk-team-member-role {
    width: 200px;
}

.htk-add-member-section {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.htk-member-search {
    flex: 1;
}

.htk-member-search-results {
    margin-top: 10px;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.htk-search-result {
    display: flex;
    align-items: center;
    padding: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.htk-search-result:hover {
    background-color: #f0f0f0;
}

.htk-search-result-avatar {
    margin-right: 10px;
}

.htk-search-result-avatar img {
    border-radius: 50%;
}

.htk-search-result-info {
    flex: 1;
}

.htk-search-result-name {
    font-weight: 600;
}

.htk-search-result-email {
    color: #666;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    let searchTimeout;
    const searchResults = $('.htk-member-search-results');
    const membersList = $('.htk-team-members-list');
    const searchInput = $('#htk_member_search');

    // Search users
    searchInput.on('input', function() {
        const searchTerm = $(this).val();
        clearTimeout(searchTimeout);

        if (searchTerm.length < 2) {
            searchResults.hide().empty();
            return;
        }

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'htk_search_participants',
                    nonce: '<?php echo wp_create_nonce("htk_search_participants"); ?>',
                    search_term: searchTerm
                },
                success: function(response) {
                    if (response.success && response.data) {
                        searchResults.empty();
                        response.data.forEach(function(user) {
                            const result = $(`
                                <div class="htk-search-result" data-id="${user.id}">
                                    <div class="htk-search-result-avatar">
                                        <img src="${user.avatar}" alt="" width="32" height="32">
                                    </div>
                                    <div class="htk-search-result-info">
                                        <div class="htk-search-result-name">${user.name}</div>
                                        <div class="htk-search-result-email">${user.email}</div>
                                    </div>
                                </div>
                            `);
                            searchResults.append(result);
                        });
                        searchResults.show();
                    }
                }
            });
        }, 500);
    });

    // Add member
    searchResults.on('click', '.htk-search-result', function() {
        const userId = $(this).data('id');
        const userName = $(this).find('.htk-search-result-name').text();
        const userAvatar = $(this).find('img').attr('src');

        // Check if user is already added
        if (membersList.find(`[data-id="${userId}"]`).length) {
            alert('<?php esc_html_e('This user is already added to the team.', 'htk'); ?>');
            return;
        }

        const member = $(`
            <div class="htk-team-member" data-id="${userId}">
                <div class="htk-team-member-avatar">
                    <img src="${userAvatar}" alt="" width="40" height="40">
                </div>
                <div class="htk-team-member-info">
                    <span class="htk-team-member-name">${userName}</span>
                    <input type="text" 
                           class="htk-team-member-role" 
                           name="htk_team_members[${userId}][role]" 
                           placeholder="<?php esc_attr_e('Role', 'htk'); ?>">
                    <input type="hidden" 
                           name="htk_team_members[${userId}][id]" 
                           value="${userId}">
                </div>
                <button type="button" class="htk-remove-member button">
                    <?php esc_html_e('Remove', 'htk'); ?>
                </button>
            </div>
        `);

        membersList.append(member);
        searchInput.val('');
        searchResults.hide().empty();
    });

    // Remove member
    membersList.on('click', '.htk-remove-member', function() {
        $(this).closest('.htk-team-member').remove();
    });

    // Close search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.htk-add-member-section, .htk-member-search-results').length) {
            searchResults.hide().empty();
        }
    });
});
</script> 