<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL helpers for canonicalization and hints.
 */
class Url {

	/**
	 * Normalize URL for canonical tag:
	 * - Force scheme to site scheme.
	 * - Remove fragments.
	 * - Optionally strip utm_* query args.
	 *
	 * @param string $url
	 * @param bool   $strip_utm
	 * @return string
	 */
	public static function canonicalize( string $url, bool $strip_utm = true ): string {
		if ( '' === $url ) {
			return '';
		}

		$parsed = wp_parse_url( $url );
		if ( ! $parsed || empty( $parsed['host'] ) ) {
			return $url;
		}

		$site  = wp_parse_url( home_url() );
		$scheme = $site['scheme'] ?? 'https';
		$host   = $parsed['host'];
		$path   = isset( $parsed['path'] ) ? self::remove_dot_segments( $parsed['path'] ) : '/';
		$query  = isset( $parsed['query'] ) ? $parsed['query'] : '';

		if ( $strip_utm && $query ) {
			parse_str( $query, $q );
			foreach ( array_keys( $q ) as $k ) {
				if ( 0 === strpos( strtolower( $k ), 'utm_' ) ) {
					unset( $q[ $k ] );
				}
			}
			$query = http_build_query( $q );
		}

		$norm = $scheme . '://' . $host . rtrim( $path, '/' );
		if ( '' !== $query ) {
			$norm .= '?' . $query;
		}
		return $norm;
	}

	/**
	 * Remove dot segments per RFC 3986.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function remove_dot_segments( string $path ): string {
		$input  = explode( '/', $path );
		$output = array();

		foreach ( $input as $seg ) {
			if ( '' === $seg || '.' === $seg ) {
				continue;
			}
			if ( '..' === $seg ) {
				array_pop( $output );
			} else {
				$output[] = $seg;
			}
		}
		return '/' . implode( '/', $output );
	}

	/**
	 * Build rel=preconnect/dns-prefetch tag href normalized for host only.
	 *
	 * @param string $url
	 * @return string host URL (scheme+host)
	 */
	public static function origin( string $url ): string {
		$p = wp_parse_url( $url );
		if ( ! $p || empty( $p['host'] ) ) {
			return '';
		}
		$scheme = $p['scheme'] ?? 'https';
		return $scheme . '://' . $p['host'];
	}

	/**
	 * True if URL is same-site.
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_internal( string $url ): bool {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		$u    = wp_parse_url( $url, PHP_URL_HOST );
		return $u && $host && ( strtolower( $u ) === strtolower( $host ) );
	}
}

