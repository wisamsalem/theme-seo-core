<?php
namespace ThemeSeoCore\Frontend\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [tsc_breadcrumbs] – Accessible, schema.org BreadcrumbList
 *
 * Usage:
 *   echo do_shortcode('[tsc_breadcrumbs]');
 */
class BreadcrumbsShortcode {

	public function register(): void {
		add_shortcode( 'tsc_breadcrumbs', array( $this, 'handle' ) );
	}

	/**
	 * Build a breadcrumb trail for common contexts.
	 *
	 * @return string
	 */
	public function handle( $atts = array(), $content = '', $tag = '' ): string {
		$items = $this->trail();
		if ( empty( $items ) ) {
			return '';
		}

		// Schema.org BreadcrumbList markup
		$out  = '<nav class="tsc-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'theme-seo-core' ) . '" itemscope itemtype="https://schema.org/BreadcrumbList">';
		$out .= '<ol>';

		foreach ( $items as $i => $it ) {
			$pos = $i + 1;
			$title = $it['title'];
			$url   = $it['url'];

			$out .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
			if ( $url && $pos < count( $items ) ) {
				$out .= '<a itemprop="item" href="' . esc_url( $url ) . '"><span itemprop="name">' . esc_html( $title ) . '</span></a>';
			} else {
				$out .= '<span itemprop="name" aria-current="page">' . esc_html( $title ) . '</span>';
			}
			$out .= '<meta itemprop="position" content="' . (int) $pos . '" />';
			$out .= '</li>';
		}

		$out .= '</ol></nav>';

		return $out;
	}

	/**
	 * Create a trail array: [ [title, url], ... ]
	 *
	 * @return array<int,array{title:string,url:string}>
	 */
	protected function trail(): array {
		$trail = array();

		// Home
		$trail[] = array(
			'title' => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		if ( is_front_page() ) {
			return $trail; // just home
		}

		if ( is_home() ) {
			$trail[] = array(
				'title' => get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'theme-seo-core' ),
				'url'   => '',
			);
			return $trail;
		}

		if ( is_singular() ) {
			global $post;

			// For pages: include ancestors.
			if ( is_page() ) {
				$anc = array_reverse( get_post_ancestors( $post ) );
				foreach ( $anc as $aid ) {
					$trail[] = array(
						'title' => get_the_title( $aid ),
						'url'   => get_permalink( $aid ),
					);
				}
			}

			// For posts: add posts page.
			if ( is_single() && 'post' === get_post_type( $post ) && ( $blog = (int) get_option( 'page_for_posts' ) ) ) {
				$trail[] = array(
					'title' => get_the_title( $blog ),
					'url'   => get_permalink( $blog ),
				);
			}

			$trail[] = array(
				'title' => get_the_title( $post ),
				'url'   => '',
			);

			return $trail;
		}

		// Category/Tag/Taxonomy archives.
		if ( is_category() || is_tag() || is_tax() ) {
			$qo = get_queried_object();
			if ( $qo && isset( $qo->name ) ) {
				$trail[] = array(
					'title' => single_term_title( '', false ),
					'url'   => '',
				);
			}
			return $trail;
		}

		// CPT archives.
		if ( is_post_type_archive() ) {
			$trail[] = array(
				'title' => post_type_archive_title( '', false ),
				'url'   => '',
			);
			return $trail;
		}

		// Author archive.
		if ( is_author() ) {
			$trail[] = array(
				'title' => get_the_author(),
				'url'   => '',
			);
			return $trail;
		}

		// Search
		if ( is_search() ) {
			/* translators: %s: search query */
			$trail[] = array(
				'title' => sprintf( __( 'Search results for “%s”', 'theme-seo-core' ), get_search_query() ),
				'url'   => '',
			);
			return $trail;
		}

		// 404
		if ( is_404() ) {
			$trail[] = array(
				'title' => __( 'Not Found', 'theme-seo-core' ),
				'url'   => '',
			);
			return $trail;
		}

		// Date
		if ( is_date() ) {
			if ( is_year() ) {
				$trail[] = array( 'title' => get_query_var( 'year' ), 'url' => '' );
			} elseif ( is_month() ) {
				$trail[] = array( 'title' => single_month_title( ' ', false ), 'url' => '' );
			} elseif ( is_day() ) {
				$trail[] = array( 'title' => mysql2date( get_option( 'date_format' ), get_the_time( 'Y-m-d' ) ), 'url' => '' );
			}
			return $trail;
		}

		return $trail;
	}
}

