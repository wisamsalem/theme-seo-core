<?php
namespace ThemeSeoCore\Modules\Titles;

use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TitleGenerator
 *
 * Safe even if called before the main query is set. Detects query readiness
 * and returns conservative fallbacks until WordPress has run the query.
 *
 * Includes a STATIC shim `filter_document_title()` so callbacks registered like
 * [ TitleGenerator::class, 'filter_document_title' ] work.
 */
class TitleGenerator {

	const META_KEY = '_theme_seo_title';

	/** @var Options */
	protected $options;

	/** @var array<string,string> */
	protected $patterns;

	public function __construct( Options $options ) {
		$this->options  = $options;
		$this->patterns = $this->load_patterns();
	}

	/**
	 * STATIC shim for legacy/static filter registrations.
	 *
	 * @param string $title
	 * @return string
	 */
	public static function filter_document_title( $title = '' ): string {
		$options = new Options( 'tsc_settings' );
		$gen     = new self( $options );
		$out     = $gen->generate();
		return ( $out !== '' ) ? $out : (string) $title;
	}

	/**
	 * Produce the final <title>…</title>.
	 * Safe if called too early; will return site name or minimal fallbacks.
	 */
	public function generate(): string {
		// If query not ready yet, avoid conditional tags.
		if ( ! $this->query_ready() ) {
			// In admin, or early in bootstrap, keep it simple to avoid notices.
			return $this->sitename();
		}

		// 1) Per-post override for singular.
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$custom = get_post_meta( $post_id, self::META_KEY, true );
				if ( is_string( $custom ) && $custom !== '' ) {
					return $this->apply_suffix_if_needed( wp_strip_all_tags( $custom ) );
				}
			}
		}

		// 2) Pattern-driven.
		$pattern = $this->pattern_for_context();
		$title   = $this->render_pattern( $pattern );
		if ( $title !== '' ) {
			return $title;
		}

		// 3) Sane fallbacks.
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				return $this->compose( get_the_title( $post_id ), $this->sitename() );
			}
		}

		if ( is_front_page() ) {
			$site = $this->sitename();
			$tag  = $this->tagline();
			return $tag ? $this->compose( $site, $tag ) : $site;
		}

		if ( is_home() ) {
			$blog_page = (int) get_option( 'page_for_posts' );
			$base      = $blog_page ? get_the_title( $blog_page ) : __( 'Blog', 'theme-seo-core' );
			return $this->compose( $base, $this->sitename() );
		}

		if ( is_search() ) {
			return $this->compose(
				sprintf( __( 'Search results for “%s”', 'theme-seo-core' ), get_search_query() ),
				$this->sitename()
			);
		}

		if ( is_404() ) {
			return $this->compose( __( 'Page not found', 'theme-seo-core' ), $this->sitename() );
		}

		if ( is_category() || is_tag() || is_tax() || is_post_type_archive() || is_date() || is_author() ) {
			return $this->compose( $this->archive_title(), $this->sitename() );
		}

		return $this->sitename();
	}

	/* =========================
	 * Pattern engine / context
	 * ========================= */

	protected function context_key(): string {
		if ( ! $this->query_ready() ) {
			return 'home'; // safe minimal key
		}
		if ( is_front_page() ) {
			return 'home';
		}
		if ( is_singular( 'page' ) ) {
			return 'page';
		}
		if ( is_singular() ) {
			return 'post';
		}
		if ( is_search() ) {
			return 'search';
		}
		if ( is_404() ) {
			return '404';
		}
		if ( is_author() ) {
			return 'author';
		}
		return 'archive';
	}

	protected function pattern_for_context(): string {
		$key = $this->context_key();
		return isset( $this->patterns[ $key ] ) ? (string) $this->patterns[ $key ] : '';
	}

	protected function render_pattern( string $pattern ): string {
		if ( $pattern === '' ) {
			return '';
		}

		$sep = $this->separator();

		$map = [
			'%%sep%%'           => $sep,
			'%%sitename%%'      => $this->sitename(),
			'%%tagline%%'       => $this->tagline(),
			'%%title%%'         => $this->current_title_token(),
			'%%archive_title%%' => $this->archive_title(),
			'%%search_query%%'  => $this->query_ready() ? (string) get_search_query() : '',
			'%%not_found%%'     => __( 'Page not found', 'theme-seo-core' ),
			'%%author%%'        => $this->author_name(),
			'%%page%%'          => $this->page_suffix(),
		];

		$out = strtr( $pattern, $map );
		$out = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( (string) $out ) ) ) ?: '';

		// If the pattern doesn't include %%sitename%% and we're on singular, suffix with site name.
		if ( $this->query_ready() && is_singular() && strpos( $pattern, '%%sitename%%' ) === false && $out !== '' ) {
			$out = $this->compose( $out, $this->sitename() );
		}

		return $out;
	}

	protected function apply_suffix_if_needed( string $value ): string {
		$site = $this->sitename();
		if ( $site && stripos( $value, $site ) === false ) {
			return $this->compose( $value, $site );
		}
		return $value;
	}

	/* =========================
	 * Helpers / safe tokens
	 * ========================= */

	protected function query_ready(): bool {
		// Any of these means conditional tags are safe.
		if ( did_action( 'wp' ) || did_action( 'template_redirect' ) || did_action( 'wp_head' ) ) {
			return true;
		}
		// In admin screens we avoid conditionals; treat as not ready.
		return false;
	}

	protected function separator(): string {
		$cfg = $this->options ? $this->options->all() : [];
		return ( isset( $cfg['separator'] ) && $cfg['separator'] !== '' ) ? (string) $cfg['separator'] : '–';
	}

	protected function sitename(): string {
		return (string) get_bloginfo( 'name' );
	}

	protected function tagline(): string {
		return (string) get_bloginfo( 'description' );
	}

	protected function current_title_token(): string {
		if ( ! $this->query_ready() ) {
			return $this->sitename();
		}

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			return $post_id ? (string) get_the_title( $post_id ) : $this->sitename();
		}
		if ( is_front_page() ) {
			return $this->sitename();
		}
		if ( is_home() ) {
			$blog_page = (int) get_option( 'page_for_posts' );
			return $blog_page ? (string) get_the_title( $blog_page ) : __( 'Blog', 'theme-seo-core' );
		}
		if ( is_search() ) {
			return sprintf( __( 'Search results for “%s”', 'theme-seo-core' ), get_search_query() );
		}
		if ( is_404() ) {
			return __( 'Page not found', 'theme-seo-core' );
		}
		if ( is_author() ) {
			return $this->author_name();
		}
		if ( is_category() || is_tag() || is_tax() || is_post_type_archive() || is_date() ) {
			return $this->archive_title();
		}
		return $this->sitename();
	}

	protected function archive_title(): string {
		if ( ! $this->query_ready() ) {
			return '';
		}
		$title = get_the_archive_title();
		$title = preg_replace( '/^\s*(Category|Tag|Archives|Author):\s*/i', '', (string) $title );
		return trim( (string) $title );
	}

	protected function author_name(): string {
		if ( ! $this->query_ready() ) {
			return '';
		}
		$author = get_queried_object();
		return ( $author && isset( $author->display_name ) ) ? (string) $author->display_name : __( 'Author', 'theme-seo-core' );
	}

	protected function page_suffix(): string {
		if ( ! $this->query_ready() ) {
			return '';
		}
		$paged = get_query_var( 'paged' );
		return ( $paged && (int) $paged >= 2 ) ? sprintf( __( 'Page %d', 'theme-seo-core' ), (int) $paged ) : '';
	}

	protected function compose( string $left, string $right ): string {
		$sep = $this->separator();
		$left  = trim( $left );
		$right = trim( $right );
		if ( $left === '' ) {
			return $right;
		}
		if ( $right === '' ) {
			return $left;
		}
		return "{$left} {$sep} {$right}";
	}

	protected function load_patterns(): array {
		$defaults = apply_filters( 'tsc/titles/patterns/defaults', [
			'home'    => '%%sitename%% %%sep%% %%tagline%%',
			'post'    => '%%title%% %%sep%% %%sitename%%',
			'page'    => '%%title%% %%sep%% %%sitename%%',
			'archive' => '%%archive_title%% %%sep%% %%sitename%%',
			'search'  => '%%search_query%% %%sep%% %%sitename%%',
			'404'     => '%%not_found%% %%sep%% %%sitename%%',
			'author'  => '%%author%% %%sep%% %%sitename%%',
		] );

		$cfg      = $this->options ? $this->options->all() : [];
		$user_set = ( isset( $cfg['titles'] ) && is_array( $cfg['titles'] ) ) ? $cfg['titles'] : [];

		return array_filter( array_merge( $defaults, $user_set ), 'is_string' );
	}
}
