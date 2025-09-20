<?php
namespace ThemeSeoCore\Modules\OpenGraph;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open Graph / Twitter Cards Module.
 * Hooks are wired only when enabled() returns true.
 */
class Module extends BaseModule {

	protected static $slug = 'open-graph';
	protected static $title = 'Open Graph';
	protected static $description = 'Open Graph and Twitter Card tags.';

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		// Respect settings/compatibility.
		if ( ! $this->enabled() ) {
			return;
		}

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'wp_head' => [ [ $this, 'render' ], 5 ],
		];
	}

	/**
	 * Render Open Graph + basic Twitter tags.
	 * If an OgRenderer/TwitterCards exists, prefer those.
	 */
	public function render(): void {
		// Double-guard in case settings flip mid-request.
		if ( ! $this->enabled() ) {
			return;
		}

		// Prefer project renderers if present.
		if ( class_exists( __NAMESPACE__ . '\\OgRenderer' ) ) {
			$og = new OgRenderer();
			$og->output();
		} else {
			// Minimal OG fallbacks.
			$title = wp_get_document_title();
			$url   = is_singular() ? get_permalink() : home_url( '/' );
			$type  = is_singular() ? 'article' : 'website';

			printf( "<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $title ) );
			printf( "<meta property=\"og:type\" content=\"%s\" />\n", esc_attr( $type ) );
			printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $url ) );
			printf( "<meta property=\"og:site_name\" content=\"%s\" />\n", esc_attr( get_bloginfo( 'name' ) ) );

			// Image: featured image for singular, otherwise site icon if available.
			$img = '';
			if ( is_singular() ) {
				$img = get_the_post_thumbnail_url( get_queried_object_id(), 'full' ) ?: '';
			}
			if ( ! $img && function_exists( 'get_site_icon_url' ) ) {
				$img = (string) get_site_icon_url();
			}
			if ( $img ) {
				printf( "<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $img ) );
			}
		}

		if ( class_exists( __NAMESPACE__ . '\\TwitterCards' ) ) {
			$tw = new TwitterCards();
			$tw->output();
		} else {
			// Basic Twitter fallback.
			echo "<meta name=\"twitter:card\" content=\"summary_large_image\" />\n";
		}
	}
}

