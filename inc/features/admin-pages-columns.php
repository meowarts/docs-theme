<?php
/**
 * Admin Pages Columns Customization
 * 
 * Customizes the pages list in WP Admin:
 * - Shows slug under page title
 * - Replaces published date with updated date using relative time
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add slug under page title in admin
 */
add_filter('page_row_actions', 'docs_theme_add_slug_to_page_title', 10, 2);
function docs_theme_add_slug_to_page_title($actions, $post) {
    // We'll handle this in the admin_footer function instead
    return $actions;
}

/**
 * Modify pages columns - replace date with updated date
 */
add_filter('manage_pages_columns', 'docs_theme_modify_pages_columns');
function docs_theme_modify_pages_columns($columns) {
    // Remove the default date column
    unset($columns['date']);
    
    // Add our custom updated date column
    $columns['updated'] = __('Updated', 'docs-theme');
    
    return $columns;
}

/**
 * Display content for the updated column
 */
add_action('manage_pages_custom_column', 'docs_theme_show_updated_column', 10, 2);
function docs_theme_show_updated_column($column_name, $post_id) {
    if ($column_name == 'updated') {
        $post = get_post($post_id);
        
        // Get timestamps
        $modified_timestamp = strtotime($post->post_modified);
        $published_timestamp = strtotime($post->post_date);
        
        // Get relative time
        $relative_time = docs_theme_get_admin_relative_time($modified_timestamp);
        
        // Get formatted date
        $modified_date = date_i18n(get_option('date_format'), $modified_timestamp);
        
        // Determine color based on age
        $age_class = docs_theme_get_admin_age_class($modified_timestamp);
        
        // Show relative time with full date below
        echo '<div class="docs-theme-updated-column ' . $age_class . '">';
        echo '<span class="relative-time">' . esc_html($relative_time) . '</span><br>';
        echo '<span class="full-date" style="color: #666; font-size: 12px;">' . esc_html($modified_date) . '</span>';
        echo '</div>';
    }
}

/**
 * Calculate human-readable time difference for admin
 */
function docs_theme_get_admin_relative_time($timestamp) {
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
        return sprintf(_n('%d min ago', '%d mins ago', $minutes, 'docs-theme'), $minutes);
    } elseif ($hours < 24) {
        return sprintf(_n('%d hr ago', '%d hrs ago', $hours, 'docs-theme'), $hours);
    } elseif ($days < 7) {
        return sprintf(_n('%d day ago', '%d days ago', $days, 'docs-theme'), $days);
    } elseif ($weeks < 4) {
        return sprintf(_n('%d wk ago', '%d wks ago', $weeks, 'docs-theme'), $weeks);
    } elseif ($months < 12) {
        return sprintf(_n('%d mo ago', '%d mos ago', $months, 'docs-theme'), $months);
    } else {
        return sprintf(_n('%d yr ago', '%d yrs ago', $years, 'docs-theme'), $years);
    }
}

/**
 * Get age class based on last update timestamp for admin
 */
function docs_theme_get_admin_age_class($timestamp) {
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $timestamp;
    $days_diff = floor($time_diff / (60 * 60 * 24));
    
    // Determine color class based on time since last update
    if ($days_diff < 30) { // Less than a month - fresh
        return 'updated-fresh';
    } elseif ($days_diff > 365) { // More than a year - stale
        return 'updated-stale';
    }
    
    return 'updated-normal'; // Default
}

/**
 * Add custom styles for the admin pages list
 */
add_action('admin_head', 'docs_theme_admin_pages_styles');
function docs_theme_admin_pages_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit' && $screen->post_type == 'page') {
        ?>
        <style>
            /* Slug under title */
            .docs-theme-slug {
                color: #666;
                font-size: 12px;
                margin-top: 2px;
            }
            
            /* Long slugs (more than 4 words) */
            .docs-theme-slug--long {
                color: #d63638 !important; /* WordPress red */
            }
            
            /* Updated column */
            .column-updated {
                width: 150px;
            }
            
            .docs-theme-updated-column {
                line-height: 1.5;
            }
            
            .docs-theme-updated-column .relative-time {
                /* No bold styling */
            }
            
            /* Color coding for freshness */
            .docs-theme-updated-column.updated-fresh .relative-time {
                color: #00a32a; /* WordPress green */
            }
            
            .docs-theme-updated-column.updated-stale .relative-time {
                color: #d63638; /* WordPress red */
            }
            
            .docs-theme-updated-column.updated-normal .relative-time {
                color: #3c434a; /* WordPress gray */
            }
            
            /* Make sure slug appears inline */
            .row-title + div {
                display: block !important;
            }
        </style>
        <?php
    }
}

/**
 * Make the updated column sortable
 */
add_filter('manage_edit-page_sortable_columns', 'docs_theme_updated_column_sortable');
function docs_theme_updated_column_sortable($columns) {
    $columns['updated'] = 'modified';
    return $columns;
}

/**
 * Fix the slug display using a more reliable method
 */
add_action('admin_footer', 'docs_theme_fix_slug_display');
function docs_theme_fix_slug_display() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit' && $screen->post_type == 'page') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Add slugs under all page titles
            $('#the-list tr').each(function() {
                var $row = $(this);
                var $titleWrapper = $row.find('.row-title').parent();
                var postId = $row.attr('id');
                
                if (postId && !$titleWrapper.find('.docs-theme-slug').length) {
                    // Get the edit link to extract the slug
                    var $editLink = $row.find('.row-actions .edit a');
                    if ($editLink.length) {
                        var href = $editLink.attr('href');
                        var postMatch = href.match(/post=(\d+)/);
                        
                        if (postMatch) {
                            // We'll need to get the slug via AJAX or from the quick edit data
                            var $inlineData = $('#inline_' + postMatch[1]);
                            if ($inlineData.length) {
                                var slug = $inlineData.find('.post_name').text();
                                if (slug) {
                                    // Count words (separated by hyphens)
                                    var wordCount = slug.split('-').length;
                                    var slugClass = wordCount > 4 ? 'docs-theme-slug docs-theme-slug--long' : 'docs-theme-slug';
                                    $titleWrapper.append('<div class="' + slugClass + '">/' + slug + '</div>');
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
}