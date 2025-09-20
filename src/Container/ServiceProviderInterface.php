<?php
namespace ThemeSeoCore\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple service provider contract (register + boot).
 *
 * @package ThemeSeoCore
 */
interface ServiceProviderInterface {

	/**
	 * Bind services into the container.
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Perform actions after all providers are registered (hooks, init, etc).
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function boot( Container $container ): void;
}

