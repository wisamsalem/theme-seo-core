<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Container\ServiceProviderInterface;
use ThemeSeoCore\Contracts\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin bootstrap: registers menu, settings, metaboxes, columns, notices, assets.
 */
class Admin implements ServiceProviderInterface, ServiceInterface {

	/** @inheritDoc */
	public function register( Container $c ): void {
		$c->singleton( Menu::class, fn() => new Menu() );
		$c->singleton( SettingsRegistry::class, fn() => new SettingsRegistry() );
		$c->singleton( MetaBox::class, fn() => new MetaBox() );
		$c->singleton( Columns::class, fn() => new Columns() );
		$c->singleton( Notices::class, fn() => new Notices() );
		$c->singleton( Assets::class, fn() => new Assets() );
	}

	/** @inheritDoc */
	public function boot( Container $c ): void {
		// IMPORTANT: pass $c because your register() signatures expect it
		$c->make( Menu::class )->register( $c );
		$c->make( SettingsRegistry::class )->register( $c );
		$c->make( MetaBox::class )->register( $c );
		$c->make( Columns::class )->register( $c );
		$c->make( Notices::class )->register( $c );
		$c->make( Assets::class )->register( $c );
	}
}

