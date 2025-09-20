<?php
namespace ThemeSeoCore\Modules\Sitemaps\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides <url> entries for published posts/pages/CPTs.
 */
class PostsProvider {

	/**
	 * @param int $page
	 * @return array{entries:array<int,array<string,mixed>>, total_pages:int, current_page:int}
	 */
	public function page( int $page ): array {
		$per_page = (int) apply_filters( 'tsc/sitemaps/posts/per_page', 1000 );

		$q = new \WP_Query( array(
			'post_type'      => $this->post_types(),
			'post_status'    => 'publish',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'no_found_rows'  => false,
			'fields'         => 'ids',
		) );

		$total_pages = (int) max( 1, $q->max_num_pages );
		$entries = array();

		foreach ( $q->posts as $post_id ) {
			$loc = get_permalink( $post_id );
			$lastmod = get_post_modified_time( 'c', true, $post_id );
			$entries[] = array(
				'loc'        => $loc,
				'lastmod'    => $lastmod,
				'changefreq' => 'weekly',
				'priority'   => '0.6',
			);
		}
		wp_reset_postdata();

		return compact( 'entries', 'total_pages', 'page' ) + array( 'current_page' => $page );
	}

	/**
	 * @return array<int,string>
	 */
	protected function post_types(): array {
		$pts = get_post_types( array( 'public' => true ), 'names' );
		unset( $pts['attachment'] );
		return array_values( (array) apply_filters( 'tsc/sitemaps/posts/post_types', $pts ) );
	}
}

