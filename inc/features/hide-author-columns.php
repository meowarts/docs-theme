<?php
/**
 * Hide Author Columns Feature
 * 
 * Removes author columns from posts and pages admin lists
 */

// Remove author column from posts list
add_filter('manage_posts_columns', 'docs_theme_remove_author_column');
function docs_theme_remove_author_column($columns) {
    unset($columns['author']);
    return $columns;
}

// Remove author column from pages list
add_filter('manage_pages_columns', 'docs_theme_remove_author_column_pages');
function docs_theme_remove_author_column_pages($columns) {
    unset($columns['author']);
    return $columns;
}

// Also hide author filter dropdown in posts list
add_action('restrict_manage_posts', 'docs_theme_remove_author_filter', 999);
function docs_theme_remove_author_filter() {
    ?>
    <style>
        select#author,
        label[for="author"] {
            display: none !important;
        }
    </style>
    <?php
}

// Hide author box in quick edit
add_action('admin_head', 'docs_theme_hide_author_quick_edit');
function docs_theme_hide_author_quick_edit() {
    $screen = get_current_screen();
    if ($screen && ($screen->base == 'edit' && ($screen->post_type == 'post' || $screen->post_type == 'page'))) {
        ?>
        <style>
            .inline-edit-author,
            .quick-edit-row .inline-edit-author {
                display: none !important;
            }
        </style>
        <?php
    }
}