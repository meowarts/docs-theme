/**
 * Page Meta Fields Block Editor Integration
 * Handles Page Subtitle and Page Emoticon
 */

( function( wp ) {
    const { __ } = wp.i18n;
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { TextareaControl, TextControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useEntityProp } = wp.coreData;
    const { createElement, Fragment } = wp.element;

    const PageMetaPanel = () => {
        // Only show on pages
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );

        if ( postType !== 'page' ) {
            return null;
        }

        // Get and set the meta
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
        const subtitle = meta?._docs_theme_subtitle || '';
        const emoticon = meta?._docs_theme_emoticon || '';

        const updateSubtitle = ( value ) => {
            setMeta( { ...meta, _docs_theme_subtitle: value } );
        };

        const updateEmoticon = ( value ) => {
            // Limit to 2 characters
            const trimmedValue = value.substring(0, 2);
            setMeta( { ...meta, _docs_theme_emoticon: trimmedValue } );
        };

        return createElement(
            Fragment,
            {},
            // Subtitle Panel
            createElement(
                PluginDocumentSettingPanel,
                {
                    name: 'page-subtitle-panel',
                    title: __( 'Page Subtitle', 'docs-theme' ),
                    className: 'docs-theme-page-subtitle-panel'
                },
                createElement(
                    TextareaControl,
                    {
                        label: __( 'Subtitle', 'docs-theme' ),
                        value: subtitle,
                        onChange: updateSubtitle,
                        help: __( 'Appears below the page title', 'docs-theme' ),
                        rows: 4
                    }
                )
            ),
            // Emoticon Panel
            createElement(
                PluginDocumentSettingPanel,
                {
                    name: 'page-emoticon-panel',
                    title: __( 'Page Emoticon', 'docs-theme' ),
                    className: 'docs-theme-page-emoticon-panel'
                },
                createElement(
                    TextControl,
                    {
                        label: __( 'Emoticon', 'docs-theme' ),
                        value: emoticon,
                        onChange: updateEmoticon,
                        help: __( 'Single emoji or icon (e.g. 📚, 🔧, 💡)', 'docs-theme' ),
                        maxLength: 2,
                        placeholder: '😊'
                    }
                )
            )
        );
    };

    registerPlugin( 'docs-theme-page-meta', {
        render: PageMetaPanel,
        icon: 'edit',
    } );

} )( window.wp );