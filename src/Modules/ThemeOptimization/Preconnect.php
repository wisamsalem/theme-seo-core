<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds dns-prefetch and preconnect resource hints.
 */
class Preconnect {

	protected array $cfg = [];

	public function register( array $cfg ): void {
		$this->cfg = $cfg;

		add_filter( 'wp_resource_hints', [ $this, 'hints' ], 10, 2 );
	}

	public function hints( array $urls, string $relation ): array {
		if ( 'dns-prefetch' === $relation ) {
			$urls = array_unique( array_merge( (array) $urls, (array) ( $this->cfg['dns_prefetch'] ?? [] ) ) );
		}
		if ( 'preconnect' === $relation ) {
			$urls = array_unique( array_merge( (array) $urls, (array) ( $this->cfg['preconnect'] ?? [] ) ) );
		}
		return $urls;
	}
}

