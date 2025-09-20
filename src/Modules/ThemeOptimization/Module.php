<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) exit;

class Module extends BaseModule {

	protected static $slug = 'theme-optimization';
	protected static $title = 'Theme Optimization';
	protected static $description = 'Clean <head>, better enqueues, image hints, fonts, preconnect and heartbeat tuning.';

	/** @var Options */
	protected $options;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );
		$this->options = new Options( 'tsc_settings' );

		$cfg = $this->config();

		if ( $cfg['clean_head'] ) ( new CleanHead() )->register();
		if ( $cfg['enqueue'] )    ( new EnqueueManager() )->register( $cfg['enqueue'] );
		if ( $cfg['preconnect'] ) ( new Preconnect() )->register( $cfg['preconnect'] );
		if ( $cfg['fonts'] )      ( new Fonts() )->register( $cfg['fonts'] );
		if ( $cfg['images'] )     ( new Images() )->register( $cfg['images'] );
		if ( $cfg['heartbeat'] )  ( new Heartbeat() )->register( $cfg['heartbeat'] );

		// ✅ Hook Customizer ALWAYS (no is_admin() gate). Early to register, plus a late self-heal.
		add_action( 'customize_register', function( $wp_customize ) {
			error_log('[TSC] ThemeOptimization: customize_register');
			try {
				( new Customizer() )->register( $wp_customize );
			} catch ( \Throwable $e ) {
				error_log('[TSC] ThemeOptimization Customizer ERROR: '.$e->getMessage());
			}
		}, 9 );

		// 🔧 If another plugin removes our panel, re-add it just before render.
		add_action( 'customize_controls_init', function() {
			global $wp_customize;
			if ( $wp_customize && ! $wp_customize->get_panel( 'tsc_seo_panel' ) ) {
				error_log('[TSC] Re-adding tsc_seo_panel (was removed by another plugin)');
				$wp_customize->add_panel( 'tsc_seo_panel', [
					'title'       => __( 'Theme SEO Core', 'theme-seo-core' ),
					'description' => __( 'Performance & head cleanup', 'theme-seo-core' ),
					'priority'    => 40,
					'capability'  => 'edit_theme_options',
				] );
			}
		}, 0 );
	}

	protected function hooks(): array {
		return [ 'tsc/theme_opt/defaults' => 'defaults' ];
	}

	public function defaults(): array {
		return [
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
	}

	protected function config(): array {
		$stored = (array) $this->options->get( 'theme_opt', [] );
		$cfg    = wp_parse_args( $stored, $this->defaults() );
		return (array) apply_filters( 'tsc/theme_opt/config', $cfg );
	}
}
