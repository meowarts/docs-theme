<?php
/**
 * Reading Time Feature
 * 
 * Calculates and displays estimated reading time for pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate reading time for content
 * 
 * @param string $content The content to calculate reading time for
 * @return int Reading time in minutes
 */
function docs_theme_calculate_reading_time($content = '') {
    if (empty($content)) {
        $content = get_the_content();
    }
    
    // Strip HTML tags and shortcodes
    $content = wp_strip_all_tags(strip_shortcodes($content));
    
    // Count words
    $word_count = str_word_count($content);
    
    // Average reading speed (words per minute)
    $reading_speed = apply_filters('docs_theme_reading_speed', 200);
    
    // Calculate reading time
    $reading_time = ceil($word_count / $reading_speed);
    
    // Minimum 1 minute
    return max(1, $reading_time);
}

/**
 * Get reading time HTML
 * 
 * @param int|null $post_id Post ID (optional)
 * @return string HTML output
 */
function docs_theme_get_reading_time($post_id = null) {
    // Check if reading time is enabled
    if (!function_exists('docs_theme_show_reading_time') || !docs_theme_show_reading_time()) {
        return '';
    }
    
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $content = get_post_field('post_content', $post_id);
    $reading_time = docs_theme_calculate_reading_time($content);
    
    // Format the output
    if ($reading_time == 1) {
        $text = __('1 min read', 'docs-theme');
    } else {
        $text = sprintf(__('%d min read', 'docs-theme'), $reading_time);
    }
    
    return sprintf(
        '<span class="docs-reading-time"><svg class="docs-reading-time__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 4V8L10.5 10.5M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C11.3137 2 14 4.68629 14 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="docs-reading-time__text">%s</span></span>',
        esc_html($text)
    );
}

/**
 * Display reading time
 */
function docs_theme_display_reading_time() {
    // Check if reading time is enabled
    if (!function_exists('docs_theme_show_reading_time') || !docs_theme_show_reading_time()) {
        return;
    }
    
    $content = get_the_content();
    $reading_time = docs_theme_calculate_reading_time($content);
    
    ?>
    <span class="docs-reading-time">
        <svg class="docs-reading-time__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 4V8L10.5 10.5M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C11.3137 2 14 4.68629 14 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="docs-reading-time__text">
            <?php printf(esc_html__('%d min', 'docs-theme'), $reading_time); ?>
        </span>
    </span>
    <?php
}