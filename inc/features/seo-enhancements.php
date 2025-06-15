<?php
/**
 * SEO Enhancements Feature
 * 
 * Adds SEO improvements to the theme
 */

// Add proper meta description support
add_action('wp_head', 'docs_theme_seo_meta_tags', 1);
function docs_theme_seo_meta_tags() {
    global $post;
    
    // Meta description
    if (is_singular() && $post) {
        // Use subtitle as meta description if available, otherwise use excerpt
        $subtitle = get_post_meta($post->ID, '_docs_theme_subtitle', true);
        $description = $subtitle ? $subtitle : get_the_excerpt($post->ID);
        
        if (!$description) {
            // Generate from content if no excerpt
            $content = strip_shortcodes($post->post_content);
            $content = strip_tags($content);
            $content = trim(preg_replace('/\s+/', ' ', $content));
            $description = substr($content, 0, 160);
        }
        
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    } elseif (is_home() || is_front_page()) {
        $description = get_bloginfo('description');
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    // Open Graph tags
    if (is_singular()) {
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        
        if (has_post_thumbnail()) {
            $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
            if ($thumbnail) {
                echo '<meta property="og:image" content="' . esc_url($thumbnail[0]) . '">' . "\n";
            }
        }
    }
    
    // Twitter Card tags
    echo '<meta name="twitter:card" content="summary">' . "\n";
    if (is_singular()) {
        echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        if (isset($description) && $description) {
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
}

// Add canonical URLs
add_action('wp_head', 'docs_theme_canonical_url');
function docs_theme_canonical_url() {
    if (is_singular()) {
        echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
    }
}

// Clean up wp_head for better SEO
remove_action('wp_head', 'wp_generator'); // Remove WordPress version
remove_action('wp_head', 'wlwmanifest_link'); // Remove Windows Live Writer
remove_action('wp_head', 'rsd_link'); // Remove Really Simple Discovery
remove_action('wp_head', 'wp_shortlink_wp_head'); // Remove shortlinks
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // Remove next/prev links

// Add schema.org structured data for articles
add_action('wp_footer', 'docs_theme_schema_markup');
function docs_theme_schema_markup() {
    if (is_singular('page')) {
        global $post;
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name')
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
        
        if (has_post_thumbnail()) {
            $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
            if ($thumbnail) {
                $schema['image'] = $thumbnail[0];
            }
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema) . '</script>' . "\n";
    }
}

// Improve title tags for SEO
add_filter('document_title_separator', 'docs_theme_title_separator');
function docs_theme_title_separator() {
    return '|';
}

add_filter('document_title_parts', 'docs_theme_title_parts');
function docs_theme_title_parts($title) {
    if (is_home() || is_front_page()) {
        unset($title['tagline']); // Remove tagline from home page title
    }
    return $title;
}

// Add breadcrumbs schema
function docs_theme_get_breadcrumbs() {
    if (!is_page() || is_front_page()) {
        return;
    }
    
    global $post;
    $breadcrumbs = array();
    $breadcrumbs[] = array(
        'name' => get_bloginfo('name'),
        'url' => home_url()
    );
    
    // Get all ancestors
    $ancestors = get_post_ancestors($post->ID);
    $ancestors = array_reverse($ancestors);
    
    foreach ($ancestors as $ancestor) {
        $breadcrumbs[] = array(
            'name' => get_the_title($ancestor),
            'url' => get_permalink($ancestor)
        );
    }
    
    // Add current page
    $breadcrumbs[] = array(
        'name' => get_the_title(),
        'url' => get_permalink()
    );
    
    // Build schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array()
    );
    
    foreach ($breadcrumbs as $index => $crumb) {
        $schema['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['name'],
            'item' => $crumb['url']
        );
    }
    
    echo '<script type="application/ld+json">' . json_encode($schema) . '</script>' . "\n";
}
add_action('wp_footer', 'docs_theme_get_breadcrumbs');

// Create XML sitemap for pages
add_action('init', 'docs_theme_sitemap_rewrite_rule');
function docs_theme_sitemap_rewrite_rule() {
    add_rewrite_rule('sitemap\.xml$', 'index.php?docs_sitemap=1', 'top');
}

add_filter('query_vars', 'docs_theme_sitemap_query_var');
function docs_theme_sitemap_query_var($vars) {
    $vars[] = 'docs_sitemap';
    return $vars;
}

add_action('template_redirect', 'docs_theme_generate_sitemap');
function docs_theme_generate_sitemap() {
    if (get_query_var('docs_sitemap')) {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo home_url('/'); ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <?php
    $pages = get_pages(array(
        'post_status' => 'publish',
        'sort_column' => 'menu_order, post_title'
    ));
    
    foreach ($pages as $page) {
        ?>
    <url>
        <loc><?php echo get_permalink($page->ID); ?></loc>
        <lastmod><?php echo get_the_modified_date('c', $page); ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
        <?php
    }
    ?>
</urlset>
        <?php
        exit;
    }
}