<?php
namespace ThemeSeoCore\Support;

use ThemeSeoCore\Contracts\ModuleInterface;
use ThemeSeoCore\Container\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opinionated base for SEO modules.
 *
 * Provides:
 * - slug/title/description via static properties or late static binding.
 * - HasHooks trait for neat hook maps.
 * - enable() helper based on saved options (fallback to true).
 */
abstract class BaseModule implements ModuleInterface {
	use HasHooks;

	/** @var string */
	protected static $slug = '';

	/** @var string */
	protected static $title = '';

	/** @var string */
	protected static $description = '';

	/** @var \ThemeSeoCore\Container\Container */
	protected $container;

	/** {@inheritDoc} */
	public static function slug(): string {
		if ( static::$slug ) {
			return static::$slug;
		}
		// Fallback: Class name → kebab-case (…\MyCoolThing\Module → my-cool-thing).
		$parts = explode( '\\', static::class );
		$name  = strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', preg_replace( '/Module$/', '', end( $parts ) ) ) );
		return trim( $name, '-' );
	}

	/** {@inheritDoc} */
	public static function title(): string {
		if ( static::$title ) {
			return static::$title;
		}
		return ucwords( str_replace( '-', ' ', static::slug() ) );
	}

	/** {@inheritDoc} */
	public static function description(): string {
		return static::$description ?: '';
	}

	/** {@inheritDoc} */
	public function register( Container $container ): void {
		$this->container = $container;
		// Child classes may override and call parent::register() to keep $container.
	}

	/** {@inheritDoc} */
	public function boot( Container $container ): void {
		// Optional in children.
	}

	/**
	 * Whether the module is enabled in settings (default true if unset).
	 *
	 * @return bool
	 */
	protected function enabled(): bool {
		$settings = get_option( 'tsc_settings', array() );
		$slug     = static::slug();
		if ( isset( $settings['modules'][ $slug ] ) ) {
			return (bool) $settings['modules'][ $slug ];
		}
		return true;
	}
}

