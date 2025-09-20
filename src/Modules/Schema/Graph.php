<?php
namespace ThemeSeoCore\Modules\Schema;

use ThemeSeoCore\Modules\Schema\Types\WebSite;
use ThemeSeoCore\Modules\Schema\Types\Organization;
use ThemeSeoCore\Modules\Schema\Types\Article;
use ThemeSeoCore\Modules\Schema\Types\BreadcrumbList;
use ThemeSeoCore\Modules\Schema\Types\FAQPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composes and prints a JSON-LD @graph.
 */
class Graph {

	/**
	 * Build graph array and echo <script type="application/ld+json">…</script>.
	 */
	public function output(): void {
		$graph = $this->compose();

		if ( empty( $graph ) ) {
			return;
		}

		$json = wp_json_encode( [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		printf( "<script type=\"application/ld+json\">%s</script>\n", $json ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Compose graph entries based on context.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function compose(): array {
		$graph = [];

		// Always include WebSite (with SearchAction).
		$graph[] = WebSite::build();

		// Organization if site has a name/logo/site-icon (customizable via filters).
		$org = Organization::maybe_build();
		if ( $org ) {
			$graph[] = $org;
		}

		// Breadcrumbs (common on most pages except front page).
		if ( ! is_front_page() ) {
			$crumbs = BreadcrumbList::maybe_build();
			if ( $crumbs ) {
				$graph[] = $crumbs;
			}
		}

		// Article/BlogPosting for singular posts/pages.
		if ( is_singular() ) {
			$article = Article::maybe_build( get_queried_object_id() );
			if ( $article ) {
				$graph[] = $article;
			}
		}

		// FAQPage from block content or filter.
		$faq = FAQPage::maybe_build();
		if ( $faq ) {
			$graph[] = $faq;
		}

		/**
		 * Filter the graph before rendering.
		 *
		 * @param array $graph
		 */
		return (array) apply_filters( 'tsc/schema/graph', $graph );
	}
}

