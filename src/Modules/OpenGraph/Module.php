<?php
namespace ThemeSeoCore\Modules\OpenGraph;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Open Graph + Twitter Cards Module
 */
class Module extends BaseModule {

	protected static $slug = 'opengraph';
	protected static $title = 'Open Graph';
	protected static $description = 'Outputs OG tags and Twitter Cards.';

	/** @var OgRenderer */
	protected $og;

	/** @var TwitterCards */
	protected $twitter;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->og      = new OgRenderer();
		$this->twitter = new TwitterCards();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'wp_head' => [ [ $this, 'render' ], 8 ],
		];
	}

	public function render(): void {
		if ( ! $this->enabled() ) {
			return;
		}
		$this->og->output();
		$this->twitter->output();
	}
}

