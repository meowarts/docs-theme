<?php
/**
 * Disable Comments Feature
 * 
 * Completely disables comments functionality across the entire website
 */

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page from admin menu
add_action('admin_menu', 'docs_theme_remove_comments_admin_menu');
function docs_theme_remove_comments_admin_menu() {
    remove_menu_page('edit-comments.php');
}

// Remove comments from admin bar
add_action('init', 'docs_theme_remove_comments_admin_bar');
function docs_theme_remove_comments_admin_bar() {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
}

// Remove comments metabox from dashboard
add_action('admin_init', 'docs_theme_remove_dashboard_comments');
function docs_theme_remove_dashboard_comments() {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}

// Remove comments from post and page edit screens
add_action('init', 'docs_theme_remove_comment_support');
function docs_theme_remove_comment_support() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}

// Remove comments column from post and page tables
add_filter('manage_posts_columns', 'docs_theme_remove_comments_column');
add_filter('manage_pages_columns', 'docs_theme_remove_comments_column');
function docs_theme_remove_comments_column($columns) {
    unset($columns['comments']);
    return $columns;
}

// Remove comments from admin menu bar
add_action('wp_before_admin_bar_render', 'docs_theme_remove_comments_admin_bar_render');
function docs_theme_remove_comments_admin_bar_render() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}

// Redirect any user trying to access comments page
add_action('admin_init', 'docs_theme_redirect_comments_page');
function docs_theme_redirect_comments_page() {
    global $pagenow;
    if ($pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
        wp_redirect(admin_url());
        exit;
    }
}

// Remove comments from the 'At a Glance' dashboard widget
add_filter('dashboard_glance_items', 'docs_theme_remove_comments_from_glance', 10, 1);
function docs_theme_remove_comments_from_glance($items) {
    foreach ($items as $key => $item) {
        if (strpos($item, 'comment') !== false) {
            unset($items[$key]);
        }
    }
    return $items;
}

// Remove Discussion settings from Settings menu
add_action('admin_menu', 'docs_theme_remove_discussion_settings');
function docs_theme_remove_discussion_settings() {
    remove_submenu_page('options-general.php', 'options-discussion.php');
}

// Remove comment-related fields from user profile
add_action('admin_init', 'docs_theme_remove_comment_profile_fields');
function docs_theme_remove_comment_profile_fields() {
    // Remove the comment shortcuts section
    add_filter('enable_comment_shortcuts', '__return_false');
}

// Remove comment RSS feeds
add_action('do_feed_rss2', 'docs_theme_disable_comment_feed', 1);
add_action('do_feed_atom', 'docs_theme_disable_comment_feed', 1);
add_action('do_feed_rss', 'docs_theme_disable_comment_feed', 1);
add_action('do_feed_rdf', 'docs_theme_disable_comment_feed', 1);
function docs_theme_disable_comment_feed() {
    if (is_comment_feed()) {
        wp_die(__('Comments are disabled on this site.', 'docs-theme'), '', array('response' => 403));
    }
}

// Remove comment-related widgets
add_action('widgets_init', 'docs_theme_remove_comment_widgets', 11);
function docs_theme_remove_comment_widgets() {
    unregister_widget('WP_Widget_Recent_Comments');
}

// Remove X-Pingback HTTP header
add_filter('wp_headers', 'docs_theme_remove_pingback_header');
function docs_theme_remove_pingback_header($headers) {
    unset($headers['X-Pingback']);
    return $headers;
}

// Disable comment-related REST API endpoints
add_filter('rest_endpoints', 'docs_theme_remove_comment_endpoints');
function docs_theme_remove_comment_endpoints($endpoints) {
    unset($endpoints['/wp/v2/comments']);
    unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
    return $endpoints;
}