<?php
namespace ThemeSeoCore\Modules\Meta;

use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MetaGenerator
 *
 * Computes and prints meta description and robots directives.
 * Safe even if called before the main query is ready.
 */
class MetaGenerator {

	const META_DESC_KEY   = '_theme_seo_description';
	const META_ROBOTS_KEY = '_theme_seo_robots'; // comma list: index,noindex,follow,nofollow

	/** @var Options */
	protected $options;

	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * STATIC shim so callers can do:
	 *   add_action( 'wp_head', [ \ThemeSeoCore\Modules\Meta\MetaGenerator::class, 'hook_head' ] );
	 * or call MetaGenerator::hook_head() directly.
	 */
	public static function hook_head(): void {
		// Prefer our internal head bus if present, plus wp_head fallback.
		add_action( 'theme_seo/head/meta', [ self::class, 'print_tags_static' ], 10 );
		add_action( 'wp_head',             [ self::class, 'print_tags_static' ], 10 );
	}

	/**
	 * Static printer used by the static hook above.
	 * Ensures single output per request.
	 */
	public static function print_tags_static(): void {
		static $printed = false;
		if ( $printed ) return;
		$printed = true;

		$gen   = new self( new Options( 'tsc_settings' ) );
		$desc  = $gen->description_for_current();
		$robot = $gen->robots_for_current();

		if ( $desc !== '' ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $robot !== '' ) {
			echo '<meta name="robots" content="' . esc_attr( $robot ) . '">' . "\n";
		}
	}

	/**
	 * Instance printer (optional, if someone wires the object directly).
	 */
	public function print_tags(): void {
		// Delegate to static to reuse the single-print guard.
		self::print_tags_static();
	}

	/**
	 * Return meta description for current context (or '').
	 */
	public function description_for_current(): string {
		if ( ! $this->query_ready() ) {
			// Early (bootstrap/admin) call: avoid conditionals, use tagline.
			return $this->sanitize_line( (string) get_bloginfo( 'description' ) );
		}

		// Per-post override
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$custom = get_post_meta( $post_id, self::META_DESC_KEY, true );
				if ( is_string( $custom ) && $custom !== '' ) {
					return $this->sanitize_line( $custom );
				}
			}
		}

		// Excerpt/content fallback
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$excerpt = get_post_field( 'post_excerpt', $post_id );
				if ( is_string( $excerpt ) && $excerpt !== '' ) {
					return $this->sanitize_line( $excerpt );
				}
				$content = get_post_field( 'post_content', $post_id );
				if ( is_string( $content ) && $content !== '' ) {
					return $this->trim_to_length( wp_strip_all_tags( $content ), 160 );
				}
			}
		}

		// Archives/home/search
		if ( is_front_page() ) {
			return $this->sanitize_line( (string) get_bloginfo( 'description' ) );
		}
		if ( is_search() ) {
			return $this->sanitize_line(
				sprintf( __( 'Search results for “%s”', 'theme-seo-core' ), get_search_query() )
			);
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->description ) && $term->description ) {
				return $this->trim_to_length( wp_strip_all_tags( (string) $term->description ), 160 );
			}
		}

		return '';
	}

	/**
	 * Return robots directives for current context (or '' to skip tag).
	 */
	public function robots_for_current(): string {
		if ( ! $this->query_ready() ) {
			return '';
		}
		// Per-post override
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$val = get_post_meta( $post_id, self::META_ROBOTS_KEY, true );
				if ( is_string( $val ) && $val !== '' ) {
					return $this->normalize_robots( $val );
				}
			}
		}
		return '';
	}

	/* ===== helpers ===== */

	protected function query_ready(): bool {
		// Any of these means conditional tags are safe.
		return did_action( 'wp' ) || did_action( 'template_redirect' ) || did_action( 'wp_head' );
	}

	protected function sanitize_line( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		return trim( $this->trim_to_length( $text, 160 ) );
	}

	protected function trim_to_length( string $text, int $len ): string {
		if ( mb_strlen( $text ) <= $len ) return $text;
		return rtrim( mb_substr( $text, 0, $len - 1 ) ) . '…';
	}

	protected function normalize_robots( string $raw ): string {
		$raw  = strtolower( trim( $raw ) );
		$bits = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		if ( empty( $bits ) ) return '';

		$flags = [ 'index' => false, 'noindex' => false, 'follow' => false, 'nofollow' => false ];
		foreach ( $bits as $b ) if ( isset( $flags[ $b ] ) ) $flags[ $b ] = true;

		$out = [];
		$out[] = $flags['noindex'] ? 'noindex' : 'index';
		$out[] = $flags['nofollow'] ? 'nofollow' : 'follow';
		return implode( ',', $out );
	}
}
