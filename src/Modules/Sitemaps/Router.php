<?php
namespace ThemeSeoCore\Modules\Sitemaps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds pretty rewrite rules + tags for sitemaps.
 */
class Router {

	public function add_rewrites(): void {
		// Index: /sitemap.xml
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?tsc_sitemap=index', 'top' );

		// Pretty paged sitemaps: /sitemap-<type>-<page>.xml
		$types = '(posts|taxonomies|authors|images)';
		add_rewrite_rule( '^sitemap-(' . $types . ')-([0-9]+)\.xml$', 'index.php?tsc_sitemap=$matches[1]&tsc_page=$matches[2]', 'top' );

		// Also allow unpaged alias: /sitemap-<type>.xml (defaults to page 1)
		add_rewrite_rule( '^sitemap-(' . $types . ')\.xml$', 'index.php?tsc_sitemap=$matches[1]&tsc_page=1', 'top' );

		// Stylesheet helper (optional): /?tsc_sitemap=xsl
		// Query vars registered by Module::query_vars().
	}
}

