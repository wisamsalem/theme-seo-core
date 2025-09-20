// Static save: render a shortcode wrapper. The theme/plugin front-end JS enhances it.
// Using a simple placeholder trail; real trail should be generated server-side or via shortcode.
export default function save() {
	// Frontend CSS will style .tsc-breadcrumbs; aria-current will be added by public.js if missing.
	return (
		<nav className="tsc-breadcrumbs" aria-label="Breadcrumbs">
			<ol>
				<li><a href="/">{/* Translators handled server-side */}Home</a></li>
				<li><span aria-current="page">Current Page</span></li>
			</ol>
		</nav>
	);
}

