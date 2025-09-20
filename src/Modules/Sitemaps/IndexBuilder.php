<?php
namespace ThemeSeoCore\Modules\Sitemaps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the <sitemapindex> entries.
 */
class IndexBuilder {

	/**
	 * @return array<int,array{loc:string,lastmod:string}>
	 */
	public function index(): array {
		$base = home_url( '/' );

		$groups = array( 'posts', 'taxonomies', 'authors', 'images' );
		$out = array();

		foreach ( $groups as $g ) {
			$pages = $this->pages_for( $g );
			for ( $i = 1; $i <= $pages; $i++ ) {
				$out[] = array(
					'loc'     => esc_url( trailingslashit( $base ) . "sitemap-{$g}-{$i}.xml" ),
					'lastmod' => $this->lastmod_for( $g ),
				);
			}
		}

		/**
		 * Filter final sitemapindex entries.
		 *
		 * @param array $out
		 */
		return (array) apply_filters( 'tsc/sitemaps/index', $out );
	}

	protected function pages_for( string $group ): int {
		$per_page = (int) apply_filters( "tsc/sitemaps/{$group}/per_page", 1000 );
		switch ( $group ) {
			case 'posts':
				$total = (int) wp_count_posts( 'any' )->publish;
				break;
			case 'taxonomies':
				$total = 0;
				foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $tax ) {
					$total += (int) wp_count_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) );
				}
				break;
			case 'authors':
				$users = count_users();
				$total = (int) ( $users['avail_roles']['author'] ?? 0 ) + (int) ( $users['avail_roles']['administrator'] ?? 0 ) + (int) ( $users['avail_roles']['editor'] ?? 0 ) + (int) ( $users['avail_roles']['contributor'] ?? 0 );
				break;
			case 'images':
				global $wpdb;
				$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' AND post_status = 'inherit'" );
				break;
			default:
				$total = 0;
		}
		return max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
	}

	protected function lastmod_for( string $group ): string {
		// Best-effort timestamps by group (ISO 8601).
		switch ( $group ) {
			case 'posts':
				$q = new \WP_Query( array(
					'post_type'      => 'any',
					'post_status'    => 'publish',
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				) );
				if ( $q->have_posts() ) {
					$q->the_post();
					$t = get_post_modified_time( 'c', true );
					wp_reset_postdata();
					return $t;
				}
				break;
			case 'taxonomies':
				// No cheap reliable way; fallback to now.
				return gmdate( 'c' );
			case 'authors':
				return gmdate( 'c' );
			case 'images':
				return gmdate( 'c' );
		}
		return gmdate( 'c' );
	}
}

