<?php
namespace ThemeSeoCore\Database\Migrations;

use ThemeSeoCore\Contracts\MigratorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create redirects table for the Redirects module.
 * Version format: YYYY_MM_DD_HHMMSS_description
 */
class CreateRedirectsTable_2025_01_01_000000 implements MigratorInterface {

	public function version(): string {
		return '2025_01_01_000000_create_redirects_table';
	}

	public function description(): string {
		return 'Create redirects table (source, target, status, match type, counters).';
	}

	public function up(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$table           = $wpdb->prefix . 'tsc_redirects';

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source VARCHAR(2048) NOT NULL,
			target VARCHAR(2048) NOT NULL,
			status SMALLINT UNSIGNED NOT NULL DEFAULT 301,
			match_type VARCHAR(20) NOT NULL DEFAULT 'exact', -- exact|prefix|regex
			hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
			last_hit DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY source (source(191)),
			KEY target (target(191))
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public function down(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'tsc_redirects';
		// Drop table on rollback (non-destructive environments only).
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}

