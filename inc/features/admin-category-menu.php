<?php
/**
 * Add category links under Pages > All Pages in admin menu
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add category submenu items under Pages
 */
function docs_theme_add_category_submenu_items() {
    global $submenu;
    
    // Get all page categories with custom ordering
    $categories = get_terms(array(
        'taxonomy' => 'page_category',
        'hide_empty' => false,
        'meta_key' => 'docs_theme_order',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ));
    
    if (!empty($categories) && !is_wp_error($categories)) {
        $parent_slug = 'edit.php?post_type=page';
        
        // Find the position of "All Pages" item
        $all_pages_position = 5; // Default position
        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $position => $item) {
                if ($item[2] === $parent_slug) {
                    $all_pages_position = $position;
                    break;
                }
            }
        }
        
        // Insert categories right after "All Pages"
        $insert_position = $all_pages_position + 1;
        
        foreach ($categories as $index => $category) {
            $menu_title = '– ' . $category->name;
            $page_title = 'Pages in ' . $category->name;
            $capability = 'edit_pages';
            $menu_slug = 'edit.php?post_type=page&page_category=' . $category->slug;
            
            // Insert at specific position
            $submenu[$parent_slug][$insert_position + $index] = array(
                $menu_title,
                $capability,
                $menu_slug
            );
        }
        
        // Re-sort the submenu array by key to maintain order
        if (isset($submenu[$parent_slug])) {
            ksort($submenu[$parent_slug]);
        }
    }
}
add_action('admin_menu', 'docs_theme_add_category_submenu_items', 999);

/**
 * Highlight the correct menu item when viewing filtered pages
 */
function docs_theme_highlight_category_menu($parent_file) {
    global $current_screen;
    
    // Check if we're on the pages list with a category filter
    if ($current_screen->base === 'edit' && 
        $current_screen->post_type === 'page' && 
        isset($_GET['page_category'])) {
        $parent_file = 'edit.php?post_type=page';
    }
    
    return $parent_file;
}
add_filter('parent_file', 'docs_theme_highlight_category_menu');

/**
 * Highlight the correct submenu item
 */
function docs_theme_highlight_category_submenu($submenu_file) {
    global $current_screen;
    
    // Check if we're on the pages list with a category filter
    if ($current_screen->base === 'edit' && 
        $current_screen->post_type === 'page' && 
        isset($_GET['page_category'])) {
        $submenu_file = 'edit.php?post_type=page&page_category=' . sanitize_text_field($_GET['page_category']);
    }
    
    return $submenu_file;
}
add_filter('submenu_file', 'docs_theme_highlight_category_submenu');

/**
 * Style the category submenu items
 */
function docs_theme_category_submenu_styles() {
    ?>
    <style>
        /* Style category submenu items */
        #adminmenu .wp-submenu a[href*="page_category="] {
            color: #b0b5ba;
        }
        
        #adminmenu .wp-submenu a[href*="page_category="]:hover {
            color: #00b9eb;
        }
        
        #adminmenu .wp-submenu a[href*="page_category="].current {
            color: #fff;
            font-weight: 600;
        }
    </style>
    <?php
}
add_action('admin_head', 'docs_theme_category_submenu_styles');