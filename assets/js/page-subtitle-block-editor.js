/**
 * Page Subtitle Block Editor Integration
 */

( function( wp ) {
    const { __ } = wp.i18n;
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { TextareaControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { useEntityProp } = wp.coreData;

    const PageSubtitlePanel = () => {
        // Only show on pages
        const postType = useSelect(
            ( select ) => select( 'core/editor' ).getCurrentPostType(),
            []
        );

        if ( postType !== 'page' ) {
            return null;
        }

        // Get and set the subtitle meta
        const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
        const subtitle = meta?._docs_theme_subtitle || '';

        const updateSubtitle = ( value ) => {
            setMeta( { ...meta, _docs_theme_subtitle: value } );
        };

        return (
            <PluginDocumentSettingPanel
                name="page-subtitle-panel"
                title={ __( 'Page Subtitle', 'docs-theme' ) }
                className="docs-theme-page-subtitle-panel"
            >
                <TextareaControl
                    label={ __( 'Subtitle', 'docs-theme' ) }
                    value={ subtitle }
                    onChange={ updateSubtitle }
                    help={ __( 'Appears below the page title', 'docs-theme' ) }
                    rows={ 4 }
                />
            </PluginDocumentSettingPanel>
        );
    };

    registerPlugin( 'docs-theme-page-subtitle', {
        render: PageSubtitlePanel,
        icon: 'edit',
    } );

} )( window.wp );