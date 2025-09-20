<?php
namespace ThemeSeoCore\Database;

use ThemeSeoCore\Contracts\MigratorInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discovers and runs versioned migrations located under src/Database/Migrations.
 *
 * - Tracks applied versions in option 'tsc_migrations' (array of version strings).
 * - Each migration class must implement MigratorInterface and provide a unique version().
 */
class Migrator {

	const OPTION = 'tsc_migrations';

	/** @var string */
	protected $dir;

	public function __construct( ?string $dir = null ) {
		$this->dir = $dir ?: dirname( __FILE__ ) . '/Migrations';
	}

	/**
	 * Get list of completed migration versions.
	 *
	 * @return array<int,string>
	 */
	public function completed_versions(): array {
		$done = get_option( self::OPTION, array() );
		return is_array( $done ) ? array_values( $done ) : array();
	}

	/**
	 * Persist the completed versions list.
	 *
	 * @param array<int,string> $versions
	 * @return void
	 */
	protected function save_completed( array $versions ): void {
		update_option( self::OPTION, array_values( array_unique( $versions ) ) );
	}

	/**
	 * Discover migration classes by requiring all PHP files in the migrations dir
	 * and instantiating classes that implement MigratorInterface.
	 *
	 * @return array<int,MigratorInterface>
	 */
	public function discover(): array {
		if ( ! is_dir( $this->dir ) ) {
			return array();
		}

		foreach ( glob( $this->dir . '/*.php' ) as $file ) {
			// Ensure file loaded so its class is defined (PSR-4 may already handle).
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}

		// Find implementors by scanning declared classes in our namespace.
		$migrations = array();
		foreach ( get_declared_classes() as $class ) {
			if ( 0 === strpos( $class, 'ThemeSeoCore\\Database\\Migrations\\' ) ) {
				if ( is_subclass_of( $class, MigratorInterface::class ) ) {
					$migrations[] = new $class();
				}
			}
		}

		// Sort by version ascending.
		usort( $migrations, function ( MigratorInterface $a, MigratorInterface $b ) {
			return strcmp( $a->version(), $b->version() );
		} );

		return $migrations;
	}

	/**
	 * Apply all pending migrations.
	 *
	 * @return array<int,string> Versions that were applied.
	 */
	public function up(): array {
		$done = $this->completed_versions();
		$ran  = array();

		foreach ( $this->discover() as $migration ) {
			$ver = $migration->version();
			if ( in_array( $ver, $done, true ) ) {
				continue;
			}
			$migration->up();
			$done[] = $ver;
			$ran[]  = $ver;
		}

		if ( $ran ) {
			$this->save_completed( $done );
		}

		return $ran;
	}

	/**
	 * Roll back a specific migration by version (if applied).
	 *
	 * @param string $version
	 * @return void
	 */
	public function down( string $version ): void {
		foreach ( $this->discover() as $migration ) {
			if ( $migration->version() === $version ) {
				$migration->down();

				$done = $this->completed_versions();
				$done = array_values( array_filter( $done, fn( $v ) => $v !== $version ) );
				$this->save_completed( $done );
				return;
			}
		}
	}
}

