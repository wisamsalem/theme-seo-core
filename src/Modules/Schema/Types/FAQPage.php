<?php
namespace ThemeSeoCore\Modules\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FAQPage schema — sourced from Gutenberg FAQ block ([tsc/faq]) or filter.
 */
class FAQPage {

	/**
	 * Build from block data in current post content or via filter.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function maybe_build() {
		if ( ! is_singular() ) {
			return null;
		}

		$post = get_post( get_queried_object_id() );
		if ( ! $post ) {
			return null;
		}

		$items = apply_filters( 'tsc/schema/faq/items', self::extract_from_content( $post->post_content ), $post );

		$main = [];
		foreach ( $items as $it ) {
			$q = trim( wp_strip_all_tags( $it['q'] ?? '' ) );
			$a = trim( $it['a'] ?? '' );
			if ( $q && $a ) {
				$main[] = [
					'@type' => 'Question',
					'name'  => $q,
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $a, // answers may include basic HTML according to Google
					],
				];
			}
		}

		if ( empty( $main ) ) {
			return null;
		}

		return [
			'@type'      => 'FAQPage',
			'@id'        => get_permalink( $post ) . '#faq',
			'mainEntity' => $main,
		];
	}

	/**
	 * Very light parser for our block’s JSON comment.
	 *
	 * @param string $content
	 * @return array<int,array{q:string,a:string}>
	 */
	protected static function extract_from_content( string $content ): array {
		if ( ! has_block( 'tsc/faq', $content ) ) {
			return [];
		}

		$items = [];

		$blocks = parse_blocks( $content );
		$walk = function( $blocks ) use ( & $walk, & $items ) {
			foreach ( $blocks as $b ) {
				if ( ! empty( $b['blockName'] ) && 'tsc/faq' === $b['blockName'] ) {
					$attrs = $b['attrs'] ?? [];
					foreach ( ( $attrs['items'] ?? [] ) as $it ) {
						$items[] = [
							'q' => (string) ( $it['q'] ?? '' ),
							'a' => (string) ( $it['a'] ?? '' ),
						];
					}
				}
				if ( ! empty( $b['innerBlocks'] ) ) {
					$walk( $b['innerBlocks'] );
				}
			}
		};
		$walk( $blocks );

		return $items;
	}
}

