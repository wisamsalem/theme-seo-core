<?php
namespace ThemeSeoCore\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility with Yoast SEO (wp-seo):
 * - If Yoast is active, disable overlapping TSC modules by default
 *   (schema, sitemaps, opengraph, breadcrumbs, canonical/meta).
 * - Suppress TSC canonical tag so Yoast’s wins.
 *
 * You can override by defining:
 *   define( 'TSC_FORCE_SEO_OVERLAP', true );
 */
class YoastCompat {

	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_boot' ), 11 );
	}

	public function maybe_boot(): void {
		if ( ! defined( 'WPSEO_VERSION' ) && ! class_exists( '\WPSEO_Frontend' ) ) {
			return;
		}

		// Prefer Yoast for these modules unless explicitly forced.
		add_filter( 'tsc/module/active', array( $this, 'prefer_yoast' ), 10, 3 );

		// Ensure our canonical doesn't duplicate Yoast's.
		add_filter( 'tsc/head/canonical', array( $this, 'suppress_canonical' ), 5 );
	}

	/**
	 * Return false for overlapping modules so our Plugin::boot_modules() skips them.
	 *
	 * @param bool   $active Current active decision (from options).
	 * @param string $slug   Module slug.
	 * @param array  $config Plugin config.
	 * @return bool
	 */
	public function prefer_yoast( bool $active, string $slug, array $config ): bool {
		if ( defined( 'TSC_FORCE_SEO_OVERLAP' ) && TSC_FORCE_SEO_OVERLAP ) {
			return $active;
		}

		$overlap = array(
			'opengraph',
			'schema',
			'sitemaps',
			'breadcrumbs',
			'canonical',
			'meta',
		);

		if ( in_array( $slug, $overlap, true ) ) {
			return false;
		}

		return $active;
	}

	/**
	 * If Yoast is active, let it output canonical (return empty to skip ours).
	 *
	 * @param string $canonical
	 * @return string
	 */
	public function suppress_canonical( string $canonical ): string {
		return '';
	}
}

