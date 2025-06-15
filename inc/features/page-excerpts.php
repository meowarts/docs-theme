<?php
/**
 * Page Excerpts Feature
 * 
 * Enables excerpt support for pages to display summaries on parent pages
 */

// Enable excerpt support for pages
add_action('init', 'docs_theme_enable_page_excerpts');
function docs_theme_enable_page_excerpts() {
    add_post_type_support('page', 'excerpt');
}

// Add excerpt field to REST API for pages
add_action('rest_api_init', 'docs_theme_add_excerpt_to_pages_api');
function docs_theme_add_excerpt_to_pages_api() {
    register_rest_field('page', 'excerpt_rendered', array(
        'get_callback' => function($post) {
            $excerpt = get_the_excerpt($post['id']);
            if (empty($excerpt)) {
                // Check if page has a subtitle and use that as fallback
                $subtitle = get_post_meta($post['id'], '_docs_theme_subtitle', true);
                if (!empty($subtitle)) {
                    $excerpt = wp_trim_words($subtitle, 25);
                } else {
                    // Generate excerpt from content if none exists
                    $content = get_post_field('post_content', $post['id']);
                    $excerpt = wp_trim_words(strip_shortcodes(wp_strip_all_tags($content)), 30);
                }
            }
            return $excerpt;
        },
        'schema' => array(
            'type' => 'string',
            'description' => 'The excerpt for the page',
        ),
    ));
}

// Custom excerpt length for pages
add_filter('excerpt_length', 'docs_theme_page_excerpt_length', 10, 2);
function docs_theme_page_excerpt_length($length, $post = null) {
    if ($post && $post->post_type === 'page') {
        return 25; // Shorter excerpts for page cards
    }
    return $length;
}

// Custom excerpt more text
add_filter('excerpt_more', 'docs_theme_page_excerpt_more', 10, 2);
function docs_theme_page_excerpt_more($more, $post = null) {
    if ($post && $post->post_type === 'page') {
        return '...';
    }
    return $more;
}