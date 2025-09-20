<?php
namespace ThemeSeoCore\Cli;

use ThemeSeoCore\Database\Migrator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP-CLI commands for Theme SEO Core.
 *
 * Register as either:
 *   wp tsc ...
 * or (aliases)
 *   wp theme-seo ...
 */
class Commands {

	/**
	 * Register CLI commands if WP_CLI is available.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tsc', array( $this, 'root' ) );
			\WP_CLI::add_command( 'tsc migrate', array( $this, 'migrate' ) );
			\WP_CLI::add_command( 'tsc settings', array( $this, 'settings' ) );
		}
	}

	/**
	 * Root help.
	 */
	public function root( $args, $assoc_args ): void {
		\WP_CLI::line( "Theme SEO Core (TSC) — WP-CLI" );
		\WP_CLI::line( "Usage:" );
		\WP_CLI::line( "  wp tsc migrate [--list] [--down=<version>]" );
		\WP_CLI::line( "  wp tsc settings get <path>" );
		\WP_CLI::line( "  wp tsc settings set <path> <value>" );
	}

	/**
	 * Run/list migrations.
	 *
	 * ## OPTIONS
	 *
	 * [--list]
	 * : List all migrations and their status.
	 *
	 * [--down=<version>]
	 * : Roll back a specific version.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tsc migrate --list
	 *     wp tsc migrate
	 *     wp tsc migrate --down=2025_01_01_000000_create_redirects_table
	 */
	public function migrate( $args, $assoc_args ): void {
		$migrator = new Migrator();

		if ( ! empty( $assoc_args['list'] ) ) {
			$all   = $migrator->discover();
			$done  = $migrator->completed_versions();
			\WP_CLI\Utils\format_items( 'table', array_map( function( $m ) use ( $done ) {
				return array(
					'version' => $m->version(),
					'description' => method_exists( $m, 'description' ) ? $m->description() : '',
					'status'  => in_array( $m->version(), $done, true ) ? 'up' : 'pending',
				);
			}, $all ), array( 'version', 'description', 'status' ) );
			return;
		}

		if ( ! empty( $assoc_args['down'] ) ) {
			$version = (string) $assoc_args['down'];
			$migrator->down( $version );
			\WP_CLI::success( "Rolled back {$version}." );
			return;
		}

		$ran = $migrator->up();
		if ( empty( $ran ) ) {
			\WP_CLI::success( 'No pending migrations.' );
		} else {
			foreach ( $ran as $v ) {
				\WP_CLI::log( "Migrated: {$v}" );
			}
			\WP_CLI::success( 'All pending migrations applied.' );
		}
	}

	/**
	 * Get/set settings (dot-path helper).
	 *
	 * ## OPTIONS
	 *
	 * get <path>
	 * : Reads a value using dot notation. Example: modules.titles
	 *
	 * set <path> <value>
	 * : Sets a value (strings "true"/"false"/numbers are typed). Example: separator "–"
	 *
	 * ## EXAMPLES
	 *
	 *     wp tsc settings get separator
	 *     wp tsc settings set modules.schema true
	 */
	public function settings( $args, $assoc_args ): void {
		$sub = $args[0] ?? '';
		if ( 'get' === $sub ) {
			$path = $args[1] ?? '';
			if ( ! $path ) {
				\WP_CLI::error( 'Provide a path, e.g., separator or modules.schema.' );
			}
			$opts = (array) get_option( 'tsc_settings', array() );
			$val  = $this->dot_get( $opts, $path, null );
			\WP_CLI::print_value( $val, array( 'format' => 'json' ) );
			return;
		}

		if ( 'set' === $sub ) {
			$path  = $args[1] ?? '';
			$value = $args[2] ?? '';
			if ( '' === $path ) {
				\WP_CLI::error( 'Provide a path and value.' );
			}
			$opts = (array) get_option( 'tsc_settings', array() );
			$this->dot_set( $opts, $path, $this->coerce( $value ) );
			update_option( 'tsc_settings', $opts );
			\WP_CLI::success( "Updated {$path}." );
			return;
		}

		\WP_CLI::error( 'Unknown subcommand. Use: get, set.' );
	}

	/* -------------------------- helpers -------------------------- */

	protected function coerce( string $value ) {
		$lc = strtolower( $value );
		if ( 'true' === $lc )  return true;
		if ( 'false' === $lc ) return false;
		if ( is_numeric( $value ) ) {
			return $value + 0;
		}
		// Trim quotes if passed
		return preg_replace( '/^"(.*)"$|^\'+(.*)\'+$/', '$1$2', $value );
	}

	protected function dot_get( array $arr, string $path, $default = null ) {
		$segments = array_filter( explode( '.', $path ), 'strlen' );
		$val      = $arr;
		foreach ( $segments as $seg ) {
			if ( is_array( $val ) && array_key_exists( $seg, $val ) ) {
				$val = $val[ $seg ];
			} else {
				return $default;
			}
		}
		return $val;
	}

	protected function dot_set( array &$arr, string $path, $value ): void {
		$segments = array_filter( explode( '.', $path ), 'strlen' );
		$ref = &$arr;
		while ( count( $segments ) > 1 ) {
			$k = array_shift( $segments );
			if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
				$ref[ $k ] = array();
			}
			$ref = &$ref[ $k ];
		}
		$ref[ array_shift( $segments ) ] = $value;
	}
}

