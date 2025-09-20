<?php
namespace ThemeSeoCore\Security;

if ( ! defined( 'ABSPATH' ) ) exit;

class Nonces {

	public function register( $container = null ): void {}

	public static function create( string $action ): string {
		return (string) wp_create_nonce( 'tsc_' . $action );
	}

	public static function field( string $action, string $name = '_tsc_nonce' ): void {
		wp_nonce_field( 'tsc_' . $action, $name );
	}

	public static function verify( string $action, string $name = '_tsc_nonce' ): bool {
		$nonce = isset( $_REQUEST[ $name ] ) ? (string) $_REQUEST[ $name ] : ''; // phpcs:ignore
		return (bool) wp_verify_nonce( $nonce, 'tsc_' . $action );
	}
}

