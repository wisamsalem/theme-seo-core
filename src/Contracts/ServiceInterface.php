<?php
namespace ThemeSeoCore\Contracts;

use ThemeSeoCore\Container\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic service contract for non-module components (Admin, Frontend, REST, etc).
 * Mirrors the provider lifecycle so you can keep classes cohesive.
 */
interface ServiceInterface {

	/**
	 * Register bindings and early hooks.
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Boot after all services are registered.
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function boot( Container $container ): void;
}

