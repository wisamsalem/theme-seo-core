<?php
namespace ThemeSeoCore\Admin;

use function ThemeSeoCore\Support\compat_conflicting_seo_active;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notices (settings saved + compatibility warning).
 */
class Notices {

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'maybe_show_result' ) );
		add_action( 'admin_notices', array( $this, 'compat_notice' ) );
	}

	/** Show success/error after settings save on our page. */
	public function maybe_show_result(): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( isset( $_GET['page'], $_GET['tsc'] ) && 'theme-seo-core' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$type = sanitize_text_field( wp_unslash( $_GET['tsc'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'updated' === $type ) {
				$this->notice( __( 'Settings updated.', 'theme-seo-core' ), 'success' );
			} elseif ( 'error' === $type ) {
				$this->notice( __( 'There was an error saving your settings.', 'theme-seo-core' ), 'error' );
			}
		}
	}

	/** Warn on settings screens if another SEO plugin is active and override is off. */
	public function compat_notice(): void {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( empty( $screen ) || strpos( (string) $screen->id, 'theme-seo-core' ) === false ) {
			return; // only on our settings pages
		}
		if ( ! compat_conflicting_seo_active() ) {
			return;
		}
		$opts = get_option( 'tsc_settings', array() );
		if ( ! empty( $opts['compatibility_override'] ) ) {
			return; // user chose to override; no nag
		}
		echo '<div class="notice notice-warning"><p>';
		echo wp_kses_post( sprintf(
			/* translators: %s: settings URL */
			__( 'Another SEO plugin is active. Theme SEO Core will suppress Meta, Open Graph, and Schema to avoid duplicates. You can change this in <a href="%s">Compatibility</a>.', 'theme-seo-core' ),
			esc_url( admin_url( 'admin.php?page=theme-seo-core#tsc_compat' ) )
		) );
		echo '</p></div>';
	}

	protected function notice( string $message, string $kind = 'info' ): void {
		$map = array(
			'success' => 'notice-success',
			'error'   => 'notice-error',
			'warning' => 'notice-warning',
			'info'    => 'notice-info',
		);
		$cls = $map[ $kind ] ?? $map['info'];
		printf( '<div class="notice %1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $cls ), esc_html( $message ) );
	}
}

