<?php
namespace ThemeSeoCore\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue public-facing assets (CSS/JS).
 */
class Assets {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue(): void {
		// Styles
		wp_enqueue_style(
			'tsc-public',
			TSC_URL . 'assets/public/css/public.css',
			array(),
			TSC_VERSION
		);

		// Scripts
		wp_enqueue_script(
			'tsc-public',
			TSC_URL . 'assets/public/js/public.js',
			array(),
			TSC_VERSION,
			true
		);
	}
}

