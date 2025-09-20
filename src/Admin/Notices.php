<?php
namespace ThemeSeoCore\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows admin notices for settings updates/errors.
 */
class Notices {

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'maybe_show' ) );
	}

	public function maybe_show(): void {
		if ( ! is_admin() ) {
			return;
		}
		// Show after admin-post redirect: ?tsc=updated|error on our page.
		if ( isset( $_GET['page'], $_GET['tsc'] ) && 'theme-seo-core' === $_GET['page'] ) { // phpcs:ignore
			$type = sanitize_text_field( wp_unslash( $_GET['tsc'] ) ); // phpcs:ignore
			if ( 'updated' === $type ) {
				$this->notice( __( 'Settings updated.', 'theme-seo-core' ), 'success' );
			} elseif ( 'error' === $type ) {
				$this->notice( __( 'There was an error saving your settings.', 'theme-seo-core' ), 'error' );
			}
		}
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

