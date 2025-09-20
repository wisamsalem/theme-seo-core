<?php
namespace ThemeSeoCore\Modules\Breadcrumbs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TrailBuilder
 *
 * Builds a breadcrumb trail array for the current request.
 * Return format: [ ['title' => string, 'url' => string], ... ]
 */
class TrailBuilder {

	/**
	 * Build the trail.
	 *
	 * @param array{
	 *   home_label?: string,
	 *   include_posts_page?: bool,
	 *   include_cpt_archive?: bool
	 * } $args
	 * @return array<int,array{title:string,url:string}>
	 */
	public function build( array $args = array() ): array {
		$home_label = $args['home_label'] ?? get_bloginfo( 'name' );
		$inc_posts  = isset( $args['include_posts_page'] ) ? (bool) $args['include_posts_page'] : true;
		$inc_cpt    = isset( $args['include_cpt_archive'] ) ? (bool) $args['include_cpt_archive'] : true;

		$trail   = array();
		$trail[] = array( 'title' => $home_label, 'url' => home_url( '/' ) );

		// Front page — just Home.
		if ( is_front_page() ) {
			return $trail;
		}

		// Blog home (posts page)
		if ( is_home() ) {
			$title = get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'theme-seo-core' );
			$trail[] = array( 'title' => $title, 'url' => '' );
			return $trail;
		}

		// Singular content
		if ( is_singular() ) {
			global $post;

			// CPT archive link (if public and has archive)
			if ( $inc_cpt && ! is_page() && post_type_supports( get_post_type( $post ), 'title' ) ) {
				$pt = get_post_type_object( get_post_type( $post ) );
				if ( $pt && $pt->has_archive ) {
					$trail[] = array(
						'title' => $pt->labels->name ?: ucfirst( $pt->name ),
						'url'   => get_post_type_archive_link( $pt->name ),
					);
				}
			}

			// For posts, include the posts page in trail if configured.
			if ( 'post' === get_post_type( $post ) && $inc_posts ) {
				$blog = (int) get_option( 'page_for_posts' );
				if ( $blog ) {
					$trail[] = array( 'title' => get_the_title( $blog ), 'url' => get_permalink( $blog ) );
				}
			}

			// Page ancestors.
			if ( is_page() ) {
				$anc = array_reverse( get_post_ancestors( $post ) );
				foreach ( $anc as $aid ) {
					$trail[] = array( 'title' => get_the_title( $aid ), 'url' => get_permalink( $aid ) );
				}
			}

			// Current post/page.
			$trail[] = array( 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) );
			return $trail;
		}

		// Category/Tag/Taxonomy archives (with parent terms)
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				$parents = array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
				foreach ( $parents as $pid ) {
					$trail[] = array(
						'title' => get_term_field( 'name', $pid, $term->taxonomy ),
						'url'   => get_term_link( (int) $pid, $term->taxonomy ),
					);
				}
				$trail[] = array( 'title' => single_term_title( '', false ), 'url' => get_term_link( $term ) );
			}
			return $trail;
		}

		// Post type archives
		if ( is_post_type_archive() ) {
			$pt = get_post_type();
			$trail[] = array(
				'title' => post_type_archive_title( '', false ),
				'url'   => get_post_type_archive_link( $pt ),
			);
			return $trail;
		}

		// Author archives
		if ( is_author() ) {
			$trail[] = array( 'title' => get_the_author(), 'url' => '' );
			return $trail;
		}

		// Date archives
		if ( is_day() ) {
			$y = get_query_var( 'year' );
			$m = get_query_var( 'monthnum' );
			$trail[] = array( 'title' => (string) $y, 'url' => get_year_link( $y ) );
			$trail[] = array( 'title' => single_month_title( ' ', false ), 'url' => get_month_link( $y, $m ) );
			$trail[] = array( 'title' => get_query_var( 'day' ), 'url' => '' );
			return $trail;
		}
		if ( is_month() ) {
			$y = get_query_var( 'year' );
			$trail[] = array( 'title' => (string) $y, 'url' => get_year_link( $y ) );
			$trail[] = array( 'title' => single_month_title( ' ', false ), 'url' => '' );
			return $trail;
		}
		if ( is_year() ) {
			$trail[] = array( 'title' => get_query_var( 'year' ), 'url' => '' );
			return $trail;
		}

		// Search
		if ( is_search() ) {
			$trail[] = array(
				'title' => sprintf( __( 'Search: %s', 'theme-seo-core' ), get_search_query() ),
				'url'   => '',
			);
			return $trail;
		}

		// 404
		if ( is_404() ) {
			$trail[] = array( 'title' => __( 'Not Found', 'theme-seo-core' ), 'url' => '' );
			return $trail;
		}

		// Fallback: just Home (unlikely)
		return $trail;
	}
}

