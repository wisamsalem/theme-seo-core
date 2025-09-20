<?php
namespace ThemeSeoCore\Modules\XmlRpc;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Low-level hooks that actually enforce XML-RPC rules.
 */
class Controls {

	/** @var array<string,mixed> */
	protected array $cfg = [];

	public function register( array $cfg ): void {
		$this->cfg = $cfg;

		// 1) Hard-disable endpoint entirely.
		add_filter( 'xmlrpc_enabled', [ $this, 'xmlrpc_enabled' ] );

		// 2) Remove risky methods / restrict to allow-list.
		add_filter( 'xmlrpc_methods', [ $this, 'filter_methods' ] );

		// 3) Before handling a request, enforce IP allowlist and rate limit.
		add_filter( 'pre_xmlrpc_request', [ $this, 'gate_request' ] );

		// 4) Hide pingback surface area (headers + head link).
		if ( ! empty( $this->cfg['remove_pingback_header'] ) ) {
			add_filter( 'wp_headers', [ $this, 'strip_pingback_header' ] );
			remove_action( 'wp_head', 'feed_links_extra', 3 ); // keeps it a bit quieter
			remove_action( 'wp_head', 'pingback_link' );
		}

		// 5) If completely disabled, 403 early on direct /xmlrpc.php hits (nice message).
		add_action( 'init', [ $this, 'maybe_block_endpoint' ], 1 );
	}

	/* ---------------- XML-RPC switches ---------------- */

	public function xmlrpc_enabled( bool $enabled ): bool {
		// Respect explicit config; otherwise, keep WP's default behaviour.
		if ( isset( $this->cfg['disable'] ) ) {
			return ! (bool) $this->cfg['disable'];
		}
		return $enabled;
	}

	/**
	 * Remove methods we don't want, or apply an allow-list.
	 *
	 * @param array<string,callable> $methods
	 * @return array<string,callable>
	 */
	public function filter_methods( array $methods ): array {
		// If hard-disabled, doesn't matter—but keep tidy anyway.
		if ( ! $this->xmlrpc_enabled( true ) ) {
			return [];
		}

		// Remove pingback endpoints if requested.
		if ( ! empty( $this->cfg['disable_pingbacks'] ) ) {
			unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		}

		// Remove system.multicall to reduce amplification abuse vectors.
		if ( ! empty( $this->cfg['disable_multicall'] ) ) {
			unset( $methods['system.multicall'] );
		}

		// If an allow-list is specified, intersect with it.
		$allow = array_filter( array_map( 'strval', (array) ( $this->cfg['allow_methods'] ?? [] ) ) );
		if ( ! empty( $allow ) ) {
			$allow = array_fill_keys( $allow, true );
			$methods = array_intersect_key( $methods, $allow );
		}

		return $methods;
	}

	/**
	 * Run before WordPress processes an XML-RPC request.
	 * You can deny by returning an IXR_Error (or a WP_Error).
	 *
	 * @param null|array $response
	 * @return mixed null to continue, or IXR_Error/WP_Error/string to interrupt
	 */
	public function gate_request( $response = null ) {
		// If disabled, short-circuit (safety; xmlrpc_enabled already covers it).
		if ( ! $this->xmlrpc_enabled( true ) ) {
			return $this->error( 403, __( 'XML-RPC is disabled on this site.', 'theme-seo-core' ) );
		}

		$ip = $this->client_ip();

		// Allowlist check
		$allow = array_map( 'trim', array_map( 'strval', (array) ( $this->cfg['allowlist_ips'] ?? [] ) ) );
		$allow = array_values( array_filter( $allow ) );
		if ( ! empty( $allow ) && ! in_array( $ip, $allow, true ) ) {
			return $this->error( 403, __( 'XML-RPC access is restricted for this site.', 'theme-seo-core' ) );
		}

		// Rate limit (per IP per minute)
		$rl = (array) ( $this->cfg['rate_limit'] ?? [] );
		if ( ! empty( $rl['enabled'] ) ) {
			$max = max( 1, (int) ( $rl['max_per_minute'] ?? 60 ) );
			$key = 'tsc_xmlrpc_' . md5( $ip . gmdate( 'Y-m-d-H-i' ) ); // unique per minute
			$hits = (int) get_transient( $key );
			if ( $hits >= $max ) {
				return $this->error( 429, __( 'Too many requests, slow down.', 'theme-seo-core' ) );
			}
			set_transient( $key, $hits + 1, 90 ); // 90s TTL to cover window drift
		}

		return $response; // let WP continue
	}

	/* ---------------- Surface area cleanup ---------------- */

	/**
	 * Drop X-Pingback header (reveals xmlrpc endpoint).
	 *
	 * @param array<string,string> $headers
	 * @return array<string,string>
	 */
	public function strip_pingback_header( array $headers ): array {
		foreach ( array_keys( $headers ) as $k ) {
			if ( 0 === strcasecmp( $k, 'X-Pingback' ) ) {
				unset( $headers[ $k ] );
			}
		}
		return $headers;
	}

	/**
	 * If disabled, block direct hits to /xmlrpc.php early with a clear 403.
	 */
	public function maybe_block_endpoint(): void {
		if ( $this->xmlrpc_enabled( true ) ) {
			return;
		}
		$req = $_SERVER['REQUEST_URI'] ?? '';
		if ( is_string( $req ) && false !== stripos( $req, 'xmlrpc.php' ) ) {
			status_header( 403 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo "XML-RPC is disabled on this site.\n";
			exit;
		}
	}

	/* ---------------- helpers ---------------- */

	protected function client_ip(): string {
		// Basic/defensive client IP retrieval.
		$keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
		foreach ( $keys as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) { // phpcs:ignore WordPress.Security
				$raw = (string) $_SERVER[ $k ]; // phpcs:ignore
				$ip  = trim( current( explode( ',', $raw ) ) );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	/**
	 * Build an IXR_Error if available, else WP_Error/string.
	 *
	 * @param int    $code
	 * @param string $message
	 * @return mixed
	 */
	protected function error( int $code, string $message ) {
		if ( class_exists( '\IXR_Error' ) ) {
			return new \IXR_Error( $code, $message );
		}
		if ( function_exists( 'is_wp_error' ) ) {
			return new \WP_Error( $code, $message );
		}
		return $message;
	}
}

