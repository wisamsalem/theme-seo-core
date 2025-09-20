<?php
namespace ThemeSeoCore\Frontend;

use ThemeSeoCore\Support\Html;
use ThemeSeoCore\Support\Url;
use ThemeSeoCore\Support\Conditionals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central <head> render pipeline for simple SEO tags:
 * - Canonical URL
 * - Robots meta (global + per-post overrides)
 *
 * More advanced OG/Schema should live in their dedicated modules,
 * but this gives sensible defaults even with minimal modules enabled.
 */
class Head {

	/** @var Conditionals */
	protected $cond;

	public function __construct( ?Conditionals $cond = null ) {
		$this->cond = $cond ?: new Conditionals();
	}

	public function register(): void {
		add_action( 'wp_head', array( $this, 'render_canonical' ), 5 );
		add_action( 'wp_head', array( $this, 'render_robots' ),    6 );
	}

	/**
	 * Output canonical link for singular, home, archives, etc.
	 * Respects a per-post override stored by the metabox.
	 */
	public function render_canonical(): void {
		$link = '';

		if ( is_singular() ) {
			global $post;
			if ( $post instanceof \WP_Post ) {
				$custom = get_post_meta( $post->ID, \ThemeSeoCore\Admin\MetaBox::META_CANONICAL, true );
				if ( $custom ) {
					$link = $custom;
				} else {
					$link = get_permalink( $post );
				}
			}
		} elseif ( is_home() && ! is_front_page() ) {
			$link = get_permalink( get_option( 'page_for_posts' ) );
		} elseif ( is_front_page() ) {
			$link = home_url( '/' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
		} elseif ( is_post_type_archive() ) {
			$link = get_post_type_archive_link( get_post_type() );
		} elseif ( is_author() ) {
			$link = get_author_posts_url( get_query_var( 'author' ) );
		} elseif ( is_search() ) {
			// We normally avoid canonical for search; leave empty.
			$link = '';
		} elseif ( is_date() ) {
			$link = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
		}

		// Add pagination canonical if needed.
		if ( $link && ( is_paged() || get_query_var( 'paged' ) ) ) {
			$paged = (int) max( 1, get_query_var( 'paged' ) );
			if ( $paged > 1 ) {
				$link = trailingslashit( $link ) . user_trailingslashit( 'page/' . $paged );
			}
		}

		$link = apply_filters( 'tsc/head/canonical', $link );

		if ( $link ) {
			echo Html::void( 'link', array( 'rel' => 'canonical', 'href' => Url::canonicalize( $link ) ) ) . "\n";
		}
	}

	/**
	 * Output robots meta based on settings and per-post flags.
	 */
	public function render_robots(): void {
		$settings = (array) get_option( 'tsc_settings', array() );

		$directives = array();

		// Global: noindex for search results (if enabled).
		if ( ! empty( $settings['noindex_search'] ) && is_search() ) {
			$directives[] = 'noindex';
		}

		// Singular overrides via post meta.
		if ( is_singular() ) {
			global $post;
			if ( $post instanceof \WP_Post ) {
				if ( get_post_meta( $post->ID, \ThemeSeoCore\Admin\MetaBox::META_NOINDEX, true ) ) {
					$directives[] = 'noindex';
				}
				if ( get_post_meta( $post->ID, \ThemeSeoCore\Admin\MetaBox::META_NOFOLLOW, true ) ) {
					$directives[] = 'nofollow';
				}
			}
		}

		// Default to index,follow if no directives applied.
		$directives = apply_filters( 'tsc/head/robots', array_unique( $directives ) );
		if ( empty( $directives ) ) {
			$directives = array( 'index', 'follow' );
		}

		echo Html::tag( 'meta', '', array( 'name' => 'robots', 'content' => implode( ',', $directives ) ) ) . "\n";
	}
}

