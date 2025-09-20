<?php
namespace ThemeSeoCore\Modules\LinkManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ExternalRel
 *
 * Adds rel="nofollow|sponsored|ugc" to outbound links in post content/comments
 * using simple rules stored in tsc_settings['links'] (optional).
 *
 * Settings shape (optional):
 *   tsc_settings => [
 *     'links' => [
 *       'nofollow_external' => true,
 *       'nofollow_whitelist'=> ['example.com','twitter.com'],
 *       'sponsored_domains' => ['aff.example.com','partner.example'],
 *       'add_noopener'      => true,
 *     ]
 *   ]
 */
class ExternalRel {

	public function filter_content( string $html ): string {
		return $this->process_html( $html, array( 'context' => 'post' ) );
	}

	public function filter_comment( string $html ): string {
		return $this->process_html( $html, array( 'context' => 'comment', 'force_ugc' => true ) );
	}

	/* ---------------------- core ---------------------- */

	protected function process_html( string $html, array $opts = array() ): string {
		if ( trim( $html ) === '' || false === stripos( $html, '<a ' ) ) {
			return $html;
		}

		$rules = $this->rules();

		// Load DOM safely.
		$dom = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );
		$dom->loadHTML( '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$anchors = $dom->getElementsByTagName( 'a' );
		$site_host = parse_url( home_url( '/' ), PHP_URL_HOST );

		foreach ( $anchors as $a ) {
			/** @var \DOMElement $a */
			$href = $a->getAttribute( 'href' );
			if ( ! $href || 0 === strpos( $href, '#' ) || 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) ) {
				continue;
			}

			$host = parse_url( $href, PHP_URL_HOST );
			if ( ! $host ) {
				// relative → internal
				continue;
			}

			$is_external = ( $site_host && strtolower( $host ) !== strtolower( $site_host ) );
			if ( ! $is_external ) {
				continue;
			}

			// Whitelist?
			if ( $this->is_whitelisted( $host, $rules['nofollow_whitelist'] ) ) {
				continue;
			}

			// Build rel set
			$rel = array_map( 'strtolower', array_filter( preg_split( '/\s+/', (string) $a->getAttribute( 'rel' ) ) ?: array() ) );

			if ( ! empty( $rules['nofollow_external'] ) ) {
				$rel[] = 'nofollow';
			}

			if ( $this->domain_in( $host, $rules['sponsored_domains'] ) ) {
				$rel[] = 'sponsored';
			}

			if ( ! empty( $opts['force_ugc'] ) ) {
				$rel[] = 'ugc';
			}

			// noopener for target=_blank (safety)
			if ( ! empty( $rules['add_noopener'] ) ) {
				$target = strtolower( (string) $a->getAttribute( 'target' ) );
				if ( '_blank' === $target ) {
					$rel[] = 'noopener';
					$rel[] = 'noreferrer';
				}
			}

			$rel = array_values( array_unique( $rel ) );
			if ( $rel ) {
				$a->setAttribute( 'rel', implode( ' ', $rel ) );
			}
		}

		$out = $dom->saveHTML();
		// Strip the meta shim.
		$out = preg_replace( '#^<meta[^>]+>\s*#', '', $out );
		return $out ?: $html;
	}

	protected function rules(): array {
		$settings = (array) get_option( 'tsc_settings', array() );
		$links    = isset( $settings['links'] ) && is_array( $settings['links'] ) ? $settings['links'] : array();
		return array(
			'nofollow_external' => ! empty( $links['nofollow_external'] ),
			'nofollow_whitelist'=> array_map( 'strtolower', array_filter( (array) ( $links['nofollow_whitelist'] ?? array() ) ) ),
			'sponsored_domains' => array_map( 'strtolower', array_filter( (array) ( $links['sponsored_domains'] ?? array() ) ) ),
			'add_noopener'      => array_key_exists( 'add_noopener', $links ) ? (bool) $links['add_noopener'] : true,
		);
	}

	protected function is_whitelisted( string $host, array $whitelist ): bool {
		if ( empty( $whitelist ) ) return false;
		$host = strtolower( $host );
		foreach ( $whitelist as $w ) {
			$w = ltrim( strtolower( $w ), '*. ' );
			if ( $host === $w || str_ends_with( $host, '.' . $w ) ) {
				return true;
			}
		}
		return false;
	}

	protected function domain_in( string $host, array $list ): bool {
		if ( empty( $list ) ) return false;
		$host = strtolower( $host );
		foreach ( $list as $w ) {
			$w = ltrim( strtolower( $w ), '*. ' );
			if ( $host === $w || str_ends_with( $host, '.' . $w ) ) {
				return true;
			}
		}
		return false;
	}
}

