<?php
namespace ThemeSeoCore\Modules\Schema;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema (JSON-LD) Module
 *
 * Lite scope: WebSite, Organization/Person, WebPage, Article, BreadcrumbList.
 * Hooks are only wired when enabled() returns true (compat + toggles).
 */
class Module extends BaseModule {

	protected static $slug = 'schema';
	protected static $title = 'Schema';
	protected static $description = 'Outputs JSON-LD for key entities.';

	/** @var Graph */
	protected $graph;

	/** Allowed types for Lite build */
	protected array $lite_types = [ 'WebSite', 'Organization', 'Person', 'WebPage', 'Article', 'BreadcrumbList' ];

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		// If suppressed (compat) or disabled, don't wire anything.
		if ( ! $this->enabled() ) {
			return;
		}

		$this->graph = new Graph();

		// Let the Graph honor our allowed-type list if it exposes a filter.
		add_filter( 'tsc_schema_allowed_types', [ $this, 'filter_allowed_types' ], 10, 1 );

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'wp_head' => [ [ $this, 'render' ], 9 ],
		];
	}

	/**
	 * Filter the allowed Schema.org types for the Lite build.
	 * If Pro is active, do nothing (Pro decides its own set).
	 *
	 * @param array|string $types
	 * @return array
	 */
	public function filter_allowed_types( $types ): array {
		if ( defined( 'TSC_IS_PRO' ) && TSC_IS_PRO ) {
			return (array) $types;
		}
		$incoming = (array) $types;
		$allowed  = array_intersect( $incoming, $this->lite_types );
		return ! empty( $allowed ) ? array_values( $allowed ) : $this->lite_types;
	}

	public function render(): void {
		// Double-guard (cheap, avoids output if setting was flipped mid-request).
		if ( ! $this->enabled() ) {
			return;
		}

		// Prefer Graph’s own output. If it consults the 'tsc_schema_allowed_types'
		// filter, our scope is enforced. If not, it still outputs its default set.
		$this->graph->output();
	}
}

