<?php
/**
 * Template for hackathon details meta box
 *
 * @var string $start_date Start date
 * @var string $end_date End date
 * @var string $location Location
 * @var int $max_participants Maximum participants
 * @var string $status Status
 */

defined('ABSPATH') || exit;
?>

<div class="htk-meta-box htk-hackathon-details">
    <div class="htk-field-row">
        <label for="htk_start_date"><?php esc_html_e('Start Date', 'htk'); ?></label>
        <input type="datetime-local" 
               id="htk_start_date" 
               name="htk_start_date" 
               value="<?php echo esc_attr($start_date); ?>"
               class="htk-date-input">
    </div>

    <div class="htk-field-row">
        <label for="htk_end_date"><?php esc_html_e('End Date', 'htk'); ?></label>
        <input type="datetime-local" 
               id="htk_end_date" 
               name="htk_end_date" 
               value="<?php echo esc_attr($end_date); ?>"
               class="htk-date-input">
    </div>

    <div class="htk-field-row">
        <label for="htk_location"><?php esc_html_e('Location', 'htk'); ?></label>
        <input type="text" 
               id="htk_location" 
               name="htk_location" 
               value="<?php echo esc_attr($location); ?>"
               class="widefat"
               placeholder="<?php esc_attr_e('Physical location or virtual platform', 'htk'); ?>">
    </div>

    <div class="htk-field-row">
        <label for="htk_max_participants"><?php esc_html_e('Maximum Participants', 'htk'); ?></label>
        <input type="number" 
               id="htk_max_participants" 
               name="htk_max_participants" 
               value="<?php echo esc_attr($max_participants); ?>"
               class="small-text"
               min="0"
               step="1">
        <p class="description">
            <?php esc_html_e('Leave empty or set to 0 for unlimited participants', 'htk'); ?>
        </p>
    </div>

    <div class="htk-field-row">
        <label for="htk_status"><?php esc_html_e('Status', 'htk'); ?></label>
        <select id="htk_status" name="htk_status" class="htk-select">
            <option value="draft" <?php selected($status, 'draft'); ?>>
                <?php esc_html_e('Draft', 'htk'); ?>
            </option>
            <option value="upcoming" <?php selected($status, 'upcoming'); ?>>
                <?php esc_html_e('Upcoming', 'htk'); ?>
            </option>
            <option value="active" <?php selected($status, 'active'); ?>>
                <?php esc_html_e('Active', 'htk'); ?>
            </option>
            <option value="completed" <?php selected($status, 'completed'); ?>>
                <?php esc_html_e('Completed', 'htk'); ?>
            </option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>
                <?php esc_html_e('Cancelled', 'htk'); ?>
            </option>
        </select>
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

.htk-date-input {
    width: 200px;
}

.htk-select {
    min-width: 200px;
}

.description {
    margin-top: 5px;
    color: #666;
    font-style: italic;
}
</style> 