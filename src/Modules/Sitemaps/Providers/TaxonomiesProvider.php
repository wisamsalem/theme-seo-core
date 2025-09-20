<?php
namespace ThemeSeoCore\Modules\Sitemaps\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides <url> entries for public taxonomy terms.
 */
class TaxonomiesProvider {

	public function page( int $page ): array {
		$per_page = (int) apply_filters( 'tsc/sitemaps/taxonomies/per_page', 2000 );

		$taxes = get_taxonomies( array( 'public' => true ), 'names' );
		$all_terms = array();

		foreach ( $taxes as $tax ) {
			$terms = get_terms( array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
				'fields'     => 'ids',
				'number'     => 0,
			) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				$all_terms = array_merge( $all_terms, $terms );
			}
		}

		$total = count( $all_terms );
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$offset = ( $page - 1 ) * $per_page;
		$ids = array_slice( $all_terms, $offset, $per_page );

		$entries = array();
		foreach ( $ids as $term_id ) {
			$loc = get_term_link( (int) $term_id );
			if ( is_wp_error( $loc ) ) {
				continue;
			}
			$entries[] = array(
				'loc'        => $loc,
				'changefreq' => 'weekly',
				'priority'   => '0.3',
			);
		}

		return compact( 'entries', 'total_pages', 'page' ) + array( 'current_page' => $page );
	}
}

