import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from '../block.json';
import './editor.scss';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { showH2, showH3, showH4 } = attributes;
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Heading Levels', 'docs-theme' ) }>
						<ToggleControl
							label={ __( 'Show H2', 'docs-theme' ) }
							checked={ showH2 }
							onChange={ ( value ) => setAttributes( { showH2: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show H3', 'docs-theme' ) }
							checked={ showH3 }
							onChange={ ( value ) => setAttributes( { showH3: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show H4', 'docs-theme' ) }
							checked={ showH4 }
							onChange={ ( value ) => setAttributes( { showH4: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<div className="docs-toc-placeholder">
						<h4>{ __( 'Table of Contents', 'docs-theme' ) }</h4>
						<p>{ __( 'Table of contents will be generated from page headings.', 'docs-theme' ) }</p>
						<ul>
							{ showH2 && <li>{ __( 'H2 headings', 'docs-theme' ) }</li> }
							{ showH3 && <li>{ __( 'H3 headings', 'docs-theme' ) }</li> }
							{ showH4 && <li>{ __( 'H4 headings', 'docs-theme' ) }</li> }
						</ul>
					</div>
				</div>
			</>
		);
	},

	save: () => null,
} );