<?php
namespace ThemeSeoCore\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for database/data migrations used by modules (e.g., Redirects).
 */
interface MigratorInterface {

	/**
	 * Unique migration identifier (e.g., "2025_01_01_000000_create_redirects_table").
	 *
	 * @return string
	 */
	public function version(): string;

	/**
	 * Apply the migration (create tables, add columns, seed data).
	 *
	 * @return void
	 */
	public function up(): void;

	/**
	 * Roll back the migration (drop/alter structures added by up()).
	 *
	 * @return void
	 */
	public function down(): void;

	/**
	 * Optional human-readable description.
	 *
	 * @return string
	 */
	public function description(): string;
}

