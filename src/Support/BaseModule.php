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
 * - slug/title/description helpers
 * - HasHooks trait integration
 * - enabled() with:
 *     • default OFF list (opt-in modules, e.g. sitemaps)
 *     • compatibility suppression (Meta/OG/Schema) when other SEO plugin active,
 *       unless user enables an override in settings.
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

		$parts = explode('\\', static::class);
		$class = array_pop($parts); // often "Module"
		$name  = preg_replace('/Module$/', '', $class);

		// If class is literally "Module", fall back to last namespace segment (e.g., "Sitemaps")
		if ($name === '' && ! empty($parts)) {
			$name = end($parts);
		}

		$slug = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
		return trim($slug, '-');
	}

	/** {@inheritDoc} */
	public static function title(): string {
		return static::$title ?: ucwords(str_replace('-', ' ', static::slug()));
	}

	/** {@inheritDoc} */
	public static function description(): string {
		return static::$description ?: '';
	}

	/** {@inheritDoc} */
	public function register( Container $container ): void {
		$this->container = $container;
	}

	/** {@inheritDoc} */
	public function boot( Container $container ): void {
		// Optional in children.
	}

	/**
	 * Whether the module is enabled in settings.
	 *
	 * - If explicitly set in options, respect it.
	 * - Otherwise:
	 *     • Most modules default ON
	 *     • Some are default OFF to avoid collisions (e.g., sitemaps)
	 * - If a conflicting SEO plugin is active, suppress selected modules unless
	 *   a user override is enabled in settings.
	 */
	protected function enabled(): bool {
		$settings = get_option( 'tsc_settings', array() );

		$slug = static::slug();
		if ( isset( $settings['modules'][ $slug ] ) ) {
			return (bool) $settings['modules'][ $slug ];
		}

		// Default-OFF modules (opt-in only).
		$default_off = apply_filters( 'tsc_default_off_modules', array( 'sitemaps' ) );

		// Modules suppressed by compatibility layer when another SEO plugin is active.
		$compat_suppressed = apply_filters(
			'tsc_compat_suppressed_modules',
			array( 'meta', 'open-graph', 'schema' )
		);

		// If other SEO plugin is active and override isn't on, suppress those modules.
		if ( in_array( $slug, $compat_suppressed, true ) ) {
			if ( function_exists( __NAMESPACE__ . '\\compat_conflicting_seo_active' ) && compat_conflicting_seo_active() ) {
				$override = ! empty( $settings['compatibility_override'] );
				if ( ! $override ) {
					return false;
				}
			}
		}

		if ( in_array( $slug, $default_off, true ) ) {
			return false;
		}

		return true;
	}
}

/**
 * Detect common conflicting SEO plugins.
 */
if ( ! function_exists( __NAMESPACE__ . '\\compat_conflicting_seo_active' ) ) {
	function compat_conflicting_seo_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$slugs = array(
			'wordpress-seo/wp-seo.php',               // Yoast
			'seo-by-rank-math/rank-math.php',         // Rank Math
			'wp-seopress/seopress.php',               // SEOPress
		 );
		foreach ( $slugs as $file ) {
			if ( is_plugin_active( $file ) ) {
				return true;
			}
		}
		// Fallback class detection.
		if ( class_exists( '\\WPSEO_Frontend' ) || class_exists( '\\RankMath\\Helper' ) || class_exists( '\\SEOPress' ) ) {
			return true;
		}
		return false;
	}
}

