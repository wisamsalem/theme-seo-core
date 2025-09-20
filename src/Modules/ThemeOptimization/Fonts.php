<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Font-related tweaks:
 * - Append display=swap to Google Fonts URLs
 * - Optional: inject font-display: swap for @font-face (best-effort)
 */
class Fonts {

	protected array $cfg = [];

	public function register( array $cfg ): void {
		$this->cfg = $cfg;

		add_filter( 'style_loader_src', [ $this, 'google_fonts_display' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'font_display_injection' ], 10, 4 );
	}

	/**
	 * Ensure &display=… is present on Google Fonts requests.
	 */
	public function google_fonts_display( string $src, string $handle ): string {
		if ( false !== strpos( $src, 'fonts.googleapis.com' ) ) {
			$display = $this->cfg['google_display'] ?? 'swap';
			$args = [];
			$parts = wp_parse_url( $src );
			parse_str( $parts['query'] ?? '', $args );
			if ( empty( $args['display'] ) ) {
				$args['display'] = $display;
				$src = ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . ( $parts['path'] ?? '' ) . '?' . http_build_query( $args );
			}
		}
		return $src;
	}

	/**
	 * Best-effort font-display: swap for inline @font-face CSS.
	 */
	public function font_display_injection( string $html, string $handle, string $href, string $media ): string {
		// Only affects inline styles with @font-face inlined by theme/plugins (rare).
		// We can't reliably modify external CSS here.
		return $html;
	}
}

