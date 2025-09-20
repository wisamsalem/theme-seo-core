<?php
namespace ThemeSeoCore\Modules\Robots;

use ThemeSeoCore\Container\Container;

if ( ! defined( 'ABSPATH' ) ) exit;

class Module {
	public function register( ?Container $c = null ): void {
		// Admin page
		add_action( 'admin_menu', [ $this, 'submenu' ] );

		// Dynamic robots.txt on the frontend
		add_action( 'do_robots', [ $this, 'render_dynamic_robots' ] );
	}

	public function submenu(): void {
		add_submenu_page(
			'theme-seo-core',
			__( 'Robots.txt', 'theme-seo-core' ),
			__( 'Robots.txt', 'theme-seo-core' ),
			'manage_options',
			'theme-seo-robots',
			[ $this, 'render_editor' ]
		);
	}

	public function render_editor(): void {
		if ( isset( $_POST['tsc_robots'] ) && check_admin_referer( 'tsc_robots_save', '_tsc_nonce' ) ) {
			$lines = (string) wp_unslash( $_POST['tsc_robots'] ); // phpcs:ignore
			update_option( 'tsc_robots_custom', wp_kses_post( $lines ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'theme-seo-core' ) . '</p></div>';
		}
		$val = (string) get_option( 'tsc_robots_custom', '' );

		echo '<div class="wrap"><h1>' . esc_html__( 'Robots.txt', 'theme-seo-core' ) . '</h1>';
		echo '<form method="post">';
		wp_nonce_field( 'tsc_robots_save', '_tsc_nonce' );
		echo '<p><textarea name="tsc_robots" rows="14" class="large-text code" spellcheck="false">' . esc_textarea( $val ) . '</textarea></p>';
		echo '<p><button class="button button-primary">' . esc_html__( 'Save', 'theme-seo-core' ) . '</button></p>';
		echo '</form></div>';
	}

	public function render_dynamic_robots(): void {
		$blog_public = get_option( 'blog_public' );
		header( 'Content-Type: text/plain; charset=utf-8' );

		// If site discouraged from indexing, disallow all.
		if ( '0' === (string) $blog_public ) {
			echo "User-agent: *\nDisallow: /\n";
			return;
		}

		// Custom lines override defaults if provided.
		$custom = (string) get_option( 'tsc_robots_custom', '' );
		if ( '' !== $custom ) {
			echo $custom;
			echo "\n";
			return;
		}

		// Default minimal robots with sitemap hint.
		$home = home_url( '/' );
		$sm   = home_url( '/sitemap.xml' );
		echo "User-agent: *\nAllow: /\n";
		echo "Sitemap: {$sm}\n";
	}
}
