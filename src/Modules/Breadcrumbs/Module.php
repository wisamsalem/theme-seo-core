<?php
namespace ThemeSeoCore\Modules\Breadcrumbs;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumbs Module
 *
 * Provides a shortcode [tsc_breadcrumbs] and a render function for themes.
 * The Schema module separately outputs JSON-LD BreadcrumbList.
 */
class Module extends BaseModule {

	protected static $slug = 'breadcrumbs';
	protected static $title = 'Breadcrumbs';
	protected static $description = 'Accessible breadcrumb trail with sensible defaults.';

	/** @var TrailBuilder */
	protected $builder;

	/** @var Renderer */
	protected $renderer;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->builder  = new TrailBuilder();
		$this->renderer = new Renderer( $this->builder );

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'init' => 'register_shortcode',

			// Optional: allow themes to echo with do_action('tsc/breadcrumbs').
			'tsc/breadcrumbs' => [ [ $this, 'action_echo' ], 10, 1, 'action' ],
		];
	}

	public function register_shortcode(): void {
		add_shortcode( 'tsc_breadcrumbs', function ( $atts = array() ) {
			return $this->renderer->render( (array) $atts );
		} );
	}

	/**
	 * Action helper: do_action('tsc/breadcrumbs', $args).
	 *
	 * @param array $args
	 * @return void
	 */
	public function action_echo( $args = array() ): void {
		echo $this->renderer->render( (array) $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

