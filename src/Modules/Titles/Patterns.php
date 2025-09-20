<?php
namespace ThemeSeoCore\Modules\Titles;

use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Patterns — token replacement for title templates.
 *
 * Supported tokens (filterable via 'tsc/titles/tokens'):
 *  - %%title%%           : Current singular title (or site name on home)
 *  - %%sitename%%        : Site name
 *  - %%tagline%%         : Tagline (site description)
 *  - %%sep%%             : Separator from settings
 *  - %%archive_title%%   : Contextual archive title (category/tag/tax/CPT/date)
 *  - %%search_query%%    : "Search results for “query”"
 *  - %%not_found%%       : "Not Found"
 *  - %%author%%          : Author display name (author archives)
 */
class Patterns {

	/** @var Options */
	protected $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Replace tokens in a pattern string using current WP query context.
	 *
	 * @param string $pattern
	 * @return string
	 */
	public function replace( string $pattern ): string {
		$tokens = $this->tokens();

		// Allow external code to modify the map.
		$map = apply_filters( 'tsc/titles/tokens', $tokens, $pattern );

		$out = strtr( $pattern, $map );

		// Collapse excessive whitespace.
		$out = preg_replace( '/\s{2,}/', ' ', (string) $out );
		return trim( $out );
	}

	/**
	 * Build the token map for the current request.
	 *
	 * @return array<string,string>
	 */
	protected function tokens(): array {
		$sep      = $this->separator();
		$sitename = get_bloginfo( 'name' );
		$tagline  = get_bloginfo( 'description' );

		$title = $sitename;

		if ( is_singular() ) {
			$title = single_post_title( '', false );
		} elseif ( is_front_page() ) {
			$title = $sitename;
		} elseif ( is_home() ) {
			$title = get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'theme-seo-core' );
		} else {
			$title = $this->archive_title();
		}

		return [
			'%%title%%'         => $title,
			'%%sitename%%'      => $sitename,
			'%%tagline%%'       => $tagline,
			'%%sep%%'           => $sep,
			'%%archive_title%%' => $this->archive_title(),
			'%%search_query%%'  => sprintf(
				/* translators: %s: search query */
				__( 'Search results for “%s”', 'theme-seo-core' ),
				get_search_query()
			),
			'%%not_found%%'     => __( 'Not Found', 'theme-seo-core' ),
			'%%author%%'        => is_author() ? get_the_author() : '',
		];
	}

	/**
	 * Compute a human-friendly archive title (similar to get_the_archive_title() but cleaner).
	 *
	 * @return string
	 */
	protected function archive_title(): string {
		if ( is_category() ) {
			return single_cat_title( '', false );
		}
		if ( is_tag() ) {
			return single_tag_title( '', false );
		}
		if ( is_tax() ) {
			return single_term_title( '', false );
		}
		if ( is_post_type_archive() ) {
			return post_type_archive_title( '', false );
		}
		if ( is_author() ) {
			return get_the_author();
		}
		if ( is_year() ) {
			return get_query_var( 'year' );
		}
		if ( is_month() ) {
			return single_month_title( ' ', false );
		}
		if ( is_day() ) {
			return mysql2date( get_option( 'date_format' ), get_the_date( 'Y-m-d' ) );
		}
		return get_bloginfo( 'name' );
	}

	/**
	 * The configured separator string.
	 *
	 * @return string
	 */
	protected function separator(): string {
		$cfg = $this->options->all();
		return isset( $cfg['separator'] ) ? (string) $cfg['separator'] : '–';
	}
}

