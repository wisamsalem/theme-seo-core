import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Front-end render:
 * - Outputs accordion markup expected by assets/public/css + public.js.
 * - Adds JSON-LD FAQPage structured data (inline <script type="application/ld+json">).
 */
export default function save({ attributes }) {
	const { items = [], title = '' } = attributes;
	const props = useBlockProps.save({ className: 'tsc-faq' });

	// Build JSON-LD
	const mainEntity = items
		.filter((it) => (it.q || '').trim() && (it.a || '').trim())
		.map((it) => ({
			'@type': 'Question',
			'name': it.q.replace(/<[^>]+>/g, ''),
			'acceptedAnswer': {
				'@type': 'Answer',
				'text': it.a
			}
		}));

	const jsonLd = {
		'@context': 'https://schema.org',
		'@type': 'FAQPage',
		'mainEntity': mainEntity
	};

	return (
		<section {...props}>
			{title ? <RichText.Content tagName="h3" value={title} /> : null}

			{items.map((it, idx) => {
				const qId = `tsc-faq-q-${idx}`;
				const aId = `tsc-faq-a-${idx}`;
				return (
					<div className="tsc-faq-item" key={idx}>
						<button className="tsc-faq-q" id={qId} aria-expanded="false" aria-controls={aId}>
							<RichText.Content value={it.q} />
						</button>
						<div className="tsc-faq-a" id={aId} role="region" aria-labelledby={qId} hidden>
							<RichText.Content tagName="div" value={it.a} />
						</div>
					</div>
				);
			})}

			{mainEntity.length > 0 && (
				<script type="application/ld+json">
					{JSON.stringify(jsonLd)}
				</script>
			)}
		</section>
	);
}

