<?php
/**
 * Template for displaying hackathon archives
 */

get_header(); ?>

<div class="htk-archive">
    <div class="htk-container">
        <header class="htk-archive-header">
            <h1 class="htk-archive-title">
                <?php
                if (is_tax('project_category')) {
                    single_term_title(__('Hackathons in Category: ', 'htk'));
                } else {
                    _e('Hackathon Projects', 'htk');
                }
                ?>
            </h1>

            <?php
            // Add filter controls
            $categories = get_terms(array(
                'taxonomy' => 'project_category',
                'hide_empty' => true
            ));
            if ($categories && !is_wp_error($categories)) : ?>
                <div class="htk-filter-controls">
                    <select id="htk-category-filter" class="htk-select">
                        <option value=""><?php _e('All Categories', 'htk'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->slug); ?>"
                                    <?php selected(get_query_var('project_category'), $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="htk-hackathon-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('htk-hackathon-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="htk-card-image">
                                <?php the_post_thumbnail('medium'); ?>
                            </div>
                        <?php endif; ?>

                        <div class="htk-card-content">
                            <h2 class="htk-card-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>

                            <?php
                            $start_date = get_post_meta(get_the_ID(), '_hackathon_start_date', true);
                            if ($start_date) : ?>
                                <div class="htk-card-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($start_date)); ?>
                                </div>
                            <?php endif; ?>

                            <div class="htk-card-excerpt">
                                <?php the_excerpt(); ?>
                            </div>

                            <?php
                            $categories = get_the_terms(get_the_ID(), 'project_category');
                            if ($categories && !is_wp_error($categories)) : ?>
                                <div class="htk-card-categories">
                                    <?php foreach ($categories as $category) : ?>
                                        <span class="htk-category-tag">
                                            <?php echo esc_html($category->name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>

            <?php
            // Pagination
            $args = array(
                'prev_text' => __('&laquo; Previous', 'htk'),
                'next_text' => __('Next &raquo;', 'htk'),
                'mid_size'  => 2
            );
            echo '<div class="htk-pagination">';
            echo paginate_links($args);
            echo '</div>';
            ?>

        <?php else : ?>
            <div class="htk-no-results">
                <p><?php _e('No hackathons found.', 'htk'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Add filter functionality
wp_enqueue_script('htk-archive-filters', HTK_PLUGIN_URL . 'assets/js/archive-filters.js', array('jquery'), HTK_VERSION, true);
wp_localize_script('htk-archive-filters', 'htkArchive', array(
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('htk_archive_filter')
));
?>

<?php get_footer(); ?>