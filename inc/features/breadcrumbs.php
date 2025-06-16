<?php
/**
 * Breadcrumb Navigation Feature
 * 
 * Displays hierarchical navigation for pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate breadcrumb navigation
 */
function docs_theme_get_breadcrumbs_html() {
    // Check if breadcrumbs are enabled
    if (!function_exists('docs_theme_show_breadcrumbs') || !docs_theme_show_breadcrumbs()) {
        return '';
    }
    
    // Only show on pages
    if (!is_page()) {
        return '';
    }
    
    global $post;
    
    $breadcrumbs = array();
    $current_page = $post;
    
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
        'title' => get_the_title($post),
        'url' => '',
        'id' => $post->ID
    );
    
    return $breadcrumbs;
}

/**
 * Display breadcrumbs
 */
function docs_theme_display_breadcrumbs() {
    $breadcrumbs = docs_theme_get_breadcrumbs_html();
    
    if (empty($breadcrumbs)) {
        return;
    }
    
    ?>
    <nav class="docs-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'docs-theme'); ?>">
        <ol class="docs-breadcrumbs__list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb) : ?>
                <li class="docs-breadcrumbs__item">
                    <?php if (!empty($breadcrumb['url'])) : ?>
                        <a href="<?php echo esc_url($breadcrumb['url']); ?>" class="docs-breadcrumbs__link">
                            <?php echo esc_html($breadcrumb['title']); ?>
                        </a>
                    <?php else : ?>
                        <span class="docs-breadcrumbs__current" aria-current="page">
                            <?php echo esc_html($breadcrumb['title']); ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}