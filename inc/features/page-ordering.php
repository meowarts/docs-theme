<?php
/**
 * Page Ordering Feature
 * 
 * Enables drag-and-drop ordering for pages in the WordPress admin
 * and respects that order in the theme's navigation
 */

// Enable page attributes support for pages (includes menu order)
add_action('init', 'docs_theme_enable_page_attributes');
function docs_theme_enable_page_attributes() {
    add_post_type_support('page', 'page-attributes');
}

// Add custom columns to pages list in admin
add_filter('manage_pages_columns', 'docs_theme_add_order_column', 20);
function docs_theme_add_order_column($columns) {
    // Add menu_order column at the end
    $columns['menu_order'] = __('Order', 'docs-theme');
    
    return $columns;
}

// Display menu order in the custom column
add_action('manage_pages_custom_column', 'docs_theme_show_order_column', 10, 2);
function docs_theme_show_order_column($column_name, $post_id) {
    if ($column_name == 'menu_order') {
        $post = get_post($post_id);
        echo '<span class="docs-theme-order">' . $post->menu_order . '</span>';
    }
}

// Make the pages table sortable by menu order
add_filter('manage_edit-page_sortable_columns', 'docs_theme_order_column_sortable');
function docs_theme_order_column_sortable($columns) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
}

// Add inline CSS for better UI in admin
add_action('admin_head', 'docs_theme_admin_order_styles');
function docs_theme_admin_order_styles() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit' && $screen->post_type == 'page') {
        ?>
        <style>
            .column-menu_order { width: 80px; text-align: center; }
            .docs-theme-order { 
                display: inline-block;
                background: #f0f0f1;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: 600;
            }
            .wp-list-table tbody tr { cursor: move; }
            .wp-list-table tbody tr.ui-sortable-helper {
                background: #fff;
                box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            }
            .wp-list-table tbody tr.ui-sortable-placeholder {
                height: 40px;
                background: #f0f0f1;
            }
        </style>
        <?php
    }
}

// Enqueue scripts for page ordering
add_action('admin_enqueue_scripts', 'docs_theme_page_ordering_scripts');
function docs_theme_page_ordering_scripts($hook) {
    if ($hook == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'page') {
        wp_enqueue_script('jquery-ui-sortable');
    }
}

// Add JavaScript for drag and drop functionality
add_action('admin_footer', 'docs_theme_page_ordering_js');
function docs_theme_page_ordering_js() {
    $screen = get_current_screen();
    if ($screen && $screen->base == 'edit' && $screen->post_type == 'page') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Check if we're on the pages list and not in search results
            if ($('#the-list').length && !$('input[name="s"]').val()) {
                $('#the-list').sortable({
                    items: 'tr',
                    axis: 'y',
                    helper: function(e, ui) {
                        ui.children().each(function() {
                            $(this).width($(this).width());
                        });
                        return ui;
                    },
                    update: function(event, ui) {
                        var order = [];
                        $('#the-list tr').each(function(index) {
                            var id = $(this).attr('id');
                            if (id) {
                                var postId = id.replace('post-', '');
                                order.push({
                                    id: postId,
                                    menu_order: index
                                });
                            }
                        });
                        
                        // Send AJAX request to update order
                        $.post(ajaxurl, {
                            action: 'docs_theme_update_page_order',
                            order: order,
                            nonce: '<?php echo wp_create_nonce('docs_theme_page_order'); ?>'
                        }, function(response) {
                            if (response.success) {
                                // Update the displayed order numbers
                                $('#the-list tr').each(function(index) {
                                    $(this).find('.docs-theme-order').text(index);
                                });
                            }
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

// Handle AJAX request to update page order
add_action('wp_ajax_docs_theme_update_page_order', 'docs_theme_handle_page_order_update');
function docs_theme_handle_page_order_update() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'docs_theme_page_order')) {
        wp_die('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('edit_pages')) {
        wp_die('Insufficient permissions');
    }
    
    // Update page orders
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        foreach ($_POST['order'] as $item) {
            wp_update_post(array(
                'ID' => intval($item['id']),
                'menu_order' => intval($item['menu_order'])
            ));
        }
        
        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid order data');
    }
}

// Modify the main query for page ordering in the theme
add_filter('docs_theme_pages_query_args', 'docs_theme_apply_page_ordering');
function docs_theme_apply_page_ordering($args) {
    // Add orderby menu_order and then title as fallback
    $args['orderby'] = 'menu_order title';
    $args['order'] = 'ASC';
    
    return $args;
}