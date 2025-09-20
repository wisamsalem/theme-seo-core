<?php
namespace ThemeSeoCore\Modules\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BreadcrumbList entity.
 */
class BreadcrumbList {

	/**
	 * Build BreadcrumbList for current context if there is a logical trail.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function maybe_build() {
		$items = self::trail();
		if ( count( $items ) <= 1 ) {
			return null;
		}

		$position = 1;
		$list = array_map( function( $item ) use ( &$position ) {
			return [
				'@type'    => 'ListItem',
				'position' => $position++,
				'item'     => [
					'@id'  => $item['url'],
					'name' => $item['title'],
				],
			];
		}, $items );

		return [
			'@type' => 'BreadcrumbList',
			'@id'   => home_url( '/' ) . '#breadcrumbs',
			'itemListElement' => $list,
		];
	}

	/**
	 * Generate a simple breadcrumb trail matching the shortcode logic.
	 *
	 * @return array<int,array{title:string,url:string}>
	 */
	protected static function trail(): array {
		$trail = [];
		$trail[] = [ 'title' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ];

		if ( is_front_page() ) {
			return $trail;
		}

		if ( is_home() ) {
			$trail[] = [ 'title' => get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'theme-seo-core' ), 'url' => '' ];
			return $trail;
		}

		if ( is_singular() ) {
			global $post;

			if ( is_page() ) {
				$anc = array_reverse( get_post_ancestors( $post ) );
				foreach ( $anc as $aid ) {
					$trail[] = [ 'title' => get_the_title( $aid ), 'url' => get_permalink( $aid ) ];
				}
			}

			if ( is_single() && 'post' === get_post_type( $post ) && ( $blog = (int) get_option( 'page_for_posts' ) ) ) {
				$trail[] = [ 'title' => get_the_title( $blog ), 'url' => get_permalink( $blog ) ];
			}

			$trail[] = [ 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) ];
			return $trail;
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$trail[] = [ 'title' => single_term_title( '', false ), 'url' => get_term_link( get_queried_object() ) ];
			return $trail;
		}

		if ( is_post_type_archive() ) {
			$trail[] = [ 'title' => post_type_archive_title( '', false ), 'url' => get_post_type_archive_link( get_post_type() ) ];
			return $trail;
		}

		if ( is_author() ) {
			$trail[] = [ 'title' => get_the_author(), 'url' => '' ];
			return $trail;
		}

		if ( is_search() ) {
			$trail[] = [ 'title' => sprintf( __( 'Search results for “%s”', 'theme-seo-core' ), get_search_query() ), 'url' => '' ];
			return $trail;
		}

		if ( is_404() ) {
			$trail[] = [ 'title' => __( 'Not Found', 'theme-seo-core' ), 'url' => '' ];
			return $trail;
		}

		return $trail;
	}
}

