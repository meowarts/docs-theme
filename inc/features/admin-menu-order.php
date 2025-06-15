<?php
/**
 * Admin Menu Order Feature
 * 
 * Reorders the WordPress admin menu to prioritize Pages
 */

// Enable custom menu order
add_filter('custom_menu_order', '__return_true');

// Set the custom menu order
add_filter('menu_order', 'docs_theme_custom_menu_order');
function docs_theme_custom_menu_order($menu_order) {
    // Define our preferred order
    $new_order = array(
        'index.php',                    // Dashboard
        'separator1',                   // First separator
        'edit.php?post_type=page',      // Pages (moved to top)
        'upload.php',                   // Media
        'edit.php',                     // Posts
    );
    
    // Get all other menu items that aren't in our custom order
    $remaining_items = array_diff($menu_order, $new_order);
    
    // Combine our custom order with the remaining items
    return array_merge($new_order, $remaining_items);
}