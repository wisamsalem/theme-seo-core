<?php
namespace ThemeSeoCore\Modules\Meta;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta Module
 *
 * Prints <meta name="description"> and <meta name="robots"> into the head.
 */
class Module extends BaseModule {

	protected static $slug = 'meta';
	protected static $title = 'Meta';
	protected static $description = 'Meta description and robots directives.';

	/** @var Options */
	protected $options;

	/** @var MetaGenerator */
	protected $generator;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );
		$this->options   = new Options( 'tsc_settings' );
		$this->generator = new MetaGenerator( $this->options );
		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// Prefer our internal head bus if present:
			'theme_seo/head/meta' => [ [ $this, 'render_meta_tags' ], 10, 0, 'action' ],
			// Fallback to wp_head if the head bus isn't used:
			'wp_head'             => [ [ $this, 'render_meta_tags' ], 10, 0, 'action' ],
		];
	}

	/**
	 * Echo meta tags safely.
	 */
	public function render_meta_tags(): void {
		if ( ! $this->enabled() ) {
			return;
		}

		$desc   = $this->generator->description_for_current();
		$robots = $this->generator->robots_for_current();

		if ( $desc !== '' ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $robots !== '' ) {
			echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
		}
	}
}
