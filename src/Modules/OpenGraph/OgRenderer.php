<?php
namespace ThemeSeoCore\Modules\OpenGraph;

use ThemeSeoCore\Support\Html;
use ThemeSeoCore\Support\Url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Open Graph tags.
 */
class OgRenderer {

	/**
	 * Echo <meta property="og:*"> tags
	 */
	public function output(): void {
		$data = $this->data();

		foreach ( $data as $k => $v ) {
			if ( $v === '' || $v === null ) {
				continue;
			}
			if ( is_array( $v ) ) {
				foreach ( $v as $val ) {
					echo Html::tag( 'meta', '', [ 'property' => $k, 'content' => $val ] ) . "\n";
				}
			} else {
				echo Html::tag( 'meta', '', [ 'property' => $k, 'content' => $v ] ) . "\n";
			}
		}
	}

	/**
	 * Build OG data, filterable via `tsc/og/data`.
	 *
	 * @return array<string,mixed>
	 */
	public function data(): array {
		$title = wp_get_document_title();
		$desc  = $this->description();
		$url   = $this->url();
		$type  = is_singular( array( 'post', 'page' ) ) ? 'article' : 'website';
		$site  = get_bloginfo( 'name' );
		$img   = $this->image();

		$locale = get_locale();
		$og = [
			'og:title'       => $title,
			'og:description' => $desc,
			'og:url'         => $url,
			'og:type'        => $type,
			'og:site_name'   => $site,
			'og:image'       => $img ? [ $img ] : [],
			'og:locale'      => str_replace( '_', '-', $locale ),
		];

		/**
		 * Filter final Open Graph data.
		 *
		 * @param array $og
		 */
		return apply_filters( 'tsc/og/data', $og );
	}

	protected function description(): string {
		// Prefer existing meta description from our Meta module (already printed),
		// else fallback to excerpt/content.
		$desc = '';
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$manual  = (string) get_post_meta( $post_id, \ThemeSeoCore\Admin\MetaBox::META_DESC, true );
			if ( $manual !== '' ) {
				$desc = $manual;
			} else {
				$excerpt = get_the_excerpt( $post_id );
				$desc    = $excerpt ?: wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 30, '…' );
			}
		} else {
			$desc = get_bloginfo( 'description' );
		}
		return trim( wp_strip_all_tags( $desc ) );
	}

	protected function url(): string {
		$link = '';
		if ( is_singular() ) {
			$link = get_permalink( get_queried_object_id() );
		} elseif ( is_home() && ! is_front_page() ) {
			$link = get_permalink( (int) get_option( 'page_for_posts' ) );
		} elseif ( is_front_page() ) {
			$link = home_url( '/' );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
		} elseif ( is_post_type_archive() ) {
			$link = get_post_type_archive_link( get_post_type() );
		} else {
			$link = home_url( add_query_arg( null, null ) );
		}
		return Url::canonicalize( $link );
	}

	protected function image(): string {
		$img = '';

		// 1) Per-post featured image.
		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$img     = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
		}

		// 2) Term thumbnail (common via plugins).
		if ( ! $img && ( is_category() || is_tag() || is_tax() ) ) {
			$term = get_queried_object();
			$thumb_id = get_term_meta( $term->term_id ?? 0, 'thumbnail_id', true );
			if ( $thumb_id ) {
				$src = wp_get_attachment_image_src( (int) $thumb_id, 'full' );
				$img = $src[0] ?? '';
			}
		}

		// 3) Site icon.
		if ( ! $img && function_exists( 'has_site_icon' ) && has_site_icon() ) {
			$img = get_site_icon_url( 512 );
		}

		// 4) Plugin fallback (ship assets/images/og-default.png).
		if ( ! $img && defined( 'TSC_URL' ) ) {
			$img = TSC_URL . 'assets/images/og-default.png';
		}

		/**
		 * Filter final OG image URL.
		 *
		 * @param string $img
		 */
		return (string) apply_filters( 'tsc/og/image', $img );
	}
}

