<?php
namespace ThemeSeoCore\Integrations;

use ThemeSeoCore\Support\Url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce integration:
 * - Canonical cleanup for common WC query args (orderby, min_price, etc.)
 * - (Room to expand: product OG/Schema shims inside dedicated modules)
 */
class WooCommerce {

	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_boot' ), 12 );
	}

	public function maybe_boot(): void {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		// Clean noisy query args from canonical on shop & product taxonomy pages.
		add_filter( 'tsc/head/canonical', array( $this, 'canonical_cleanup' ), 10 );
	}

	/**
	 * Remove non-canonical WC params from canonical URL.
	 * We keep pagination intact; we strip known filter/sort params.
	 *
	 * @param string $canonical
	 * @return string
	 */
	public function canonical_cleanup( string $canonical ): string {
		if ( '' === $canonical ) {
			return $canonical;
		}

		// Only touch WC contexts.
		if ( ! ( is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy() ) ) {
			return $canonical;
		}

		$parsed = wp_parse_url( $canonical );
		if ( ! $parsed ) {
			return $canonical;
		}

		$q = array();
		if ( ! empty( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $q );
		}

		$strip = array(
			'orderby', 'min_price', 'max_price', 'rating_filter', 'filter_*',
			'onsale', 'in-stock', 'stock_status', 'pa_*', 'product-page',
			'add-to-cart'
		);

		foreach ( array_keys( $q ) as $key ) {
			$lk = strtolower( $key );
			foreach ( $strip as $mask ) {
				if ( '*' === substr( $mask, -1 ) ) {
					$prefix = rtrim( $mask, '*' );
					if ( 0 === strpos( $lk, $prefix ) ) {
						unset( $q[ $key ] );
					}
				} elseif ( $lk === $mask ) {
					unset( $q[ $key ] );
				}
			}
		}

		$rebuilt = $parsed['scheme'] . '://' . $parsed['host'] . ( isset( $parsed['path'] ) ? $parsed['path'] : '/' );
		if ( ! empty( $q ) ) {
			$rebuilt .= '?' . http_build_query( $q );
		}

		return Url::canonicalize( $rebuilt );
	}
}

