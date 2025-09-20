<?php
namespace ThemeSeoCore\Rest;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Container\ServiceProviderInterface;
use ThemeSeoCore\Contracts\ServiceInterface;
use ThemeSeoCore\Rest\v1\SeoController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API bootstrap: registers versioned controllers.
 */
class Controller implements ServiceProviderInterface, ServiceInterface {

	/** @inheritDoc */
	public function register( Container $c ): void {
		$c->singleton( SeoController::class, fn() => new SeoController() );
	}

	/** @inheritDoc */
	public function boot( Container $c ): void {
		add_action( 'rest_api_init', function () use ( $c ) {
			$c->make( SeoController::class )->register_routes();
		} );
	}
}

