<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Heartbeat API tuning.
 */
class Heartbeat {

	protected array $cfg = [];

	public function register( array $cfg ): void {
		$this->cfg = $cfg;

		add_action( 'init', [ $this, 'maybe_disable_front' ], 1 );
		add_filter( 'heartbeat_settings', [ $this, 'settings' ] );
	}

	public function maybe_disable_front(): void {
		if ( ! is_admin() && ! is_user_logged_in() && ! empty( $this->cfg['disable_front'] ) ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	public function settings( array $settings ): array {
		$interval = max( 15, (int) ( $this->cfg['interval'] ?? 60 ) );
		$settings['interval'] = $interval;
		return $settings;
	}
}

