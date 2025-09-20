<?php
namespace ThemeSeoCore\Modules\Redirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Matches an incoming request URI against a rule row.
 *
 * Columns used: source, target, match_type (exact|prefix|regex).
 */
class Matcher {

	/**
	 * @param array<string,mixed> $row
	 * @param string $request_uri   "/path?query=1"
	 * @param string $host          "example.com"
	 * @return bool
	 */
	public static function matches( array $row, string $request_uri, string $host ): bool {
		$source = (string) ( $row['source'] ?? '' );
		$type   = (string) ( $row['match_type'] ?? 'exact' );

		// Normalize both sides.
		$req_norm = self::normalize( $request_uri );
		$src_norm = self::normalize( $source );

		switch ( $type ) {
			case 'exact':
				return $req_norm === $src_norm;

			case 'prefix':
				return 0 === strpos( $req_norm, rtrim( $src_norm, '*' ) );

			case 'regex':
				$pattern = self::ensure_delimiters( $source );
				return (bool) preg_match( $pattern, $request_uri );

			default:
				return false;
		}
	}

	/**
	 * Lowercase host-insensitive path, keep query string as-is (case sensitive).
	 */
	protected static function normalize( string $uri ): string {
		$p = wp_parse_url( $uri );
		$path  = isset( $p['path'] ) ? strtolower( rtrim( $p['path'], '/' ) ?: '/' ) : '/';
		$query = isset( $p['query'] ) ? '?' . $p['query'] : '';
		return $path . $query;
	}

	protected static function ensure_delimiters( string $pattern ): string {
		$pattern = trim( $pattern );
		if ( '' === $pattern ) {
			return '#$^#';
		}
		$del = substr( $pattern, 0, 1 );
		$end = substr( $pattern, -1 );
		if ( $del === $end && in_array( $del, array( '/', '#', '~', '!' ), true ) ) {
			return $pattern;
		}
		return '#' . str_replace( '#', '\#', $pattern ) . '#';
	}
}

