<?php
/**
 * Last Updated Date Feature
 * 
 * Displays when a page was last modified
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get last updated date HTML
 * 
 * @param int|null $post_id Post ID (optional)
 * @return string HTML output
 */
function docs_theme_get_last_updated($post_id = null) {
    // Check if last updated is enabled
    if (!function_exists('docs_theme_show_last_updated') || !docs_theme_show_last_updated()) {
        return '';
    }
    
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Get the modified date
    $modified_date = get_the_modified_date(get_option('date_format'), $post_id);
    $modified_time = get_the_modified_date('c', $post_id); // ISO 8601 format for datetime attribute
    
    // Check if the post has been modified after publication
    $published_time = get_the_date('U', $post_id);
    $modified_timestamp = get_the_modified_date('U', $post_id);
    
    // Only show if actually modified after publication
    if ($modified_timestamp <= $published_time) {
        return '';
    }
    
    return sprintf(
        '<span class="docs-last-updated"><svg class="docs-last-updated__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 5V8M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C9.61244 2 11.0778 2.63362 12.1543 3.66413M12 2V4H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="docs-last-updated__text">%s <time datetime="%s">%s</time></span></span>',
        esc_html__('Updated', 'docs-theme'),
        esc_attr($modified_time),
        esc_html($modified_date)
    );
}

/**
 * Calculate human-readable time difference
 */
function docs_theme_get_relative_time($timestamp) {
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $timestamp;
    
    // Convert to various units
    $minutes = floor($time_diff / 60);
    $hours = floor($time_diff / (60 * 60));
    $days = floor($time_diff / (60 * 60 * 24));
    $weeks = floor($days / 7);
    $months = floor($days / 30);
    $years = floor($days / 365);
    
    // Return appropriate string
    if ($minutes < 1) {
        return __('just now', 'docs-theme');
    } elseif ($minutes < 60) {
        return $minutes . ' min ago';
    } elseif ($hours < 24) {
        return $hours . ' ' . ($hours == 1 ? 'hr' : 'hrs') . ' ago';
    } elseif ($days < 7) {
        return $days . ' ' . ($days == 1 ? 'day' : 'days') . ' ago';
    } elseif ($weeks < 4) {
        return $weeks . ' ' . ($weeks == 1 ? 'wk' : 'wks') . ' ago';
    } elseif ($months < 12) {
        return $months . ' ' . ($months == 1 ? 'mo' : 'mos') . ' ago';
    } else {
        return $years . ' ' . ($years == 1 ? 'yr' : 'yrs') . ' ago';
    }
}

/**
 * Get age class based on last update timestamp
 */
function docs_theme_get_age_class($timestamp) {
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $timestamp;
    $days_diff = floor($time_diff / (60 * 60 * 24));
    
    // Determine color class based on time since last update
    if ($days_diff < 30) { // Less than a month
        return 'last-updated--green';
    } elseif ($days_diff > 365) { // More than a year
        return 'last-updated--red';
    }
    
    return ''; // Default, no special class
}

/**
 * Display last updated date
 */
function docs_theme_display_last_updated() {
    // Check if last updated is enabled
    if (!function_exists('docs_theme_show_last_updated') || !docs_theme_show_last_updated()) {
        return;
    }
    
    $post_id = get_the_ID();
    
    // Get the modified date
    $modified_time = get_the_modified_date('c', $post_id); // ISO 8601 format for datetime attribute
    
    // Check if the post has been modified after publication
    $published_time = get_the_date('U', $post_id);
    $modified_timestamp = get_the_modified_date('U', $post_id);
    
    // Only show if actually modified after publication
    if ($modified_timestamp <= $published_time) {
        return;
    }
    
    // Get relative time
    $relative_time = docs_theme_get_relative_time($modified_timestamp);
    
    // Calculate time difference for color coding
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $modified_timestamp;
    $days_diff = floor($time_diff / (60 * 60 * 24));
    
    // Determine color class based on time since last update
    $color_class = '';
    if ($days_diff < 30) { // Less than a month
        $color_class = ' docs-last-updated--green';
    } elseif ($days_diff > 365) { // More than a year
        $color_class = ' docs-last-updated--red';
    }
    
    ?>
    <span class="docs-last-updated<?php echo esc_attr($color_class); ?>">
        <svg class="docs-last-updated__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 5V8M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C9.61244 2 11.0778 2.63362 12.1543 3.66413M12 2V4H10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="docs-last-updated__text">
            <time datetime="<?php echo esc_attr($modified_time); ?>"><?php echo esc_html($relative_time); ?></time>
        </span>
    </span>
    <?php
}

/**
 * Track content updates
 * Update the modified date when content is actually changed
 */
function docs_theme_track_content_updates($data, $postarr) {
    // Only for pages
    if ($data['post_type'] !== 'page') {
        return $data;
    }
    
    // Check if this is an update (not a new post)
    if (!empty($postarr['ID'])) {
        $old_post = get_post($postarr['ID']);
        
        // Compare content to see if it actually changed
        if ($old_post && $old_post->post_content === $data['post_content'] && $old_post->post_title === $data['post_title']) {
            // Content hasn't changed, preserve the original modified date
            $data['post_modified'] = $old_post->post_modified;
            $data['post_modified_gmt'] = $old_post->post_modified_gmt;
        }
    }
    
    return $data;
}
add_filter('wp_insert_post_data', 'docs_theme_track_content_updates', 10, 2);