<?php
/**
 * Template for displaying single hackathon
 */

get_header();
?>

<div class="htk-hackathon-header">
    <div class="htk-powered">
        <span>POWERED BY TERRALUX</span>
    </div>
    
    <div class="htk-main-header">
        <div class="htk-logo">
            <img src="<?php echo HTK_PLUGIN_URL . 'assets/images/htk-logo.png'; ?>" alt="HXTK Logo">
        </div>
        <h1 class="htk-title">HACKATHON EXPERIENCE TOOL KIT.</h1>
        <div class="htk-search">
            <input type="search" placeholder="Search" class="htk-search-input">
        </div>
    </div>
</div>

<div class="htk-hackathon-content">
    <div class="htk-status-indicator">
        <div class="htk-status-circle"></div>
    </div>

    <div class="htk-overview">
        <h2>Overview</h2>
        <div class="htk-overview-content">
            <?php the_content(); ?>
        </div>
    </div>

    <div class="htk-team-members">
        <?php
        $team_members = get_post_meta(get_the_ID(), '_htk_team_members', true);
        if ($team_members) :
            foreach ($team_members as $member) : ?>
                <div class="htk-member">
                    <div class="htk-member-avatar">
                        <?php echo get_avatar($member['id'], 100); ?>
                    </div>
                    <div class="htk-member-role">
                        <?php echo esc_html($member['role']); ?>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>

    <div class="htk-info-sections">
        <div class="htk-section htk-roles">
            <h3>ROLES</h3>
            <div class="htk-section-content">
                <?php 
                $roles = get_post_meta(get_the_ID(), '_htk_roles', true);
                if ($roles) {
                    echo '<ul>';
                    foreach ($roles as $role) {
                        echo '<li>' . esc_html($role) . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        </div>

        <div class="htk-section htk-resources">
            <h3>RESOURCES</h3>
            <div class="htk-section-content">
                <?php 
                $resources = get_post_meta(get_the_ID(), '_htk_resources', true);
                if ($resources) {
                    echo '<ul>';
                    foreach ($resources as $resource) {
                        echo '<li><a href="' . esc_url($resource['url']) . '">' . esc_html($resource['title']) . '</a></li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="htk-tools-grid">
        <?php
        $tools = get_post_meta(get_the_ID(), '_htk_tools', true);
        if ($tools) :
            foreach ($tools as $tool) : ?>
                <div class="htk-tool-card">
                    <h4>TOOLS</h4>
                    <div class="htk-tool-content">
                        <h5><?php echo esc_html($tool['name']); ?></h5>
                        <p><?php echo esc_html($tool['description']); ?></p>
                        <a href="<?php echo esc_url($tool['url']); ?>" class="htk-tool-button">
                            <?php echo esc_html($tool['button_text']); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach;
        endif; ?>
    </div>
</div>

<?php get_footer(); ?>