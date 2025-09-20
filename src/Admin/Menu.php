<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) exit;

// IMPORTANT: this file must declare class Menu (NOT class Admin).
class Menu {

	public function register( Container $c ): void {
		add_action( 'admin_menu', [ $this, 'menus' ] );
	}

	public function menus(): void {
		$cap  = Capabilities::manage_seo();
		$slug = 'theme-seo-core';

		add_menu_page(
			__( 'Theme SEO Core', 'theme-seo-core' ),
			__( 'SEO', 'theme-seo-core' ),
			$cap,
			$slug,
			[ $this, 'render_settings' ],
			'dashicons-chart-area',
			59
		);

		// Lite-only upsell.
		if ( defined( 'TSC_IS_PRO' ) && ! TSC_IS_PRO ) {
			add_submenu_page(
				$slug,
				__( 'Get Full Features', 'theme-seo-core' ),
				__( 'Get Full Features', 'theme-seo-core' ),
				$cap,
				'theme-seo-core-upgrade',
				function () {
					echo '<div class="wrap"><h1>' . esc_html__( 'Get Full Features', 'theme-seo-core' ) . '</h1>';
					echo '<p>' . esc_html__( 'Unlock Redirects, Link Manager, Content Analysis, and integrations.', 'theme-seo-core' ) . '</p>';
					echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="https://minsifir.com/theme-seo-core/pro">' .
						esc_html__( 'Get Pro', 'theme-seo-core' ) . '</a></p></div>';
				}
			);
		}
	}

	public function render_settings(): void {
		echo '<div class="wrap"><h1>' . esc_html__( 'Theme SEO Core', 'theme-seo-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Configure global SEO settings and modules.', 'theme-seo-core' ) . '</p>';

		// Your views are at plugin root /views/settings-page.php
		$view = dirname( __DIR__, 2 ) . '/views/settings-page.php';
		if ( is_readable( $view ) ) {
			include $view;
		} else {
			echo '<div class="notice notice-warning"><p>' .
				esc_html__( 'Settings view not found. Expected /views/settings-page.php in plugin root.', 'theme-seo-core' ) .
				'</p></div>';
		}
		echo '</div>';
	}
}
