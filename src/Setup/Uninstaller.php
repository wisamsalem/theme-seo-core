<?php
namespace ThemeSeoCore\Setup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Uninstaller (invoked from uninstall.php)
 *
 * - Deletes options/transients
 * - Drops custom tables
 * - Removes custom capabilities
 * - Clears cron hooks
 */
class Uninstaller {

	/** Match Deactivator/Activator constants */
	const OPTION_KEY   = Activator::OPTION_KEY;
	const VERSION_KEY  = Activator::VERSION_KEY;
	const CRON_HOURLY  = Activator::CRON_HOURLY;
	const TABLE_REDIRECTS = Activator::TABLE_REDIRECTS;

	/**
	 * Entry point called from uninstall.php.
	 *
	 * @return void
	 */
	public static function run(): void {
		// Safety: Must be called by WP uninstall context.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			return;
		}

		if ( is_multisite() ) {
			self::for_each_site( function () {
				self::uninstall_site();
			} );
		} else {
			self::uninstall_site();
		}
	}

	/**
	 * Perform uninstall tasks for a single site.
	 *
	 * @return void
	 */
	protected static function uninstall_site(): void {
		global $wpdb;

		// 1) Clear cron.
		$hook = self::CRON_HOURLY;
		$ts   = wp_next_scheduled( $hook );
		while ( false !== $ts ) {
			wp_unschedule_event( $ts, $hook );
			$ts = wp_next_scheduled( $hook );
		}

		// 2) Delete options.
		delete_option( self::OPTION_KEY );
		delete_option( self::VERSION_KEY );

		// Delete any transients used by the plugin (prefix: tsc_).
		// Remove site transients as well for single site.
		$like = $wpdb->esc_like( '_transient_tsc_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$like_site = $wpdb->esc_like( '_site_transient_tsc_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_site ) );

		// 3) Drop tables.
		$table = $wpdb->prefix . self::TABLE_REDIRECTS;
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// 4) Remove capabilities from common roles.
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'tsc_manage_seo' );
				$role->remove_cap( 'tsc_manage_redirects' );
			}
		}

		// 5) Flush rewrite rules after removing endpoints (safe here).
		flush_rewrite_rules( false );
	}

	/**
	 * Helper: run a callback for each site in a network.
	 *
	 * @param callable $callback
	 * @return void
	 */
	protected static function for_each_site( callable $callback ): void {
		$site_ids = get_sites( array( 'fields' => 'ids' ) );
		foreach ( $site_ids as $site_id ) {
			switch_to_blog( (int) $site_id );
			try {
				$callback();
			} finally {
				restore_current_blog();
			}
		}
	}
}

