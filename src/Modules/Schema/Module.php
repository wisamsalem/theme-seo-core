<?php
namespace ThemeSeoCore\Modules\Schema;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema (JSON-LD) Module
 *
 * Outputs a composed JSON-LD graph (WebSite, Organization, Article, BreadcrumbList, FAQPage).
 */
class Module extends BaseModule {

	protected static $slug = 'schema';
	protected static $title = 'Schema';
	protected static $description = 'Outputs JSON-LD for key entities.';

	/** @var Graph */
	protected $graph;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->graph = new Graph();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'wp_head' => [ [ $this, 'render' ], 9 ],
		];
	}

	public function render(): void {
		if ( ! $this->enabled() ) {
			return;
		}
		$this->graph->output();
	}
}

