<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ThemeOptimization\Images
 *
 * Adds modern image attributes to post content safely.
 * - Adds loading="lazy" when enabled and missing
 * - Adds decoding="async|auto" when enabled and missing
 * - NEVER breaks content if HTML is malformed (graceful fallback)
 */
class Images {

	/** @var array */
	protected $cfg = [];

	/**
	 * Register hooks. $cfg example:
	 * [
	 *   'add_lazy'     => true,
	 *   'add_decoding' => 'async', // 'async' or 'auto'
	 * ]
	 */
	public function register( array $cfg = [] ): void {
		$this->cfg = $cfg;

		// Only affect front-end post content. Reasonable priority so it runs
		// after shortcodes but before heavy minifiers.
		if ( ! is_admin() ) {
			add_filter( 'the_content', [ $this, 'content_img_attrs' ], 30 );
		}
	}

	/**
	 * Parse <img> in content and add loading/decoding when missing.
	 * Safe on malformed HTML. Avoids regex stripping; extracts <body> children.
	 *
	 * @param string $html
	 * @return string
	 */
	public function content_img_attrs( string $html ): string {
		// Fast bail: nothing to do.
		if ( stripos( $html, '<img' ) === false ) {
			return $html;
		}

		// Safety: skip in non-HTML contexts.
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $html;
		}

		$cfg          = is_array( $this->cfg ?? null ) ? $this->cfg : [];
		$add_lazy     = ! empty( $cfg['add_lazy'] );
		$dec_pref_raw = isset( $cfg['add_decoding'] ) ? (string) $cfg['add_decoding'] : '';
		$dec_pref     = in_array( $dec_pref_raw, [ 'async', 'auto' ], true ) ? $dec_pref_raw : '';

		// DOM extension required.
		if ( ! class_exists( '\DOMDocument' ) ) {
			// Server missing ext-dom / php-xml; don't break output.
			return $html;
		}

		$doc  = new \DOMDocument();
		$prev = libxml_use_internal_errors( true );

		// Wrap fragment so DOMDocument can parse reliably; we’ll extract <body>.
		$wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
		$loaded  = $doc->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Reset libxml state.
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		// If parse failed, return the original HTML (no crash).
		if ( ! $loaded ) {
			return $html;
		}

		// Mutate <img> nodes (snapshot because DOMNodeList is live).
		$imgs = $doc->getElementsByTagName( 'img' );
		$list = [];
		foreach ( $imgs as $img ) {
			$list[] = $img;
		}
		foreach ( $list as $img ) {
			/** @var \DOMElement $img */
			if ( $add_lazy && ! $img->hasAttribute( 'loading' ) ) {
				$img->setAttribute( 'loading', 'lazy' );
			}
			if ( $dec_pref !== '' && ! $img->hasAttribute( 'decoding' ) ) {
				$img->setAttribute( 'decoding', $dec_pref );
			}
		}

		// Extract only the body’s children to avoid leftover head/meta markup.
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $body ) {
			return $html;
		}

		$out = '';
		foreach ( $body->childNodes as $child ) {
			$out .= $doc->saveHTML( $child );
		}

		return $out !== '' ? $out : $html;
	}
}
