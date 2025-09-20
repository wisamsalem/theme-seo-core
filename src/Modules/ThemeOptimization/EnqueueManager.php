<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Script/style optimizations: move to footer, defer/async, dequeue, preload.
 */
class EnqueueManager {

	protected array $cfg = [];

	public function register( array $cfg ): void {
		$this->cfg = $cfg;

		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_move_core_js' ], 0 );
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue' ], 1000 );
		add_filter( 'script_loader_tag', [ $this, 'attributes' ], 20, 3 );
		add_filter( 'style_loader_tag',  [ $this, 'preload_main_style' ], 10, 4 );
	}

	public function maybe_move_core_js(): void {
		if ( ! empty( $this->cfg['move_scripts_footer'] ) ) {
			// Move jQuery dependencies to footer (non-admin only).
			if ( ! is_admin() ) {
				wp_deregister_script( 'jquery' );
				wp_register_script( 'jquery', includes_url( 'js/jquery/jquery.min.js' ), [], null, true );
				wp_deregister_script( 'jquery-core' );
				wp_register_script( 'jquery-core', includes_url( 'js/jquery/jquery.min.js' ), [], null, true );
				wp_deregister_script( 'jquery-migrate' );
				wp_register_script( 'jquery-migrate', includes_url( 'js/jquery/jquery-migrate.min.js' ), [ 'jquery' ], null, true );
			}
		}
	}

	public function dequeue(): void {
		$handles = (array) ( $this->cfg['dequeue'] ?? [] );
		foreach ( $handles as $h ) {
			wp_dequeue_style( $h );
			wp_dequeue_script( $h );
		}
	}

	/**
	 * Add defer/async attributes to scripts based on config.
	 */
	public function attributes( string $tag, string $handle, string $src ): string {
		if ( is_admin() ) return $tag;

		$defer = ! empty( $this->cfg['defer_scripts'] );
		$async_list = array_map( 'strval', (array) ( $this->cfg['async_scripts'] ?? [] ) );

		if ( in_array( $handle, $async_list, true ) ) {
			return $this->inject_attr( $tag, 'async' );
		}
		if ( $defer ) {
			return $this->inject_attr( $tag, 'defer' );
		}
		return $tag;
	}

	protected function inject_attr( string $tag, string $attr ): string {
		if ( ! preg_match( '/\s' . preg_quote( $attr, '/' ) . '\b/', $tag ) ) {
			$tag = str_replace( '<script ', '<script ' . $attr . ' ', $tag );
		}
		return $tag;
	}

	/**
	 * Preload the main stylesheet while keeping it as stylesheet for non-FOIT.
	 */
	public function preload_main_style( string $html, string $handle, string $href, string $media ): string {
		if ( empty( $this->cfg['preload_main_style'] ) ) return $html;
		// Try to detect "theme" or "style" main handle.
		if ( in_array( $handle, apply_filters( 'tsc/theme_opt/preload_style_handles', [ 'theme', 'style', 'styles' ] ), true ) ) {
			$pre = sprintf(
				'<link rel="preload" as="style" href="%s"/>',
				esc_url( $href )
			);
			return $pre . "\n" . $html;
		}
		return $html;
	}
}

