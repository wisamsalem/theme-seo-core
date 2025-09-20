<?php
namespace ThemeSeoCore\Modules\Sitemaps\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides <url> entries for author archives that have published posts.
 */
class AuthorsProvider {

	public function page( int $page ): array {
		$per_page = (int) apply_filters( 'tsc/sitemaps/authors/per_page', 1000 );

		$users = get_users( array(
			'who'   => 'authors',
			'fields'=> array( 'ID' ),
			'number'=> -1,
		) );

		$user_ids = array_map( fn( $u ) => (int) $u->ID, $users );
		$total = count( $user_ids );
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$offset = ( $page - 1 ) * $per_page;
		$ids = array_slice( $user_ids, $offset, $per_page );

		$entries = array();
		foreach ( $ids as $uid ) {
			// Only include authors with at least one published post.
			$c = count_user_posts( $uid, 'post', true );
			if ( $c <= 0 ) {
				continue;
			}
			$entries[] = array(
				'loc'        => get_author_posts_url( $uid ),
				'changefreq' => 'weekly',
				'priority'   => '0.2',
			);
		}

		return compact( 'entries', 'total_pages', 'page' ) + array( 'current_page' => $page );
	}
}

