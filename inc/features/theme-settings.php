<?php
/**
 * Theme Settings Page
 * 
 * Adds a settings page for Docs Theme configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu
 */
function docs_theme_add_admin_menu() {
    add_menu_page(
        __('Docs Theme Settings', 'docs-theme'),
        __('Docs Theme', 'docs-theme'),
        'manage_options',
        'docs-theme-settings',
        'docs_theme_settings_page',
        'dashicons-book-alt',
        59
    );
}
add_action('admin_menu', 'docs_theme_add_admin_menu');

/**
 * Register settings
 */
function docs_theme_register_settings() {
    // Register settings
    register_setting('docs_theme_settings', 'docs_theme_show_breadcrumbs');
    register_setting('docs_theme_settings', 'docs_theme_show_last_updated');
    register_setting('docs_theme_settings', 'docs_theme_show_reading_time');
    register_setting('docs_theme_settings', 'docs_theme_green_button_label');
    register_setting('docs_theme_settings', 'docs_theme_green_button_url');
    register_setting('docs_theme_settings', 'docs_theme_purple_button_label');
    register_setting('docs_theme_settings', 'docs_theme_purple_button_url');
    
    // Add settings sections
    add_settings_section(
        'docs_theme_display_section',
        __('Display Settings', 'docs-theme'),
        'docs_theme_display_section_callback',
        'docs_theme_settings'
    );
    
    add_settings_section(
        'docs_theme_buttons_section',
        __('Header Buttons', 'docs-theme'),
        'docs_theme_buttons_section_callback',
        'docs_theme_settings'
    );
    
    // Add settings fields
    add_settings_field(
        'docs_theme_show_breadcrumbs',
        __('Show Breadcrumbs', 'docs-theme'),
        'docs_theme_breadcrumbs_callback',
        'docs_theme_settings',
        'docs_theme_display_section'
    );
    
    add_settings_field(
        'docs_theme_show_last_updated',
        __('Show Last Updated Date', 'docs-theme'),
        'docs_theme_last_updated_callback',
        'docs_theme_settings',
        'docs_theme_display_section'
    );
    
    add_settings_field(
        'docs_theme_show_reading_time',
        __('Show Reading Time', 'docs-theme'),
        'docs_theme_reading_time_callback',
        'docs_theme_settings',
        'docs_theme_display_section'
    );
    
    // Green button fields
    add_settings_field(
        'docs_theme_green_button_label',
        __('Green Button Label', 'docs-theme'),
        'docs_theme_green_button_label_callback',
        'docs_theme_settings',
        'docs_theme_buttons_section'
    );
    
    add_settings_field(
        'docs_theme_green_button_url',
        __('Green Button URL', 'docs-theme'),
        'docs_theme_green_button_url_callback',
        'docs_theme_settings',
        'docs_theme_buttons_section'
    );
    
    // Purple button fields
    add_settings_field(
        'docs_theme_purple_button_label',
        __('Purple Button Label', 'docs-theme'),
        'docs_theme_purple_button_label_callback',
        'docs_theme_settings',
        'docs_theme_buttons_section'
    );
    
    add_settings_field(
        'docs_theme_purple_button_url',
        __('Purple Button URL', 'docs-theme'),
        'docs_theme_purple_button_url_callback',
        'docs_theme_settings',
        'docs_theme_buttons_section'
    );
}
add_action('admin_init', 'docs_theme_register_settings');

/**
 * Section callbacks
 */
function docs_theme_display_section_callback() {
    echo '<p>' . __('Configure which elements to display on your documentation pages.', 'docs-theme') . '</p>';
}

function docs_theme_buttons_section_callback() {
    echo '<p>' . __('Add custom buttons to the header. Both label and URL are required for a button to appear.', 'docs-theme') . '</p>';
}

/**
 * Field callbacks
 */
function docs_theme_breadcrumbs_callback() {
    $value = get_option('docs_theme_show_breadcrumbs', '1');
    ?>
    <label>
        <input type="checkbox" name="docs_theme_show_breadcrumbs" value="1" <?php checked($value, '1'); ?> />
        <?php _e('Display breadcrumb navigation above page titles', 'docs-theme'); ?>
    </label>
    <?php
}

function docs_theme_last_updated_callback() {
    $value = get_option('docs_theme_show_last_updated', '1');
    ?>
    <label>
        <input type="checkbox" name="docs_theme_show_last_updated" value="1" <?php checked($value, '1'); ?> />
        <?php _e('Display last updated date below page subtitle', 'docs-theme'); ?>
    </label>
    <?php
}

function docs_theme_reading_time_callback() {
    $value = get_option('docs_theme_show_reading_time', '1');
    ?>
    <label>
        <input type="checkbox" name="docs_theme_show_reading_time" value="1" <?php checked($value, '1'); ?> />
        <?php _e('Display estimated reading time', 'docs-theme'); ?>
    </label>
    <?php
}

/**
 * Settings page HTML
 */
function docs_theme_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if settings were saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error('docs_theme_messages', 'docs_theme_message', __('Settings Saved', 'docs-theme'), 'updated');
    }
    
    settings_errors('docs_theme_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('docs_theme_settings');
            do_settings_sections('docs_theme_settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

/**
 * Add settings link to plugins page
 */
function docs_theme_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=docs-theme-settings') . '">' . __('Settings', 'docs-theme') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
// Note: This would be for plugins, not themes

/**
 * Helper functions to check settings
 */
function docs_theme_show_breadcrumbs() {
    return get_option('docs_theme_show_breadcrumbs', '1') === '1';
}

function docs_theme_show_last_updated() {
    return get_option('docs_theme_show_last_updated', '1') === '1';
}

function docs_theme_show_reading_time() {
    return get_option('docs_theme_show_reading_time', '1') === '1';
}

// Button field callbacks
function docs_theme_green_button_label_callback() {
    $value = get_option('docs_theme_green_button_label', '');
    ?>
    <input type="text" name="docs_theme_green_button_label" value="<?php echo esc_attr($value); ?>" class="regular-text" />
    <p class="description"><?php _e('Label for the green button (e.g., "Free Version")', 'docs-theme'); ?></p>
    <?php
}

function docs_theme_green_button_url_callback() {
    $value = get_option('docs_theme_green_button_url', '');
    ?>
    <input type="url" name="docs_theme_green_button_url" value="<?php echo esc_attr($value); ?>" class="regular-text" />
    <p class="description"><?php _e('URL for the green button', 'docs-theme'); ?></p>
    <?php
}

function docs_theme_purple_button_label_callback() {
    $value = get_option('docs_theme_purple_button_label', '');
    ?>
    <input type="text" name="docs_theme_purple_button_label" value="<?php echo esc_attr($value); ?>" class="regular-text" />
    <p class="description"><?php _e('Label for the purple button (e.g., "Pro Version")', 'docs-theme'); ?></p>
    <?php
}

function docs_theme_purple_button_url_callback() {
    $value = get_option('docs_theme_purple_button_url', '');
    ?>
    <input type="url" name="docs_theme_purple_button_url" value="<?php echo esc_attr($value); ?>" class="regular-text" />
    <p class="description"><?php _e('URL for the purple button', 'docs-theme'); ?></p>
    <?php
}

// Helper functions to get button settings
function docs_theme_get_green_button() {
    $label = get_option('docs_theme_green_button_label', '');
    $url = get_option('docs_theme_green_button_url', '');
    
    if (!empty($label) && !empty($url)) {
        return array('label' => $label, 'url' => $url);
    }
    
    return false;
}

function docs_theme_get_purple_button() {
    $label = get_option('docs_theme_purple_button_label', '');
    $url = get_option('docs_theme_purple_button_url', '');
    
    if (!empty($label) && !empty($url)) {
        return array('label' => $label, 'url' => $url);
    }
    
    return false;
}