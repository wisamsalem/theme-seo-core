<?php
namespace ThemeSeoCore\Contracts;

use ThemeSeoCore\Container\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for a toggleable SEO module.
 *
 * Typical lifecycle:
 * - register() : bindings, hooks that don't require WP globals fully ready
 * - boot()     : hooks that require runtime (runs on/after `init`)
 */
interface ModuleInterface {

	/**
	 * Unique slug for this module (used in settings toggles, filters).
	 *
	 * @return string
	 */
	public static function slug(): string;

	/**
	 * Human-readable title for UI.
	 *
	 * @return string
	 */
	public static function title(): string;

	/**
	 * Short description for UI/help.
	 *
	 * @return string
	 */
	public static function description(): string;

	/**
	 * Bind services, filters, and early hooks.
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function register( Container $container ): void;

	/**
	 * Late boot phase (e.g., enqueue, template tags).
	 *
	 * @param \ThemeSeoCore\Container\Container $container
	 * @return void
	 */
	public function boot( Container $container ): void;
}

