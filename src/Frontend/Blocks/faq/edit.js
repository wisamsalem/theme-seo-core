import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	Button,
	TextControl,
	ToolbarGroup,
	ToolbarButton
} from '@wordpress/components';
import {
	useBlockProps,
	RichText,
	BlockControls,
	InspectorControls
} from '@wordpress/block-editor';
import { useCallback } from '@wordpress/element';
import save from './save';

const emptyItem = () => ({ q: '', a: '' });

registerBlockType('tsc/faq', {
	title: __('FAQ (TSC)', 'theme-seo-core'),
	description: __('Expandable FAQ section with JSON-LD schema output.', 'theme-seo-core'),
	attributes: {
		items: { type: 'array', default: [] },
		title: { type: 'string', default: '' }
	},
	edit({ attributes, setAttributes }) {
		const { items = [], title = '' } = attributes;
		const props = useBlockProps({ className: 'tsc-faq' });

		const addItem = useCallback(() => {
			setAttributes({ items: [...items, emptyItem()] });
		}, [items]);

		const removeItem = useCallback((idx) => {
			const next = [...items];
			next.splice(idx, 1);
			setAttributes({ items: next });
		}, [items]);

		const updateItem = useCallback((idx, patch) => {
			const next = [...items];
			next[idx] = { ...next[idx], ...patch };
			setAttributes({ items: next });
		}, [items]);

		return (
			<div {...props}>
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton icon="plus" label={__('Add FAQ', 'theme-seo-core')} onClick={addItem} />
					</ToolbarGroup>
				</BlockControls>

				<InspectorControls>
					<PanelBody title={__('Settings', 'theme-seo-core')} initialOpen>
						<TextControl
							label={__('Section Title (optional)', 'theme-seo-core')}
							value={title}
							onChange={(v) => setAttributes({ title: v })}
						/>
						<p className="components-help">
							{__('Add common questions and answers. JSON-LD markup is generated on save.', 'theme-seo-core')}
						</p>
					</PanelBody>
				</InspectorControls>

				{title ? (
					<RichText
						tagName="h3"
						value={title}
						onChange={(v) => setAttributes({ title: v })}
						placeholder={__('FAQ', 'theme-seo-core')}
						allowedFormats={[]}
					/>
				) : (
					<RichText
						tagName="h3"
						value={title}
						onChange={(v) => setAttributes({ title: v })}
						placeholder={__('FAQ', 'theme-seo-core')}
						allowedFormats={[]}
					/>
				)}

				<div className="tsc-faq">
					{items.length === 0 && (
						<p className="components-help">{__('No items yet. Click + to add your first Q&A.', 'theme-seo-core')}</p>
					)}

					{items.map((item, idx) => (
						<div key={idx} className="tsc-faq-item is-open">
							<div style={{ display: 'flex', gap: '8px', alignItems: 'flex-start' }}>
								<RichText
									tagName="button"
									allowedFormats={[]}
									className="tsc-faq-q"
									value={item.q}
									onChange={(v) => updateItem(idx, { q: v })}
									placeholder={__('Question…', 'theme-seo-core')}
								/>
								<Button
									isDestructive
									onClick={() => removeItem(idx)}
									variant="secondary"
									icon="trash"
									label={__('Remove', 'theme-seo-core')}
								/>
							</div>
							<RichText
								tagName="div"
								allowedFormats={['core/bold', 'core/italic', 'core/link']}
								className="tsc-faq-a"
								value={item.a}
								onChange={(v) => updateItem(idx, { a: v })}
								placeholder={__('Answer…', 'theme-seo-core')}
							/>
						</div>
					))}
				</div>

				<div style={{ marginTop: 12 }}>
					<Button variant="primary" onClick={addItem} icon="plus">
						{__('Add FAQ', 'theme-seo-core')}
					</Button>
				</div>
			</div>
		);
	},
	save
});

