<?php
namespace ThemeSeoCore\Frontend;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Container\ServiceProviderInterface;
use ThemeSeoCore\Contracts\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend bootstrap: head output, public assets, shortcodes.
 */
class Frontend implements ServiceProviderInterface, ServiceInterface {

	/** @inheritDoc */
	public function register( Container $c ): void {
		$c->singleton( Assets::class, fn() => new Assets() );
		$c->singleton( Head::class, fn() => new Head() );
		$c->singleton( Shortcodes\BreadcrumbsShortcode::class, fn() => new Shortcodes\BreadcrumbsShortcode() );
	}

	/** @inheritDoc */
	public function boot( Container $c ): void {
		$c->make( Assets::class )->register();
		$c->make( Head::class )->register();
		$c->make( Shortcodes\BreadcrumbsShortcode::class )->register();
	}
}

