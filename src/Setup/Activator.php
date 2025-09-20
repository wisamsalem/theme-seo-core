<?php
namespace ThemeSeoCore\Setup;

use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	/** Hook name for hourly maintenance tasks. */
	public const CRON_HOURLY = 'tsc_hourly_event';

	/**
	 * Plugin activation entrypoint (network-aware).
	 */
	public static function run(): void {
		if ( is_multisite() && isset( $_GET['networkwide'] ) && '1' === $_GET['networkwide'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::activate_network();
		} else {
			self::activate_site( get_current_blog_id() );
		}
	}

	protected static function activate_network(): void {
		$site_ids = get_sites( [ 'fields' => 'ids' ] );
		foreach ( $site_ids as $site_id ) {
			self::activate_site( (int) $site_id );
		}
	}

	public static function activate_site( int $site_id ): void {
		if ( is_multisite() ) switch_to_blog( $site_id );

		self::ensure_options();
		self::ensure_caps();
		self::create_tables();
		self::schedule_events();

		flush_rewrite_rules();

		if ( is_multisite() ) restore_current_blog();
	}

	/**
	 * Ensure the main options array exists and has sane defaults.
	 * - Keep backward-compat with old 'separator' key.
	 * - Default-off modules: sitemaps.
	 * - Compatibility override default OFF.
	 */
	protected static function ensure_options(): void {
		$opts = get_option( 'tsc_settings', [] );
		if ( ! is_array( $opts ) ) {
			$opts = [];
		}

		// Title separator: prefer 'sep', fallback to legacy 'separator', then '–'.
		$sep = $opts['sep'] ?? ( $opts['separator'] ?? '–' );
		$opts['sep'] = is_string( $sep ) ? mb_substr( $sep, 0, 3 ) : '–';
		// Keep legacy key in place if it was used previously (harmless duplicate).
		if ( isset( $opts['separator'] ) && ! is_string( $opts['separator'] ) ) {
			unset( $opts['separator'] );
		}
		if ( ! isset( $opts['separator'] ) ) {
			$opts['separator'] = $opts['sep'];
		}

		// Modules container.
		if ( ! isset( $opts['modules'] ) || ! is_array( $opts['modules'] ) ) {
			$opts['modules'] = [];
		}
		// Default-OFF: sitemaps (opt-in).
		if ( ! array_key_exists( 'sitemaps', $opts['modules'] ) ) {
			$opts['modules']['sitemaps'] = 0;
		}

		// Compatibility override (off by default).
		if ( ! isset( $opts['compatibility_override'] ) ) {
			$opts['compatibility_override'] = 0;
		}

		update_option( 'tsc_settings', $opts );
	}

	/**
	 * Grant baseline capabilities to administrator if a custom cap is used.
	 * Skips when manage_seo() == 'manage_options'.
	 */
	protected static function ensure_caps(): void {
		$cap = Capabilities::manage_seo();
		if ( $cap === 'manage_options' ) {
			return; // nothing to add; admins already have it
		}
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}

	protected static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = $wpdb->prefix . 'tsc_redirects';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source_url TEXT NOT NULL,
			target_url TEXT NOT NULL,
			match_type VARCHAR(20) NOT NULL DEFAULT 'exact',
			http_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
			last_hit DATETIME NULL DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY match_type (match_type),
			KEY is_active (is_active)
		) {$charset_collate};";

		dbDelta( $sql );

		// Safety patches for legacy installs (no inline comments!)
		$col = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'match_type' ) );
		if ( ! $col ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN `match_type` VARCHAR(20) NOT NULL DEFAULT 'exact'" );
		} else {
			$type_ok    = ( isset( $col->Type ) && stripos( (string) $col->Type, 'varchar(20)' ) !== false );
			$default_ok = ( isset( $col->Default ) && $col->Default === 'exact' );
			if ( ! $type_ok || ! $default_ok ) {
				$wpdb->query( "ALTER TABLE {$table} MODIFY `match_type` VARCHAR(20) NOT NULL DEFAULT 'exact'" );
			}
		}
		$col = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'http_code' ) );
		if ( ! $col ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN `http_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301" );
		}
		$col = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'is_active' ) );
		if ( ! $col ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1" );
		}
	}

	protected static function schedule_events(): void {
		if ( ! wp_next_scheduled( self::CRON_HOURLY ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOURLY );
		}
	}
}

