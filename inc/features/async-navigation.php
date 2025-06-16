<?php
/**
 * Async Navigation Feature
 * 
 * Provides REST API endpoints and functionality for asynchronous page loading
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes
 */
function docs_theme_register_async_routes() {
    register_rest_route('docs-theme/v1', '/page-content/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'docs_theme_get_page_content',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}
add_action('rest_api_init', 'docs_theme_register_async_routes');

/**
 * Get page content for async loading
 */
function docs_theme_get_page_content($request) {
    $page_id = $request->get_param('id');
    $page = get_post($page_id);
    
    if (!$page || $page->post_type !== 'page' || $page->post_status !== 'publish') {
        return new WP_Error('page_not_found', 'Page not found', array('status' => 404));
    }
    
    // Setup post data - MUST be before any functions that use global $post
    $GLOBALS['post'] = $page;
    setup_postdata($page);
    
    // Get page content
    $content = apply_filters('the_content', $page->post_content);
    
    // Generate breadcrumbs manually for REST API context
    $breadcrumbs_html = '';
    if (function_exists('docs_theme_show_breadcrumbs') && docs_theme_show_breadcrumbs()) {
        $breadcrumbs = array();
        $current_page = $page;
        
        // Build breadcrumb trail
        while ($current_page->post_parent) {
            $parent = get_post($current_page->post_parent);
            array_unshift($breadcrumbs, array(
                'title' => get_the_title($parent),
                'url' => get_permalink($parent),
                'id' => $parent->ID
            ));
            $current_page = $parent;
        }
        
        // Add home link
        array_unshift($breadcrumbs, array(
            'title' => __('Documentation', 'docs-theme'),
            'url' => home_url('/'),
            'id' => 0
        ));
        
        // Add current page (without link)
        $breadcrumbs[] = array(
            'title' => get_the_title($page),
            'url' => '',
            'id' => $page->ID
        );
        
        // Build HTML
        ob_start();
        ?>
        <nav class="docs-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'docs-theme'); ?>">
            <ul class="docs-breadcrumbs__list">
                <?php foreach ($breadcrumbs as $index => $crumb) : ?>
                    <li class="docs-breadcrumbs__item">
                        <?php if ($crumb['url']) : ?>
                            <a href="<?php echo esc_url($crumb['url']); ?>" class="docs-breadcrumbs__link">
                                <?php echo esc_html($crumb['title']); ?>
                            </a>
                        <?php else : ?>
                            <span class="docs-breadcrumbs__current" aria-current="page">
                                <?php echo esc_html($crumb['title']); ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
        $breadcrumbs_html = ob_get_clean();
    }
    
    // Get badges (reading time and last updated)
    $badges_html = '';
    $child_pages = get_pages(array(
        'parent' => $page_id,
        'post_status' => 'publish',
        'number' => 1, // Just check if any exist
    ));
    
    if (empty($child_pages)) {
        ob_start();
        ?>
        <?php if ((function_exists('docs_theme_show_reading_time') && docs_theme_show_reading_time()) ||
                 (function_exists('docs_theme_show_last_updated') && docs_theme_show_last_updated())) : ?>
            <div class="docs-badges-wrapper">
                <?php
                // Reading time badge
                if (function_exists('docs_theme_show_reading_time') && docs_theme_show_reading_time()) {
                    $reading_time = docs_theme_calculate_reading_time($content);
                    ?>
                    <span class="docs-reading-time-badge">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 4V8L10.5 10.5M14 8C14 11.3137 11.3137 14 8 14C4.68629 14 2 11.3137 2 8C2 4.68629 4.68629 2 8 2C11.3137 2 14 4.68629 14 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php printf(esc_html__('%d min read', 'docs-theme'), $reading_time); ?>
                    </span>
                <?php } ?>
                
                <?php
                // Last updated badge
                if (function_exists('docs_theme_show_last_updated') && docs_theme_show_last_updated()) {
                    $modified_timestamp = get_the_modified_time('U', $page);
                    $relative_time = docs_theme_get_relative_time($modified_timestamp);
                    $age_class = docs_theme_get_age_class($modified_timestamp);
                    ?>
                    <span class="docs-last-updated-badge <?php echo esc_attr($age_class); ?>">
                        <svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.5 1.5L14.5 4.5L5 14L2 15L3 12L12.5 2.5L11.5 1.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php echo esc_html($relative_time); ?>
                    </span>
                <?php } ?>
            </div>
        <?php endif;
        $badges_html = ob_get_clean();
    }
    
    // Get subtitle
    $subtitle = get_post_meta($page_id, '_docs_theme_subtitle', true);
    
    // Get table of contents headings
    $headings = docs_theme_extract_headings($content);
    
    // Get child pages if any
    $child_pages_html = '';
    $full_child_pages = get_pages(array(
        'parent' => $page_id,
        'sort_column' => 'menu_order,post_title',
        'sort_order' => 'ASC',
        'post_status' => 'publish',
    ));
    
    if (!empty($full_child_pages)) {
        ob_start();
        ?>
        <div class="docs-child-pages">
            <div class="docs-page-cards">
                <?php foreach ($full_child_pages as $child_page) : 
                    $excerpt = $child_page->post_excerpt;
                    if (empty($excerpt)) {
                        $subtitle_child = get_post_meta($child_page->ID, '_docs_theme_subtitle', true);
                        if (!empty($subtitle_child)) {
                            $excerpt = wp_trim_words($subtitle_child, 25, '...');
                        } else {
                            $excerpt = wp_trim_words(strip_shortcodes(wp_strip_all_tags($child_page->post_content)), 25, '...');
                        }
                    }
                    $emoticon = get_post_meta($child_page->ID, '_docs_theme_emoticon', true);
                    $grandchild_count = count(get_pages(array(
                        'parent' => $child_page->ID,
                        'post_status' => 'publish',
                    )));
                    ?>
                    <a href="<?php echo esc_url(get_permalink($child_page->ID)); ?>" class="docs-page-card" data-page-id="<?php echo esc_attr($child_page->ID); ?>">
                        <div class="docs-page-card-content">
                            <h3 class="docs-page-card-title">
                                <?php if (!empty($emoticon)) : ?>
                                    <span class="docs-page-card-emoticon"><?php echo esc_html($emoticon); ?></span>
                                <?php endif; ?>
                                <?php echo esc_html($child_page->post_title); ?>
                            </h3>
                            <?php if (!empty($excerpt)) : ?>
                                <p class="docs-page-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                            <?php endif; ?>
                            <?php if ($grandchild_count > 0) : ?>
                                <span class="docs-page-card-meta"><?php printf(esc_html(_n('%d page', '%d pages', $grandchild_count, 'docs-theme')), $grandchild_count); ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="docs-page-card-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 12L10 8L6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $child_pages_html = ob_get_clean();
    }
    
    // Build response
    $response = array(
        'id' => $page_id,
        'title' => $page->post_title,
        'subtitle' => $subtitle,
        'content' => $content,
        'breadcrumbs_html' => $breadcrumbs_html,
        'badges_html' => $badges_html,
        'headings' => $headings,
        'child_pages_html' => $child_pages_html,
        'has_children' => !empty($full_child_pages),
        'url' => get_permalink($page_id),
        'parent_id' => $page->post_parent,
    );
    
    wp_reset_postdata();
    
    return rest_ensure_response($response);
}

/**
 * Extract headings from content for TOC
 */
function docs_theme_extract_headings($content) {
    $headings = array();
    
    // Match h2, h3, h4 tags
    preg_match_all('/<h([2-4])[^>]*(?:id=["\']([^"\']+)["\'])?[^>]*>(.+?)<\/h\1>/i', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $level = $match[1];
        $id = $match[2];
        $text = strip_tags($match[3]);
        
        // Generate ID if not present
        if (empty($id)) {
            $id = sanitize_title($text);
        }
        
        $headings[] = array(
            'level' => intval($level),
            'id' => $id,
            'text' => $text,
        );
    }
    
    return $headings;
}

/**
 * Add data attributes to internal links for async navigation
 */
function docs_theme_add_link_attributes($content) {
    // Only process on frontend
    if (is_admin()) {
        return $content;
    }
    
    // Get site URL
    $site_url = home_url();
    
    // Add data-page-id to internal page links
    $content = preg_replace_callback(
        '/(<a[^>]+href=["\'])(' . preg_quote($site_url, '/') . '\/[^"\']+)(["\'][^>]*>)/i',
        function($matches) {
            $url = $matches[2];
            $page_id = url_to_postid($url);
            
            if ($page_id && get_post_type($page_id) === 'page') {
                return $matches[1] . $matches[2] . $matches[3] . ' data-page-id="' . $page_id . '"';
            }
            
            return $matches[0];
        },
        $content
    );
    
    return $content;
}
add_filter('the_content', 'docs_theme_add_link_attributes', 20);

/**
 * Enqueue async navigation script
 */
function docs_theme_enqueue_async_navigation() {
    wp_enqueue_script(
        'docs-theme-async-navigation',
        get_template_directory_uri() . '/assets/js/async-navigation.js',
        array('docs-theme-script'),
        defined('DOCS_THEME_VERSION') ? DOCS_THEME_VERSION : '1.0.0',
        true
    );
    
    wp_localize_script('docs-theme-async-navigation', 'docsThemeAsync', array(
        'restUrl' => esc_url_raw(rest_url('docs-theme/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'loadingText' => __('Loading...', 'docs-theme'),
    ));
}
add_action('wp_enqueue_scripts', 'docs_theme_enqueue_async_navigation');