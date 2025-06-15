<?php
/**
 * Block Styles Feature
 * 
 * Adds custom block styles for enhanced content display
 */

namespace DocsTheme;

// Register custom block styles
add_action('init', __NAMESPACE__ . '\\register_block_styles');

function register_block_styles() {
    // Blockquote styles
    register_block_style(
        'core/quote',
        array(
            'name'  => 'info',
            'label' => __('Info', 'docs-theme'),
        )
    );
    
    register_block_style(
        'core/quote',
        array(
            'name'  => 'warning',
            'label' => __('Warning', 'docs-theme'),
        )
    );
    
    register_block_style(
        'core/quote',
        array(
            'name'  => 'danger',
            'label' => __('Danger', 'docs-theme'),
        )
    );
    
    register_block_style(
        'core/quote',
        array(
            'name'  => 'success',
            'label' => __('Success', 'docs-theme'),
        )
    );
}

// Add inline styles for the block styles
add_action('wp_head', __NAMESPACE__ . '\\add_block_style_css');

function add_block_style_css() {
    ?>
    <style>
        /* Blockquote styles with icons and vibrant colors */
        .wp-block-quote {
            position: relative;
            padding-left: calc(var(--spacing-xl) + 60px) !important;
        }
        
        .wp-block-quote.is-style-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-left: none;
            color: #dbeafe;
        }
        
        .wp-block-quote.is-style-warning {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.2) 0%, rgba(245, 158, 11, 0.15) 100%);
            border-left: none;
            color: #fef3c7;
        }
        
        .wp-block-quote.is-style-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.15) 100%);
            border-left: none;
            color: #fee2e2;
        }
        
        .wp-block-quote.is-style-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(16, 185, 129, 0.15) 100%);
            border-left: none;
            color: #d1fae5;
        }
        
        /* Add icons using pseudo-elements */
        .wp-block-quote::before {
            content: "";
            position: absolute;
            left: var(--spacing-lg);
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.9;
        }
        
        /* Default info icon */
        .wp-block-quote::before,
        .wp-block-quote.is-style-info::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%233b82f6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .wp-block-quote.is-style-warning::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23f59e0b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .wp-block-quote.is-style-danger::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23ef4444'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .wp-block-quote.is-style-success::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2310b981'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'%3E%3C/path%3E%3C/svg%3E");
        }
        
        /* Ensure all styled blockquotes have consistent styling */
        .wp-block-quote.is-style-info,
        .wp-block-quote.is-style-warning,
        .wp-block-quote.is-style-danger,
        .wp-block-quote.is-style-success {
            padding: var(--spacing-lg) var(--spacing-xl) var(--spacing-lg) calc(var(--spacing-xl) + 60px);
            border-left: none;
            border-radius: 8px;
            margin: var(--spacing-xl) 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            backdrop-filter: blur(2px);
        }
        
        .wp-block-quote.is-style-info p:last-child,
        .wp-block-quote.is-style-warning p:last-child,
        .wp-block-quote.is-style-danger p:last-child,
        .wp-block-quote.is-style-success p:last-child {
            margin-bottom: 0;
        }
    </style>
    <?php
}

// Add editor styles
add_action('enqueue_block_editor_assets', __NAMESPACE__ . '\\add_editor_block_styles');

function add_editor_block_styles() {
    $custom_css = '
        /* Fix editor width issue and match frontend styles */
        .editor-styles-wrapper .wp-block-quote {
            max-width: 100%;
            position: relative;
            padding-left: calc(2rem + 60px) !important;
        }
        
        /* Ensure the quote block content is properly contained */
        .editor-styles-wrapper .wp-block[data-type="core/quote"] > .block-editor-block-list__block-edit > .wp-block-quote {
            margin-left: 0;
            margin-right: 0;
        }
        
        /* Default blockquote style */
        .editor-styles-wrapper .wp-block-quote {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-left: none;
            border-radius: 8px;
            padding: 1.5rem 2rem 1.5rem calc(2rem + 60px);
            color: #1e40af;
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(37, 99, 235, 0.1) 100%);
            border-left: none;
            padding: 1.5rem 2rem 1.5rem calc(2rem + 60px);
            color: #1e40af;
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-warning {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.2) 0%, rgba(245, 158, 11, 0.15) 100%);
            border-left: none;
            padding: 1.5rem 2rem 1.5rem calc(2rem + 60px);
            color: #c2410c;
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.15) 100%);
            border-left: none;
            padding: 1.5rem 2rem 1.5rem calc(2rem + 60px);
            color: #b91c1c;
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2) 0%, rgba(16, 185, 129, 0.15) 100%);
            border-left: none;
            padding: 1.5rem 2rem 1.5rem calc(2rem + 60px);
            color: #15803d;
        }
        
        /* Add icons in editor */
        .editor-styles-wrapper .wp-block-quote::before {
            content: "";
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.9;
        }
        
        .editor-styles-wrapper .wp-block-quote::before,
        .editor-styles-wrapper .wp-block-quote.is-style-info::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%233b82f6\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-warning::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23f59e0b\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-danger::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23ef4444\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'%3E%3C/path%3E%3C/svg%3E");
        }
        
        .editor-styles-wrapper .wp-block-quote.is-style-success::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%2310b981\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\'%3E%3C/path%3E%3C/svg%3E");
        }
        
        /* Make sure paragraph inside quote shows parent block toolbar */
        .editor-styles-wrapper .wp-block-quote p {
            margin-bottom: 0;
        }
        
        /* Visual hint for block selection */
        .editor-styles-wrapper .wp-block-quote.has-style-options {
            position: relative;
        }
        
        .editor-styles-wrapper .wp-block-quote.has-style-options::before {
            content: "💡 Select the quote block to change style";
            position: absolute;
            top: -20px;
            left: 0;
            font-size: 12px;
            color: #666;
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 3px;
            pointer-events: none;
            z-index: 1;
        }
    ';
    
    wp_add_inline_style('wp-edit-blocks', $custom_css);
}

// Add helpful message about blockquote styles
add_action('admin_footer', __NAMESPACE__ . '\\add_blockquote_help');

function add_blockquote_help() {
    if (!is_admin()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add tooltip to help users understand how to style blockquotes
        if (typeof wp !== 'undefined' && wp.data) {
            wp.domReady(function() {
                // Monitor for paragraph selection inside quotes
                wp.data.subscribe(function() {
                    const selectedBlock = wp.data.select('core/block-editor').getSelectedBlock();
                    if (selectedBlock && selectedBlock.name === 'core/paragraph') {
                        const parentBlocks = wp.data.select('core/block-editor').getBlockParents(selectedBlock.clientId);
                        parentBlocks.forEach(function(parentId) {
                            const parentBlock = wp.data.select('core/block-editor').getBlock(parentId);
                            if (parentBlock && parentBlock.name === 'core/quote') {
                                // Show a quick notification
                                const notices = document.querySelector('.components-snackbar-list');
                                if (notices && !document.querySelector('.quote-style-notice')) {
                                    const notice = document.createElement('div');
                                    notice.className = 'components-snackbar quote-style-notice';
                                    notice.innerHTML = '<div class="components-snackbar__content">💡 Tip: Click on the Quote block (not the paragraph) to access style options</div>';
                                    notices.appendChild(notice);
                                    setTimeout(() => notice.remove(), 3000);
                                }
                            }
                        });
                    }
                });
            });
        }
    });
    </script>
    <?php
}