import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Disabled, Placeholder } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import save from './save';

registerBlockType('tsc/breadcrumbs', {
	title: __('Breadcrumbs (TSC)', 'theme-seo-core'),
	description: __('Accessible breadcrumb trail with schema.org markup.', 'theme-seo-core'),
	edit() {
		// No attributes—front end uses shortcode markup/styles.
		useEffect(() => {}, []);
		return (
			<Disabled>
				<Placeholder
					label={__('Breadcrumbs', 'theme-seo-core')}
					instructions={__('This block outputs a breadcrumb trail on the front-end.', 'theme-seo-core')}
				>
					<nav className="tsc-breadcrumbs">
						<ol>
							<li><a href="#">{__('Home', 'theme-seo-core')}</a></li>
							<li><span aria-current="page">{__('Current Page', 'theme-seo-core')}</span></li>
						</ol>
					</nav>
				</Placeholder>
			</Disabled>
		);
	},
	save
});

