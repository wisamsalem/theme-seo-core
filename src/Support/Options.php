<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple, typed wrapper around get_option()/update_option() for plugin settings.
 */
class Options {

	/** @var string Main options key */
	protected $key = 'tsc_settings';

	/** @var array<string,mixed> Cached options */
	protected $cache = array();

	public function __construct( string $key = 'tsc_settings' ) {
		$this->key = $key;
		$this->cache = (array) get_option( $this->key, array() );
	}

	/**
	 * Get entire options array (merged with defaults).
	 *
	 * @param array<string,mixed> $defaults
	 * @return array<string,mixed>
	 */
	public function all( array $defaults = array() ): array {
		return wp_parse_args( $this->cache, $defaults );
	}

	/**
	 * Get a nested value using "dot" notation: modules.titles
	 *
	 * @param string $path
	 * @param mixed  $default
	 * @return mixed
	 */
	public function get( string $path, $default = null ) {
		$segments = explode( '.', $path );
		$value    = $this->cache;

		foreach ( $segments as $seg ) {
			if ( is_array( $value ) && array_key_exists( $seg, $value ) ) {
				$value = $value[ $seg ];
			} else {
				return $default;
			}
		}
		return $value;
	}

	/**
	 * Set a nested value (in-memory only; call save() to persist).
	 *
	 * @param string $path
	 * @param mixed  $value
	 * @return void
	 */
	public function set( string $path, $value ): void {
		$segments = explode( '.', $path );
		$ref      = &$this->cache;

		while ( count( $segments ) > 1 ) {
			$key = array_shift( $segments );
			if ( ! isset( $ref[ $key ] ) || ! is_array( $ref[ $key ] ) ) {
				$ref[ $key ] = array();
			}
			$ref = &$ref[ $key ];
		}
		$ref[ array_shift( $segments ) ] = $value;
	}

	/**
	 * Persist the cache to the options table.
	 *
	 * @return bool
	 */
	public function save(): bool {
		return (bool) update_option( $this->key, $this->cache );
	}
}

