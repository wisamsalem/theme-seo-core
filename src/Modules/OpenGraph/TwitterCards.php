<?php
namespace ThemeSeoCore\Modules\OpenGraph;

use ThemeSeoCore\Support\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Twitter Card tags.
 */
class TwitterCards {

	public function output(): void {
		$data = $this->data();

		foreach ( $data as $k => $v ) {
			if ( $v === '' || $v === null ) {
				continue;
			}
			echo Html::tag( 'meta', '', [ 'name' => $k, 'content' => $v ] ) . "\n";
		}
	}

	/**
	 * Build twitter:* data, filterable via `tsc/twitter/data`.
	 */
	protected function data(): array {
		$og   = ( new OgRenderer() )->data();
		$card = [
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => (string) ( $og['og:title'] ?? '' ),
			'twitter:description' => (string) ( $og['og:description'] ?? '' ),
			'twitter:image'       => is_array( $og['og:image'] ?? null ) ? ( $og['og:image'][0] ?? '' ) : ( $og['og:image'] ?? '' ),
			// Optional handle (set via filter or option).
			'twitter:site'        => apply_filters( 'tsc/twitter/site', '' ),
			'twitter:creator'     => apply_filters( 'tsc/twitter/creator', '' ),
		];

		/**
		 * Filter final twitter card data.
		 *
		 * @param array $card
		 */
		return apply_filters( 'tsc/twitter/data', $card );
	}
}

