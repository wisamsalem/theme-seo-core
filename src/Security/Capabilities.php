<?php
namespace ThemeSeoCore\Security;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Capability map. Defaults keep it simple: admin-only (manage_options).
 */
class Capabilities {

	/** No-op so callers can safely call ->register(). */
	public function register( $container = null ): void {
		// Hook here later if you want dynamic roles/meta caps.
	}

	/** Main settings capability. */
	public static function manage_seo(): string {
		return (string) apply_filters( 'tsc/cap/manage_seo', 'manage_options' );
	}

	public static function manage_redirects(): string {
		return (string) apply_filters( 'tsc/cap/manage_redirects', self::manage_seo() );
	}

	public static function manage_robots(): string {
		return (string) apply_filters( 'tsc/cap/manage_robots', self::manage_seo() );
	}

	public static function manage_sitemaps(): string {
		return (string) apply_filters( 'tsc/cap/manage_sitemaps', self::manage_seo() );
	}

	public static function edit_seo_meta(): string {
		return (string) apply_filters( 'tsc/cap/edit_seo_meta', 'edit_posts' );
	}
}

