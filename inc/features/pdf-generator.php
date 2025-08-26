<?php
/**
 * PDF Generator
 * 
 * Generates PDF documentation from all pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add submenu under Docs Theme
 */
function docs_theme_add_pdf_menu() {
    add_submenu_page(
        'docs-theme-settings',
        __('Generate PDF', 'docs-theme'),
        __('Create PDF', 'docs-theme'),
        'manage_options',
        'docs-theme-pdf',
        'docs_theme_pdf_page'
    );
}
add_action('admin_menu', 'docs_theme_add_pdf_menu');

/**
 * Hook into template_redirect to catch PDF generation requests early
 */
add_action('template_redirect', 'docs_theme_handle_pdf_generation');
function docs_theme_handle_pdf_generation() {
    // Check if this is a PDF generation request
    if (isset($_GET['docs_theme_pdf']) && $_GET['docs_theme_pdf'] === '1') {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'docs_theme_generate_pdf')) {
            wp_die(__('Security check failed', 'docs-theme'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to generate PDFs', 'docs-theme'));
        }
        
        // Get PDF title
        $pdf_title = sanitize_text_field($_GET['pdf_title'] ?? get_bloginfo('name') . ' Documentation');
        
        // Generate clean PDF without any WordPress chrome
        docs_theme_generate_pdf_html($pdf_title);
        exit;
    }
}

/**
 * PDF Generator page HTML
 */
function docs_theme_pdf_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php _e('Generate PDF Documentation', 'docs-theme'); ?></h1>
        
        <div class="notice notice-info">
            <p><?php _e('This Create PDF feature was built to generate a comprehensive PDF document that can be used to feed an AI with the complete content and structure of this documentation website.', 'docs-theme'); ?></p>
        </div>
        
        <?php
        // Build the PDF generation URL
        $pdf_url = add_query_arg(array(
            'docs_theme_pdf' => '1',
            '_wpnonce' => wp_create_nonce('docs_theme_generate_pdf')
        ), home_url());
        ?>
        
        <form method="get" action="<?php echo esc_url($pdf_url); ?>" target="_blank">
            <input type="hidden" name="docs_theme_pdf" value="1" />
            <?php wp_nonce_field('docs_theme_generate_pdf', '_wpnonce', false); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('PDF Title', 'docs-theme'); ?></th>
                    <td>
                        <input type="text" name="pdf_title" class="regular-text" value="<?php echo esc_attr(get_bloginfo('name') . ' Documentation'); ?>" />
                        <p class="description"><?php _e('The title that will appear on the PDF cover page.', 'docs-theme'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Generate PDF', 'docs-theme'), 'primary', 'submit'); ?>
            <p class="description" style="margin-top: 10px;">
                <?php _e('The PDF will open in a new tab. Use your browser\'s print function (Ctrl/Cmd + P) to save as PDF.', 'docs-theme'); ?>
            </p>
        </form>
        
        <div style="margin-top: 30px;">
            <h2><?php _e('Preview: Pages to be included (in order)', 'docs-theme'); ?></h2>
            <?php docs_theme_show_pages_preview(); ?>
        </div>
    </div>
    <?php
}

/**
 * Show preview of pages that will be included with proper ordering
 */
function docs_theme_show_pages_preview() {
    // Get categories with custom ordering
    $category_args = array(
        'taxonomy' => 'page_category',
        'hide_empty' => true,
        'meta_key' => 'docs_theme_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC'
    );
    $categories = get_terms($category_args);
    
    echo '<div style="background: #f6f7f7; padding: 15px; border: 1px solid #dcdcde; border-radius: 4px; max-height: 400px; overflow-y: auto;">
    <style>
        #docs-preview-list ul { margin: 2px 0 2px 15px !important; padding: 0; }
        #docs-preview-list ul li { margin-bottom: 2px !important; }
        #docs-preview-list ul li ul { margin-top: 2px !important; }
    </style>
    <div id="docs-preview-list">';
    
    // Get all top-level pages
    $all_pages = get_pages(array(
        'sort_order' => 'ASC',
        'sort_column' => 'menu_order,post_title',
        'hierarchical' => true,
        'parent' => 0
    ));
    
    // Separate pages by category
    $pages_by_category = array();
    $uncategorized_pages = array();
    
    foreach ($all_pages as $page) {
        $page_categories = get_the_terms($page->ID, 'page_category');
        
        if ($page_categories && !is_wp_error($page_categories)) {
            // Page has categories - add to first category
            $category = $page_categories[0];
            if (!isset($pages_by_category[$category->term_id])) {
                $pages_by_category[$category->term_id] = array();
            }
            $pages_by_category[$category->term_id][] = $page;
        } else {
            // Page has no category
            $uncategorized_pages[] = $page;
        }
    }
    
    // Show uncategorized pages first
    if (!empty($uncategorized_pages)) {
        echo '<strong style="color: #1e293b; text-transform: uppercase; letter-spacing: 1px; font-size: 12px;">' . esc_html(strtoupper(__('General', 'docs-theme'))) . '</strong>';
        echo '<ul style="margin: 5px 0 15px 20px; list-style: disc;">';
        foreach ($uncategorized_pages as $page) {
            docs_theme_show_page_in_preview($page);
        }
        echo '</ul>';
    }
    
    // Show categorized pages with proper order
    foreach ($categories as $category) {
        if (isset($pages_by_category[$category->term_id]) && !empty($pages_by_category[$category->term_id])) {
            $category_name = html_entity_decode($category->name, ENT_QUOTES, 'UTF-8');
            echo '<strong style="color: #1e293b; text-transform: uppercase; letter-spacing: 1px; font-size: 12px;">' . esc_html(strtoupper($category_name)) . '</strong>';
            echo '<ul style="margin: 5px 0 15px 20px; list-style: disc;">';
            foreach ($pages_by_category[$category->term_id] as $page) {
                docs_theme_show_page_in_preview($page);
            }
            echo '</ul>';
        }
    }
    
    echo '</div></div>';
}

/**
 * Show page and its children in preview
 */
function docs_theme_show_page_in_preview($page, $level = 0) {
    // Only indent if this is a child page (level > 0), otherwise let the ul handle spacing
    $indent = 0; // Remove individual item indentation, let ul margins handle it
    echo '<li style="margin-bottom: 3px;">' . esc_html($page->post_title);
    
    // Get child pages
    $children = get_pages(array(
        'child_of' => $page->ID,
        'sort_order' => 'ASC',
        'sort_column' => 'menu_order,post_title',
        'parent' => $page->ID
    ));
    
    if ($children) {
        echo '<ul style="margin: 2px 0 2px 15px; list-style: circle;">';
        foreach ($children as $child) {
            docs_theme_show_page_in_preview($child, $level + 1);
        }
        echo '</ul>';
    }
    
    echo '</li>';
}

/**
 * Generate HTML for PDF with proper page breaks
 */
function docs_theme_generate_pdf_html($pdf_title) {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html($pdf_title); ?></title>
        <script>
            // Auto-trigger print dialog after page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
        <style>
            /* Reset and base styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Georgia, 'Times New Roman', serif;
                line-height: 1.6;
                color: #000;
                background: white;
                font-size: 11pt;
            }
            
            /* Page setup for printing */
            @page {
                size: A4;
                margin: 30mm 25mm 30mm 25mm;
            }
            
            @media print {
                body {
                    padding: 0;
                }
                
                .page-break {
                    page-break-after: always;
                    page-break-inside: avoid;
                }
                
                h1, h2, h3, h4 {
                    page-break-after: avoid;
                }
                
                p {
                    orphans: 3;
                    widows: 3;
                }
                
                /* Add some padding to content pages for better readability */
                .page-content {
                    padding: 10mm 5mm;
                }
            }
            
            /* Cover page */
            .cover-page {
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                page-break-after: always;
            }
            
            .cover-title {
                font-size: 36pt;
                font-weight: bold;
                margin-bottom: 20pt;
                color: #111;
            }
            
            .cover-meta {
                font-size: 12pt;
                color: #666;
                margin-top: 20pt;
            }
            
            /* Category separators */
            .category-separator {
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                page-break-after: always;
                page-break-before: always;
            }
            
            .category-title {
                font-size: 28pt;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 3px;
                color: #111;
            }
            
            /* Content pages */
            .page-content {
                page-break-before: always;
                min-height: 100vh;
            }
            
            .page-content h1 {
                font-size: 24pt;
                margin-bottom: 12pt;
                color: #111;
                border-bottom: 2pt solid #000;
                padding-bottom: 6pt;
            }
            
            .page-content h2 {
                font-size: 18pt;
                margin-top: 18pt;
                margin-bottom: 10pt;
                color: #222;
            }
            
            .page-content h3 {
                font-size: 14pt;
                margin-top: 14pt;
                margin-bottom: 8pt;
                color: #333;
            }
            
            .page-content h4 {
                font-size: 12pt;
                margin-top: 12pt;
                margin-bottom: 6pt;
                color: #444;
            }
            
            .page-content p {
                margin-bottom: 10pt;
                text-align: justify;
            }
            
            .page-content ul,
            .page-content ol {
                margin-left: 20pt;
                margin-bottom: 10pt;
            }
            
            .page-content li {
                margin-bottom: 4pt;
            }
            
            .page-meta {
                font-size: 9pt;
                color: #666;
                margin-bottom: 12pt;
                font-style: italic;
            }
            
            .page-url {
                font-size: 9pt;
                color: #666;
                margin-bottom: 12pt;
                word-break: break-all;
            }
            
            .page-excerpt {
                font-size: 11pt;
                color: #444;
                margin-bottom: 14pt;
                padding: 10pt;
                background: #f8f8f8;
                border-left: 3pt solid #333;
            }
            
            /* Code blocks */
            pre {
                background: #f5f5f5;
                padding: 10pt;
                margin: 10pt 0;
                overflow-x: auto;
                font-family: 'Courier New', Courier, monospace;
                font-size: 9pt;
                border: 1pt solid #ddd;
                page-break-inside: avoid;
            }
            
            code {
                background: #f5f5f5;
                padding: 2pt 4pt;
                font-family: 'Courier New', Courier, monospace;
                font-size: 9pt;
            }
            
            pre code {
                background: none;
                padding: 0;
            }
            
            /* Blockquotes */
            blockquote {
                border-left: 3pt solid #666;
                margin: 10pt 0;
                padding-left: 15pt;
                color: #555;
                font-style: italic;
            }
            
            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 12pt 0;
                page-break-inside: avoid;
            }
            
            th, td {
                border: 1pt solid #ddd;
                padding: 8pt;
                text-align: left;
            }
            
            th {
                background: #f5f5f5;
                font-weight: bold;
            }
            
            /* Images */
            img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 12pt auto;
            }
            
            figure {
                margin: 12pt 0;
                text-align: center;
            }
            
            figcaption {
                font-size: 9pt;
                color: #666;
                margin-top: 6pt;
            }
            
            /* Links */
            a {
                color: #000;
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <!-- Cover Page -->
        <div class="cover-page">
            <div class="cover-title"><?php echo esc_html($pdf_title); ?></div>
            <div class="cover-meta">
                <p style="margin-top: 10pt;">Generated as of <?php echo date('F j, Y'); ?></p>
                <p><?php echo esc_url(get_bloginfo('url')); ?></p>
            </div>
        </div>
        
        <?php
        // Get categories with custom ordering
        $category_args = array(
            'taxonomy' => 'page_category',
            'hide_empty' => true,
            'meta_key' => 'docs_theme_order',
            'orderby' => 'meta_value_num',
            'order' => 'ASC'
        );
        $categories = get_terms($category_args);
        
        // Get all pages to build hierarchy
        $all_pages = get_pages(array(
            'sort_order' => 'ASC',
            'sort_column' => 'menu_order,post_title',
            'hierarchical' => false,
            'parent' => 0
        ));
        
        // Separate pages by category
        $pages_by_category = array();
        $uncategorized_pages = array();
        
        foreach ($all_pages as $page) {
            $page_categories = get_the_terms($page->ID, 'page_category');
            
            if ($page_categories && !is_wp_error($page_categories)) {
                // Page has categories - add to first category
                $category = $page_categories[0];
                if (!isset($pages_by_category[$category->term_id])) {
                    $pages_by_category[$category->term_id] = array();
                }
                $pages_by_category[$category->term_id][] = $page;
            } else {
                // Page has no category
                $uncategorized_pages[] = $page;
            }
        }
        
        // Output uncategorized pages first
        if (!empty($uncategorized_pages)) {
            ?>
            <div class="category-separator">
                <div class="category-title">General</div>
            </div>
            <?php
            
            foreach ($uncategorized_pages as $page) {
                docs_theme_output_page_for_pdf($page);
                
                // Process child pages
                $children = get_pages(array(
                    'child_of' => $page->ID,
                    'sort_order' => 'ASC',
                    'sort_column' => 'menu_order,post_title'
                ));
                
                foreach ($children as $child) {
                    docs_theme_output_page_for_pdf($child);
                }
            }
        }
        
        // Process categorized pages with proper ordering
        foreach ($categories as $category) {
            if (isset($pages_by_category[$category->term_id]) && !empty($pages_by_category[$category->term_id])) {
                ?>
                <div class="category-separator">
                    <div class="category-title"><?php echo esc_html($category->name); ?></div>
                </div>
                <?php
                
                foreach ($pages_by_category[$category->term_id] as $page) {
                    docs_theme_output_page_for_pdf($page);
                    
                    // Process child pages
                    $children = get_pages(array(
                        'child_of' => $page->ID,
                        'sort_order' => 'ASC',
                        'sort_column' => 'menu_order,post_title'
                    ));
                    
                    foreach ($children as $child) {
                        docs_theme_output_page_for_pdf($child);
                    }
                }
            }
        }
        ?>
    </body>
    </html>
    <?php
}

/**
 * Output individual page for PDF
 */
function docs_theme_output_page_for_pdf($page) {
    ?>
    <div class="page-content">
        <h1><?php echo esc_html($page->post_title); ?></h1>
        
        <?php if (!empty($page->post_excerpt)) : ?>
            <div class="page-excerpt">
                <?php echo esc_html($page->post_excerpt); ?>
            </div>
        <?php endif; ?>
        
        <div class="page-url">
            <strong>URL:</strong> <?php echo esc_url(get_permalink($page->ID)); ?>
        </div>
        
        <div class="page-meta">
            <?php echo __('Last updated: ', 'docs-theme') . get_the_modified_date('F j, Y', $page->ID); ?>
        </div>
        
        <?php
        // Process content
        $content = apply_filters('the_content', $page->post_content);
        $content = docs_theme_clean_content_for_pdf($content);
        echo $content;
        ?>
    </div>
    <?php
}

/**
 * Clean content for PDF output
 */
function docs_theme_clean_content_for_pdf($content) {
    // Remove script and style tags
    $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
    $content = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $content);
    
    // Remove WordPress block comments
    $content = preg_replace('/<!-- wp:.*?\/-->/s', '', $content);
    $content = preg_replace('/<!-- \/wp:.*?-->/s', '', $content);
    
    // Clean up block classes
    $content = preg_replace('/ class="[^"]*wp-block[^"]*"/', '', $content);
    $content = preg_replace('/ class="[^"]*has-[^"]*"/', '', $content);
    $content = preg_replace('/ class="[^"]*is-[^"]*"/', '', $content);
    
    // Remove copy buttons
    $content = preg_replace('/<button[^>]*class="[^"]*copy-button[^"]*"[^>]*>.*?<\/button>/si', '', $content);
    
    // Convert relative URLs to absolute
    $site_url = get_site_url();
    $content = str_replace('href="/', 'href="' . $site_url . '/', $content);
    $content = str_replace('src="/', 'src="' . $site_url . '/', $content);
    
    // Remove empty paragraphs
    $content = preg_replace('/<p[^>]*>[\s&nbsp;]*<\/p>/i', '', $content);
    
    // Clean up excessive breaks
    $content = preg_replace('/(<br\s*\/?>){3,}/', '<br/><br/>', $content);
    
    return $content;
}