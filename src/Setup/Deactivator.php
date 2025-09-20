<?php
namespace ThemeSeoCore\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivator {

	/**
	 * Plugin deactivation entrypoint (network-aware).
	 */
	public static function run(): void {
		if ( is_multisite() && isset( $_GET['networkwide'] ) && '1' === $_GET['networkwide'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::deactivate_network();
		} else {
			self::deactivate_site( get_current_blog_id() );
		}
	}

	protected static function deactivate_network(): void {
		$site_ids = get_sites( [ 'fields' => 'ids' ] );
		foreach ( $site_ids as $site_id ) {
			self::deactivate_site( (int) $site_id );
		}
	}

	public static function deactivate_site( int $site_id ): void {
		if ( is_multisite() ) switch_to_blog( $site_id );

		self::unschedule_events();

		if ( is_multisite() ) restore_current_blog();
	}

	protected static function unschedule_events(): void {
		// Clear all scheduled instances of our hourly event.
		$timestamp = wp_next_scheduled( Activator::CRON_HOURLY );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, Activator::CRON_HOURLY );
			$timestamp = wp_next_scheduled( Activator::CRON_HOURLY );
		}
	}
}
