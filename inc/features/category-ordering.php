<?php
/**
 * Category Ordering Feature
 * 
 * Enables drag-and-drop ordering for page categories in the WordPress admin
 * and respects that order in the theme's navigation
 */

// Modify the terms query in admin to respect our order
add_action('pre_get_terms', 'docs_theme_order_categories_in_admin');
function docs_theme_order_categories_in_admin($query) {
    if (is_admin() && isset($query->query_vars['taxonomy']) && in_array('page_category', $query->query_vars['taxonomy'])) {
        // Only apply our ordering if no specific orderby is set or if it's the default 'name'
        if (!isset($_GET['orderby']) || $_GET['orderby'] == 'name') {
            $query->query_vars['meta_key'] = 'docs_theme_order';
            $query->query_vars['orderby'] = 'meta_value_num';
            $query->query_vars['order'] = 'ASC';
        }
    }
}

// Force the default sorting in the categories admin screen
add_filter('get_terms_defaults', 'docs_theme_set_category_default_order', 10, 2);
function docs_theme_set_category_default_order($defaults, $taxonomies) {
    if (is_admin() && is_array($taxonomies) && in_array('page_category', $taxonomies)) {
        $screen = get_current_screen();
        if ($screen && $screen->base == 'edit-tags' && !isset($_GET['orderby'])) {
            $defaults['meta_key'] = 'docs_theme_order';
            $defaults['orderby'] = 'meta_value_num';
            $defaults['order'] = 'ASC';
        }
    }
    return $defaults;
}

// Add custom columns to page_category taxonomy
add_filter('manage_edit-page_category_columns', 'docs_theme_add_category_order_column');
function docs_theme_add_category_order_column($columns) {
    $new_columns = array();
    
    foreach($columns as $key => $value) {
        if ($key == 'name') {
            $new_columns[$key] = $value;
            $new_columns['order'] = __('Order', 'docs-theme');
        } else {
            $new_columns[$key] = $value;
        }
    }
    
    return $new_columns;
}

// Display order in the custom column
add_filter('manage_page_category_custom_column', 'docs_theme_show_category_order_column', 10, 3);
function docs_theme_show_category_order_column($content, $column_name, $term_id) {
    if ($column_name == 'order') {
        $order = get_term_meta($term_id, 'docs_theme_order', true);
        if ($order === '') $order = 999; // Show unordered items at the end
        return '<span class="docs-theme-category-order" data-term-id="' . $term_id . '">' . $order . '</span>';
    }
    return $content;
}

// Make the order column sortable
add_filter('manage_edit-page_category_sortable_columns', 'docs_theme_make_order_column_sortable');
function docs_theme_make_order_column_sortable($columns) {
    $columns['order'] = 'order';
    return $columns;
}

// Add inline CSS for category ordering
add_action('admin_head', 'docs_theme_admin_category_order_styles');
function docs_theme_admin_category_order_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit-tags' && $screen->taxonomy == 'page_category') {
        ?>
        <style>
            .column-order { width: 80px; text-align: center; }
            .docs-theme-category-order { 
                display: inline-block;
                background: #f0f0f1;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: 600;
            }
            #the-list tr { cursor: move; }
            #the-list tr.ui-sortable-helper {
                background: #fff;
                box-shadow: 0 3px 6px rgba(0,0,0,0.1);
                display: table;
            }
            #the-list tr.ui-sortable-placeholder {
                height: 40px;
                background: #f0f0f1;
            }
        </style>
        <?php
    }
}

// Enqueue scripts for category ordering
add_action('admin_enqueue_scripts', 'docs_theme_category_ordering_scripts');
function docs_theme_category_ordering_scripts($hook) {
    if ($hook == 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'page_category') {
        wp_enqueue_script('jquery-ui-sortable');
    }
}

// Add JavaScript for drag and drop functionality
add_action('admin_footer', 'docs_theme_category_ordering_js');
function docs_theme_category_ordering_js() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit-tags' && $screen->taxonomy == 'page_category') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('Category ordering script loaded');
            console.log('Found #the-list:', $('#the-list').length);
            
            if ($('#the-list').length) {
                console.log('Initializing sortable on categories');
                $('#the-list').sortable({
                    items: 'tr',
                    axis: 'y',
                    containment: 'parent',
                    helper: function(e, ui) {
                        ui.children().each(function() {
                            $(this).width($(this).width());
                        });
                        return ui;
                    },
                    update: function(event, ui) {
                        var order = [];
                        $('#the-list tr').each(function(index) {
                            var termId = $(this).find('.docs-theme-category-order').data('term-id');
                            if (termId) {
                                order.push({
                                    term_id: termId,
                                    order: index
                                });
                            }
                        });
                        
                        // Send AJAX request to update order
                        $.post(ajaxurl, {
                            action: 'docs_theme_update_category_order',
                            order: order,
                            nonce: '<?php echo wp_create_nonce('docs_theme_category_order'); ?>'
                        }, function(response) {
                            if (response.success) {
                                // Update the displayed order numbers
                                order.forEach(function(item) {
                                    $('.docs-theme-category-order[data-term-id="' + item.term_id + '"]').text(item.order);
                                });
                                console.log('Category order updated successfully');
                            } else {
                                console.error('Failed to update category order:', response);
                                alert('Failed to update category order. Please try again.');
                            }
                        }).fail(function(xhr, status, error) {
                            console.error('AJAX request failed:', status, error);
                            alert('Failed to update category order. Please check the console for errors.');
                        });
                    }
                });
                
                // Add visual feedback
                $('#the-list').disableSelection();
            }
        });
        </script>
        <?php
    }
}

// Handle AJAX request to update category order
add_action('wp_ajax_docs_theme_update_category_order', 'docs_theme_handle_category_order_update');
function docs_theme_handle_category_order_update() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'docs_theme_category_order')) {
        wp_send_json_error('Security check failed: Invalid nonce');
        wp_die();
    }
    
    // Check permissions
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Insufficient permissions');
        wp_die();
    }
    
    // Update category orders
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $updated = array();
        foreach ($_POST['order'] as $item) {
            $term_id = intval($item['term_id']);
            $order = intval($item['order']);
            update_term_meta($term_id, 'docs_theme_order', $order);
            $updated[] = array('term_id' => $term_id, 'order' => $order);
        }
        
        wp_send_json_success(array('updated' => $updated));
    } else {
        wp_send_json_error('Invalid order data');
    }
}

// Modify category query to respect custom order
add_filter('docs_theme_categories_query_args', 'docs_theme_apply_category_ordering');
function docs_theme_apply_category_ordering($args) {
    $args['meta_key'] = 'docs_theme_order';
    $args['orderby'] = 'meta_value_num';
    $args['order'] = 'ASC';
    
    return $args;
}

// Initialize order for existing categories on activation
add_action('admin_init', 'docs_theme_initialize_category_order');
function docs_theme_initialize_category_order() {
    // Force re-initialization if requested
    if (isset($_GET['reset_category_order']) && current_user_can('manage_categories')) {
        delete_option('docs_theme_category_order_initialized');
    }
    
    // Check if we've already initialized
    if (get_option('docs_theme_category_order_initialized')) {
        return;
    }
    
    // Get all categories
    $categories = get_terms(array(
        'taxonomy' => 'page_category',
        'hide_empty' => false,
    ));
    
    if (!empty($categories) && !is_wp_error($categories)) {
        $order = 0;
        foreach ($categories as $category) {
            $existing_order = get_term_meta($category->term_id, 'docs_theme_order', true);
            if ($existing_order === '') {
                update_term_meta($category->term_id, 'docs_theme_order', $order);
                $order++;
            }
        }
    }
    
    // Mark as initialized
    update_option('docs_theme_category_order_initialized', true);
}

// When creating a new category, assign it the next order number
add_action('created_page_category', 'docs_theme_set_initial_category_order', 10, 2);
function docs_theme_set_initial_category_order($term_id, $tt_id) {
    // Get the highest current order
    $categories = get_terms(array(
        'taxonomy' => 'page_category',
        'hide_empty' => false,
        'meta_key' => 'docs_theme_order',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'number' => 1
    ));
    
    $highest_order = 0;
    if (!empty($categories) && !is_wp_error($categories)) {
        $highest_order = intval(get_term_meta($categories[0]->term_id, 'docs_theme_order', true));
    }
    
    // Set the order for the new category
    update_term_meta($term_id, 'docs_theme_order', $highest_order + 1);
}