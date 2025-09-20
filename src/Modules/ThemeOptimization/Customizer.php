<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

use WP_Customize_Manager;

if ( ! defined( 'ABSPATH' ) ) exit;

class Customizer {

	protected $option = 'tsc_settings';

	public function register( WP_Customize_Manager $wp_customize ): void {
		// Log entry
		error_log('[TSC] Customizer::register entering');

		// Capability guard
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			error_log('[TSC] Customizer::register abort (capability)');
			return;
		}

		try {
			$defaults = $this->defaults();

			// Panel
			if ( ! $wp_customize->get_panel( 'tsc_seo_panel' ) ) {
				$wp_customize->add_panel( 'tsc_seo_panel', [
					'title'       => __( 'Theme SEO Core', 'theme-seo-core' ),
					'description' => __( 'Performance & head cleanup', 'theme-seo-core' ),
					'priority'    => 160,
					'capability'  => 'edit_theme_options',
				] );
			}

			// Sections
			$wp_customize->add_section( 'tsc_head_cleanup', [
				'title'      => __( 'Head Cleanup', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 10,
				'capability' => 'edit_theme_options',
			] );

			$wp_customize->add_section( 'tsc_enqueue', [
				'title'      => __( 'Assets & Enqueue', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 20,
				'capability' => 'edit_theme_options',
			] );

			$wp_customize->add_section( 'tsc_preconnect', [
				'title'      => __( 'Preconnect & DNS-Prefetch', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 30,
				'capability' => 'edit_theme_options',
			] );

			$wp_customize->add_section( 'tsc_fonts', [
				'title'      => __( 'Fonts', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 40,
				'capability' => 'edit_theme_options',
			] );

			$wp_customize->add_section( 'tsc_images', [
				'title'      => __( 'Images', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 50,
				'capability' => 'edit_theme_options',
			] );

			$wp_customize->add_section( 'tsc_heartbeat', [
				'title'      => __( 'Heartbeat', 'theme-seo-core' ),
				'panel'      => 'tsc_seo_panel',
				'priority'   => 60,
				'capability' => 'edit_theme_options',
			] );

			// Controls (one per section is enough to keep the panel visible)
			$this->add_checkbox( $wp_customize, 'theme_opt][clean_head',
				__( 'Remove WP cruft from <head>', 'theme-seo-core' ), (bool) $defaults['clean_head'], 'tsc_head_cleanup' );

			$this->add_checkbox( $wp_customize, 'theme_opt][enqueue][move_scripts_footer',
				__( 'Move scripts to footer', 'theme-seo-core' ), (bool) $defaults['enqueue']['move_scripts_footer'], 'tsc_enqueue' );

			$this->add_textarea( $wp_customize, 'theme_opt][preconnect][dns_prefetch',
				__( 'DNS-Prefetch domains (one per line)', 'theme-seo-core' ),
				$this->lines_from_array( (array) $defaults['preconnect']['dns_prefetch'] ),
				'tsc_preconnect', [ $this, 'sanitize_url_list' ] );

			$this->add_select( $wp_customize, 'theme_opt][fonts][google_display',
				__( 'Google Fonts display strategy', 'theme-seo-core' ),
				(string) $defaults['fonts']['google_display'],
				[ '' => __( 'Theme default', 'theme-seo-core' ), 'swap' => 'swap', 'block' => 'block', 'fallback' => 'fallback', 'optional' => 'optional', 'auto' => 'auto' ],
				'tsc_fonts', [ $this, 'sanitize_display_choice' ] );

			$this->add_checkbox( $wp_customize, 'theme_opt][images][add_lazy',
				__( 'Add loading="lazy" to images', 'theme-seo-core' ), (bool) $defaults['images']['add_lazy'], 'tsc_images' );

			$this->add_number( $wp_customize, 'theme_opt][heartbeat][interval',
				__( 'Admin heartbeat interval (seconds)', 'theme-seo-core' ),
				(int) $defaults['heartbeat']['interval'], 'tsc_heartbeat', [ $this, 'sanitize_interval' ],
				[ 'min' => 15, 'max' => 120, 'step' => 5 ] );

			error_log('[TSC] Customizer::register finished OK');
		} catch ( \Throwable $e ) {
			error_log('[TSC] Customizer::register ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
		}
	}

	protected function setting_id( string $path ): string {
		return $this->option . '[' . $path . ']';
	}

	protected function add_checkbox( WP_Customize_Manager $c, string $path, string $label, bool $default, string $section ): void {
		$id = $this->setting_id( $path );
		$c->add_setting( $id, [
			'type'              => 'option',
			'default'           => $default,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
			'transport'         => 'refresh',
		] );
		$c->add_control( $id, [
			'section' => $section,
			'label'   => $label,
			'type'    => 'checkbox',
		] );
	}

	protected function add_textarea( WP_Customize_Manager $c, string $path, string $label, string $default, string $section, callable $sanitize ): void {
		$id = $this->setting_id( $path );
		$c->add_setting( $id, [
			'type'              => 'option',
			'default'           => $default,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		] );
		$c->add_control( $id, [
			'section' => $section,
			'label'   => $label,
			'type'    => 'textarea',
		] );
	}

	protected function add_select( WP_Customize_Manager $c, string $path, string $label, string $default, array $choices, string $section, callable $sanitize ): void {
		$id = $this->setting_id( $path );
		$c->add_setting( $id, [
			'type'              => 'option',
			'default'           => $default,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		] );
		$c->add_control( $id, [
			'section' => $section,
			'label'   => $label,
			'type'    => 'select',
			'choices' => $choices,
		] );
	}

	protected function add_number( WP_Customize_Manager $c, string $path, string $label, int $default, string $section, callable $sanitize, array $attrs ): void {
		$id = $this->setting_id( $path );
		$c->add_setting( $id, [
			'type'              => 'option',
			'default'           => $default,
			'capability'        => 'edit_theme_options',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		] );
		$c->add_control( $id, [
			'section'      => $section,
			'label'        => $label,
			'type'         => 'number',
			'input_attrs'  => $attrs,
		] );
	}

	// Sanitizers
	public function sanitize_checkbox( $v ): bool { return (bool) ( is_string( $v ) ? in_array( $v, ['1','true','on'], true ) : $v ); }
	public function sanitize_url_list( $value ): string {
		if ( ! is_string( $value ) ) return '';
		$lines = preg_split( '/\r\n|\r|\n/', $value );
		$out = [];
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( $line === '' ) continue;
			if ( ! preg_match( '#^https?://#i', $line ) ) $line = 'https://' . $line;
			$url = esc_url_raw( $line );
			if ( $url ) $out[] = untrailingslashit( $url );
		}
		return implode( "\n", array_values( array_unique( $out ) ) );
	}
	public function sanitize_display_choice( $v ): string { $a=['','swap','block','fallback','optional','auto']; return in_array($v,$a,true)?$v:''; }
	public function sanitize_interval( $v ): int { $v=absint($v); if($v<15)$v=15; if($v>120)$v=120; return $v; }

	protected function defaults(): array {
		$local = [
			'clean_head' => true,
			'enqueue'    => [
				'move_scripts_footer' => true,
				'defer_scripts'       => true,
				'async_scripts'       => [],
				'dequeue'             => [],
				'preload_main_style'  => true,
			],
			'preconnect' => [
				'dns_prefetch' => [ 'https://fonts.gstatic.com', 'https://fonts.googleapis.com' ],
				'preconnect'   => [ 'https://fonts.gstatic.com' ],
			],
			'fonts'      => [
				'google_display' => 'swap',
				'disable_emojis' => true,
			],
			'images'     => [
				'add_lazy'        => true,
				'add_decoding'    => 'async',
				'add_dimensions'  => true,
			],
			'heartbeat'  => [
				'disable_front' => true,
				'interval'      => 60,
			],
		];

		$module_defaults = apply_filters( 'tsc/theme_opt/defaults', $local );
		$stored          = get_option( $this->option, [] );
		$current         = ( is_array( $stored ) && isset( $stored['theme_opt'] ) ) ? (array) $stored['theme_opt'] : [];
		return wp_parse_args( $current, $module_defaults );
	}

	protected function lines_from_array( array $urls ): string {
		$urls = array_map( static function( $u ) { $u = trim( (string) $u ); return $u === '' ? '' : untrailingslashit( $u ); }, $urls );
		return implode( "\n", array_values( array_unique( array_filter( $urls ) ) ) );
	}
}
