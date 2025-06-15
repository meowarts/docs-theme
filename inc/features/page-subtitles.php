<?php
/**
 * Page Subtitles Feature
 * 
 * Adds subtitle support to pages with automatic display below the title
 */

// Add subtitle meta box to page editor (Classic Editor fallback)
add_action('add_meta_boxes', 'docs_theme_add_subtitle_meta_box');
function docs_theme_add_subtitle_meta_box() {
    // Only add meta box if not using block editor
    if (!use_block_editor_for_post_type('page')) {
        add_meta_box(
            'docs_theme_subtitle',
            __('Page Subtitle', 'docs-theme'),
            'docs_theme_subtitle_meta_box_callback',
            'page',
            'side',
            'default'
        );
    }
}

// Meta box callback
function docs_theme_subtitle_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('docs_theme_subtitle_meta_box', 'docs_theme_subtitle_meta_box_nonce');
    
    // Get existing value
    $subtitle = get_post_meta($post->ID, '_docs_theme_subtitle', true);
    
    ?>
    <p>
        <textarea id="docs_theme_subtitle_field" name="docs_theme_subtitle" class="widefat" rows="4"><?php echo esc_textarea($subtitle); ?></textarea>
        <span class="description"><?php _e('Appears below the page title', 'docs-theme'); ?></span>
    </p>
    <?php
}

// Save subtitle when page is saved
add_action('save_post_page', 'docs_theme_save_subtitle_meta');
function docs_theme_save_subtitle_meta($post_id) {
    // Check if nonce is set
    if (!isset($_POST['docs_theme_subtitle_meta_box_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['docs_theme_subtitle_meta_box_nonce'], 'docs_theme_subtitle_meta_box')) {
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
    
    // Save the subtitle
    if (isset($_POST['docs_theme_subtitle'])) {
        update_post_meta($post_id, '_docs_theme_subtitle', sanitize_textarea_field($_POST['docs_theme_subtitle']));
    }
}

// Add subtitle support to REST API for block editor
add_action('init', 'docs_theme_register_subtitle_meta');
function docs_theme_register_subtitle_meta() {
    register_post_meta('page', '_docs_theme_subtitle', array(
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        }
    ));
}

// Enqueue block editor assets
add_action('enqueue_block_editor_assets', 'docs_theme_enqueue_subtitle_block_editor_assets');
function docs_theme_enqueue_subtitle_block_editor_assets() {
    // Only load on page editor
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'page') {
        wp_enqueue_script(
            'docs-theme-page-subtitle-editor',
            get_template_directory_uri() . '/assets/js/page-subtitle-block-editor.js',
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-core-data',
                'wp-i18n'
            ),
            defined('DOCS_THEME_VERSION') ? DOCS_THEME_VERSION : '1.0.0',
            true
        );
        
        // Enqueue editor styles
        wp_enqueue_style(
            'docs-theme-editor-styles',
            get_template_directory_uri() . '/assets/css/editor-styles.css',
            array(),
            defined('DOCS_THEME_VERSION') ? DOCS_THEME_VERSION : '1.0.0'
        );
    }
}

// Helper function to get page subtitle
function docs_theme_get_subtitle($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, '_docs_theme_subtitle', true);
}

// Display subtitle after page title
add_action('docs_theme_after_page_title', 'docs_theme_display_subtitle');
function docs_theme_display_subtitle() {
    if (is_page()) {
        $subtitle = docs_theme_get_subtitle();
        if ($subtitle) {
            // Convert line breaks to <br> tags for display
            $subtitle_html = nl2br(esc_html($subtitle));
            echo '<p class="page-subtitle">' . $subtitle_html . '</p>';
        }
    }
}