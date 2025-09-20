<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If for any reason the main Menu didn't register (fatal earlier in the chain),
 * add the top-level SEO menu so the user can at least reach settings.
 */
class FallbackMenu {
	public static function register(): void {
		add_action( 'admin_menu', [ __CLASS__, 'ensure_menu' ], 99 );
	}

	public static function ensure_menu(): void {
		$slug = 'theme-seo-core';
		global $menu, $submenu;

		// If a page with our slug already exists, do nothing.
		if ( isset( $submenu[ $slug ] ) || self::menu_slug_exists( $slug ) ) {
			return;
		}

		add_menu_page(
			__( 'Theme SEO Core', 'theme-seo-core' ),
			__( 'SEO', 'theme-seo-core' ),
			Capabilities::manage_seo(),
			$slug,
			function () {
				echo '<div class="wrap"><h1>' . esc_html__( 'Theme SEO Core', 'theme-seo-core' ) . '</h1>';
				echo '<p>' . esc_html__( 'Fallback settings entry point.', 'theme-seo-core' ) . '</p>';
				$view = dirname( __DIR__, 2 ) . '/views/settings-page.php';
				if ( is_readable( $view ) ) {
					include $view;
				} else {
					echo '<div class="notice notice-warning"><p>' .
						esc_html__( 'Settings view not found. Expected /views/settings-page.php in plugin root.', 'theme-seo-core' ) .
						'</p></div>';
				}
				echo '</div>';
			},
			'dashicons-chart-area',
			59
		);
	}

	protected static function menu_slug_exists( string $slug ): bool {
		global $menu;
		if ( empty( $menu ) || ! is_array( $menu ) ) return false;
		foreach ( $menu as $m ) {
			if ( isset( $m[2] ) && $m[2] === $slug ) return true;
		}
		return false;
	}
}

