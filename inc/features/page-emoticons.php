<?php
/**
 * Page Emoticons Feature
 * 
 * Adds emoticon/emoji support to pages for visual identification
 */

// Add emoticon meta box to page editor (Classic Editor fallback)
add_action('add_meta_boxes', 'docs_theme_add_emoticon_meta_box');
function docs_theme_add_emoticon_meta_box() {
    // Only add meta box if not using block editor
    if (!use_block_editor_for_post_type('page')) {
        add_meta_box(
            'docs_theme_emoticon',
            __('Page Emoticon', 'docs-theme'),
            'docs_theme_emoticon_meta_box_callback',
            'page',
            'side',
            'default'
        );
    }
}

// Meta box callback
function docs_theme_emoticon_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('docs_theme_emoticon_meta_box', 'docs_theme_emoticon_meta_box_nonce');
    
    // Get existing value
    $emoticon = get_post_meta($post->ID, '_docs_theme_emoticon', true);
    
    ?>
    <p>
        <input type="text" id="docs_theme_emoticon_field" name="docs_theme_emoticon" class="widefat" value="<?php echo esc_attr($emoticon); ?>" maxlength="2" />
        <span class="description"><?php _e('Single emoji or icon (e.g. 📚, 🔧, 💡)', 'docs-theme'); ?></span>
    </p>
    <?php
}

// Save emoticon when page is saved
add_action('save_post_page', 'docs_theme_save_emoticon_meta');
function docs_theme_save_emoticon_meta($post_id) {
    // Check if nonce is set
    if (!isset($_POST['docs_theme_emoticon_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['docs_theme_emoticon_meta_box_nonce'], 'docs_theme_emoticon_meta_box')) {
        return;
    }
    
    // Check if this is an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }
    
    // Save the emoticon
    if (isset($_POST['docs_theme_emoticon'])) {
        // Sanitize and limit to 2 characters (most emojis are 1-2 characters)
        $emoticon = mb_substr(sanitize_text_field($_POST['docs_theme_emoticon']), 0, 2);
        update_post_meta($post_id, '_docs_theme_emoticon', $emoticon);
    }
}

// Add emoticon support to REST API for block editor
add_action('init', 'docs_theme_register_emoticon_meta');
function docs_theme_register_emoticon_meta() {
    register_post_meta('page', '_docs_theme_emoticon', array(
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
}

// Helper function to get page emoticon
function docs_theme_get_emoticon($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, '_docs_theme_emoticon', true);
}

// Add emoticon to page title display
add_filter('docs_theme_page_title_output', 'docs_theme_add_emoticon_to_title', 10, 2);
function docs_theme_add_emoticon_to_title($title_html, $post_id) {
    $emoticon = docs_theme_get_emoticon($post_id);
    if ($emoticon) {
        // Add emoticon before the title with a span for styling
        $title_html = '<span class="page-emoticon">' . esc_html($emoticon) . '</span> ' . $title_html;
    }
    return $title_html;
}

// Add emoticon data to REST API responses for pages
add_action('rest_api_init', 'docs_theme_add_emoticon_to_rest_api');
function docs_theme_add_emoticon_to_rest_api() {
    register_rest_field('page', 'emoticon', array(
        'get_callback' => function($page) {
            return docs_theme_get_emoticon($page['id']);
        },
        'schema' => array(
            'description' => __('Page emoticon', 'docs-theme'),
            'type' => 'string',
            'context' => array('view', 'edit')
        )
    ));
}