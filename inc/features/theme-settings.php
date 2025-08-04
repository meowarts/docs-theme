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
    register_setting('docs_theme_settings', 'docs_theme_color_scheme');
    register_setting('docs_theme_settings', 'docs_theme_single_page_app');
    // Register sidebar button sections
    register_setting('docs_theme_settings', 'docs_theme_sidebar_button_sections');
    
    // Add settings sections
    add_settings_section(
        'docs_theme_display_section',
        __('Display Settings', 'docs-theme'),
        'docs_theme_display_section_callback',
        'docs_theme_settings'
    );
    
    add_settings_section(
        'docs_theme_sidebar_buttons_section',
        __('Sidebar Buttons', 'docs-theme'),
        'docs_theme_sidebar_buttons_section_callback',
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
    
    add_settings_field(
        'docs_theme_color_scheme',
        __('Color Scheme', 'docs-theme'),
        'docs_theme_color_scheme_callback',
        'docs_theme_settings',
        'docs_theme_display_section'
    );
    
    add_settings_field(
        'docs_theme_single_page_app',
        __('Single Page App', 'docs-theme'),
        'docs_theme_single_page_app_callback',
        'docs_theme_settings',
        'docs_theme_display_section'
    );
    
    // Sidebar button sections field
    add_settings_field(
        'docs_theme_sidebar_button_sections',
        __('Button Sections', 'docs-theme'),
        'docs_theme_sidebar_button_sections_callback',
        'docs_theme_settings',
        'docs_theme_sidebar_buttons_section'
    );
}
add_action('admin_init', 'docs_theme_register_settings');

/**
 * Section callbacks
 */
function docs_theme_display_section_callback() {
    echo '<p>' . __('Configure which elements to display on your documentation pages.', 'docs-theme') . '</p>';
}

function docs_theme_sidebar_buttons_section_callback() {
    echo '<p>' . __('Add button sections to the sidebar. Each section can have up to two buttons.', 'docs-theme') . '</p>';
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

function docs_theme_color_scheme_callback() {
    $value = get_option('docs_theme_color_scheme', 'dark');
    ?>
    <select name="docs_theme_color_scheme" id="docs_theme_color_scheme">
        <option value="dark" <?php selected($value, 'dark'); ?>><?php _e('Dark', 'docs-theme'); ?></option>
        <option value="light" <?php selected($value, 'light'); ?>><?php _e('Light', 'docs-theme'); ?></option>
    </select>
    <p class="description"><?php _e('Choose between dark and light color schemes for your documentation.', 'docs-theme'); ?></p>
    <?php
}

function docs_theme_single_page_app_callback() {
    $value = get_option('docs_theme_single_page_app', '1');
    ?>
    <label>
        <input type="checkbox" name="docs_theme_single_page_app" value="1" <?php checked($value, '1'); ?> />
        <?php _e('Enable Single Page App mode for faster navigation', 'docs-theme'); ?>
    </label>
    <p class="description"><?php _e('When enabled, pages load dynamically without full page refreshes. Disable for traditional navigation.', 'docs-theme'); ?></p>
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

function docs_theme_get_color_scheme() {
    return get_option('docs_theme_color_scheme', 'dark');
}

function docs_theme_is_single_page_app() {
    return get_option('docs_theme_single_page_app', '1') === '1';
}

// Sidebar button sections callback
function docs_theme_sidebar_button_sections_callback() {
    $sections = get_option('docs_theme_sidebar_button_sections', array());
    if (!is_array($sections)) {
        $sections = array();
    }
    ?>
    <div id="docs-theme-button-sections">
        <div class="button-sections-container">
            <?php if (!empty($sections)) : ?>
                <?php foreach ($sections as $index => $section) : ?>
                    <div class="button-section" data-index="<?php echo esc_attr($index); ?>">
                        <div class="section-header">
                            <h4>Section <?php echo $index + 1; ?></h4>
                            <button type="button" class="button button-small remove-section" data-index="<?php echo esc_attr($index); ?>"><?php _e('Remove', 'docs-theme'); ?></button>
                        </div>
                        <table class="form-table">
                            <tr>
                                <th><label><?php _e('Section Name', 'docs-theme'); ?></label></th>
                                <td>
                                    <input type="text" name="docs_theme_sidebar_button_sections[<?php echo $index; ?>][name]" value="<?php echo esc_attr($section['name'] ?? ''); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Primary Button Label', 'docs-theme'); ?></label></th>
                                <td>
                                    <input type="text" name="docs_theme_sidebar_button_sections[<?php echo $index; ?>][primary_label]" value="<?php echo esc_attr($section['primary_label'] ?? ''); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Primary Button URL', 'docs-theme'); ?></label></th>
                                <td>
                                    <input type="url" name="docs_theme_sidebar_button_sections[<?php echo $index; ?>][primary_url]" value="<?php echo esc_attr($section['primary_url'] ?? ''); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Secondary Button Label', 'docs-theme'); ?></label></th>
                                <td>
                                    <input type="text" name="docs_theme_sidebar_button_sections[<?php echo $index; ?>][secondary_label]" value="<?php echo esc_attr($section['secondary_label'] ?? ''); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('Secondary Button URL', 'docs-theme'); ?></label></th>
                                <td>
                                    <input type="url" name="docs_theme_sidebar_button_sections[<?php echo $index; ?>][secondary_url]" value="<?php echo esc_attr($section['secondary_url'] ?? ''); ?>" class="regular-text" />
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="button" id="add-button-section"><?php _e('Add New Section', 'docs-theme'); ?></button>
    </div>
    
    <style>
        .button-section {
            background: #f6f7f7;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dcdcde;
            border-radius: 4px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .section-header h4 {
            margin: 0;
        }
        .button-section .form-table {
            margin-top: 0;
        }
        .button-section .form-table th {
            width: 180px;
            padding-left: 0;
        }
    </style>
    
    <script>
    (function($) {
        if (!$) {
            console.error('jQuery not available for Docs Theme settings');
            return;
        }
        var sectionIndex = <?php echo count($sections); ?>;
        
        $('#add-button-section').on('click', function() {
            var newSection = `
                <div class="button-section" data-index="${sectionIndex}">
                    <div class="section-header">
                        <h4>Section ${sectionIndex + 1}</h4>
                        <button type="button" class="button button-small remove-section" data-index="${sectionIndex}">Remove</button>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th><label>Section Name</label></th>
                            <td>
                                <input type="text" name="docs_theme_sidebar_button_sections[${sectionIndex}][name]" value="" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label>Primary Button Label</label></th>
                            <td>
                                <input type="text" name="docs_theme_sidebar_button_sections[${sectionIndex}][primary_label]" value="" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label>Primary Button URL</label></th>
                            <td>
                                <input type="url" name="docs_theme_sidebar_button_sections[${sectionIndex}][primary_url]" value="" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label>Secondary Button Label</label></th>
                            <td>
                                <input type="text" name="docs_theme_sidebar_button_sections[${sectionIndex}][secondary_label]" value="" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label>Secondary Button URL</label></th>
                            <td>
                                <input type="url" name="docs_theme_sidebar_button_sections[${sectionIndex}][secondary_url]" value="" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>
            `;
            
            $('.button-sections-container').append(newSection);
            sectionIndex++;
        });
        
        $(document).on('click', '.remove-section', function() {
            $(this).closest('.button-section').remove();
        });
    })(jQuery);
    </script>
    <?php
}

// Helper function to get sidebar button sections
function docs_theme_get_sidebar_button_sections() {
    $sections = get_option('docs_theme_sidebar_button_sections', array());
    if (!is_array($sections)) {
        return array();
    }
    
    // Filter out sections without required data
    $valid_sections = array();
    foreach ($sections as $section) {
        if (!empty($section['name']) && 
            ((!empty($section['primary_label']) && !empty($section['primary_url'])) ||
             (!empty($section['secondary_label']) && !empty($section['secondary_url'])))) {
            $valid_sections[] = $section;
        }
    }
    
    return $valid_sections;
}