<?php
/**
 * Font Options Feature
 * 
 * Adds multiple font options and Google Fonts support
 */

// Enqueue Google Fonts
add_action('wp_enqueue_scripts', 'docs_theme_enqueue_fonts');
function docs_theme_enqueue_fonts() {
    // Body fonts
    $body_fonts = array(
        'Inter:wght@400;500;600;700',
        'IBM+Plex+Sans:wght@400;500;600;700',
        'Source+Sans+3:wght@400;500;600;700',
        'Roboto:wght@400;500;700'
    );
    
    // Code fonts
    $code_fonts = array(
        'JetBrains+Mono:wght@400;500',
        'Fira+Code:wght@400;500',
        'Source+Code+Pro:wght@400;500'
    );
    
    // Combine all fonts
    $all_fonts = array_merge($body_fonts, $code_fonts);
    $fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $all_fonts) . '&display=swap';
    
    wp_enqueue_style('docs-theme-google-fonts', $fonts_url, array(), null);
}

// Add font switching JavaScript
add_action('wp_footer', 'docs_theme_font_switcher_js');
function docs_theme_font_switcher_js() {
    ?>
    <script>
    // Font switcher functions for testing
    window.DocsTheme = window.DocsTheme || {};
    
    // Available fonts
    window.DocsTheme.fonts = {
        body: {
            'system': '-apple-system, "system-ui", "Segoe UI", Helvetica, "Apple Color Emoji", Arial, sans-serif, "Segoe UI Emoji", "Segoe UI Symbol"',
            'inter': '"Inter", -apple-system, "system-ui", "Segoe UI", Helvetica, Arial, sans-serif',
            'ibm-plex': '"IBM Plex Sans", -apple-system, "system-ui", "Segoe UI", Helvetica, Arial, sans-serif',
            'source-sans': '"Source Sans 3", -apple-system, "system-ui", "Segoe UI", Helvetica, Arial, sans-serif',
            'roboto': '"Roboto", -apple-system, "system-ui", "Segoe UI", Helvetica, Arial, sans-serif'
        },
        code: {
            'system': 'ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace',
            'jetbrains': '"JetBrains Mono", ui-monospace, SFMono-Regular, Consolas, monospace',
            'fira': '"Fira Code", ui-monospace, SFMono-Regular, Consolas, monospace',
            'source-code': '"Source Code Pro", ui-monospace, SFMono-Regular, Consolas, monospace'
        }
    };
    
    // Current font indices
    window.DocsTheme.currentBodyIndex = 0;
    window.DocsTheme.currentCodeIndex = 0;
    
    // Get font keys as arrays
    window.DocsTheme.bodyFontKeys = Object.keys(window.DocsTheme.fonts.body);
    window.DocsTheme.codeFontKeys = Object.keys(window.DocsTheme.fonts.code);
    
    // Function to cycle through fonts
    window.DocsTheme.nextFont = function() {
        // Increment indices
        window.DocsTheme.currentBodyIndex = (window.DocsTheme.currentBodyIndex + 1) % window.DocsTheme.bodyFontKeys.length;
        window.DocsTheme.currentCodeIndex = (window.DocsTheme.currentCodeIndex + 1) % window.DocsTheme.codeFontKeys.length;
        
        // Get current font keys
        var bodyKey = window.DocsTheme.bodyFontKeys[window.DocsTheme.currentBodyIndex];
        var codeKey = window.DocsTheme.codeFontKeys[window.DocsTheme.currentCodeIndex];
        
        // Apply fonts
        document.documentElement.style.setProperty('--font-family-body', window.DocsTheme.fonts.body[bodyKey]);
        document.documentElement.style.setProperty('--font-family-mono', window.DocsTheme.fonts.code[codeKey]);
        
        // Update body font directly too for immediate effect
        document.body.style.fontFamily = window.DocsTheme.fonts.body[bodyKey];
        
        // Display current fonts
        console.clear();
        console.log('%c🔤 Current Fonts', 'font-size: 18px; font-weight: bold; color: #2563eb;');
        console.log('%cBody: ' + bodyKey.toUpperCase(), 'font-size: 14px; color: #059669; font-weight: bold');
        console.log('%cCode: ' + codeKey.toUpperCase(), 'font-size: 14px; color: #7c3aed; font-weight: bold');
        console.log('');
        console.log('Press DocsTheme.nextFont() to cycle through fonts');
        console.log('Press DocsTheme.setFontSize(15) to change size');
        
        return 'Body: ' + bodyKey + ', Code: ' + codeKey;
    };
    
    // Function to change font size
    window.DocsTheme.setFontSize = function(size) {
        document.body.style.fontSize = size + 'px';
        console.log('Font size changed to:', size + 'px');
    };
    
    // Function to reset all fonts
    window.DocsTheme.resetFonts = function() {
        document.documentElement.style.removeProperty('--font-family-body');
        document.documentElement.style.removeProperty('--font-family-mono');
        document.body.style.removeProperty('font-family');
        document.body.style.removeProperty('font-size');
        window.DocsTheme.currentBodyIndex = 0;
        window.DocsTheme.currentCodeIndex = 0;
        console.log('Fonts reset to defaults');
    };
    
    // Instructions
    console.log('%c🔤 Docs Theme Font Tester', 'font-size: 18px; font-weight: bold; color: #2563eb;');
    console.log('%cQuick Start:', 'font-weight: bold; color: #059669;');
    console.log('DocsTheme.nextFont()     - Cycle through font combinations');
    console.log('DocsTheme.setFontSize(15) - Change font size');
    console.log('DocsTheme.resetFonts()    - Reset to defaults');
    </script>
    <?php
}