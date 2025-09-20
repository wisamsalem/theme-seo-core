<?php
namespace ThemeSeoCore\Modules\Meta;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta Module (title helpers, meta description, robots).
 * Hooks are wired only when enabled() returns true.
 */
class Module extends BaseModule {

	protected static $slug = 'meta';
	protected static $title = 'Meta';
	protected static $description = 'Meta description and robots directives.';

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
			'wp_head' => [ [ $this, 'render' ], 1 ],
		];
	}

	/**
	 * Render meta description + basic robots fallback.
	 * If a dedicated MetaGenerator exists in the repo, prefer that.
	 */
	public function render(): void {
		// Double-guard in case settings flip mid-request.
		if ( ! $this->enabled() ) {
			return;
		}

		// Prefer the project's generator if present.
		if ( class_exists( __NAMESPACE__ . '\\MetaGenerator' ) ) {
			/** @var MetaGenerator $gen */
			$gen = new MetaGenerator();
			$gen->output();
			return;
		}

		// Minimal fallback: try to build a description for singular content.
		if ( is_singular() ) {
			$desc = '';
			$post = get_queried_object();
			if ( $post && isset( $post->post_content ) ) {
				$raw  = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( (string) $post->post_content );
				$desc = trim( preg_replace( '/\s+/', ' ', wp_html_excerpt( $raw, 155, '…' ) ) );
			}
			if ( $desc !== '' ) {
				printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $desc ) );
			}
		}

		// Basic robots fallback (real logic should live in MetaGenerator).
		if ( is_search() || is_404() ) {
			echo "<meta name=\"robots\" content=\"noindex,follow\" />\n";
		}
	}
}

