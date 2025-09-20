<?php
namespace ThemeSeoCore\Modules\ImageSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AltGenerator — builds human-friendly alt text for images.
 *
 * Priority sources:
 *   1) Existing _wp_attachment_image_alt (respected elsewhere; used as fallback here)
 *   2) Attachment title (cleaned) or caption
 *   3) Filename (without extension, de-slugified)
 *   4) Parent post title (as a last resort)
 *
 * Output:
 *   - Sentence-cased
 *   - No file extensions/hashy bits
 *   - Max length 120 chars (filterable)
 */
class AltGenerator {

	/**
	 * Generate alt text for an attachment id.
	 *
	 * @param int $attachment_id
	 * @return string
	 */
	public function generate_for_attachment( int $attachment_id ): string {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return '';
		}
		$mime = get_post_mime_type( $attachment );
		if ( ! is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
			return '';
		}

		// 1) Existing alt (if present).
		$existing = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( is_string( $existing ) && '' !== trim( $existing ) ) {
			return $this->shape( $existing );
		}

		// 2) Caption or title.
		$candidate = $attachment->post_excerpt ?: $attachment->post_title;
		if ( $candidate ) {
			return $this->shape( $candidate );
		}

		// 3) Filename.
		$file = get_attached_file( $attachment_id );
		if ( is_string( $file ) && $file !== '' ) {
			$base = wp_basename( $file );
			$name = preg_replace( '/\.[a-z0-9]+$/i', '', $base );
			$name = preg_replace( '/[_\-]+/', ' ', $name );
			$name = preg_replace( '/\b(?:img|image|photo|pic|dsc|screen|screenshot|final|copy|\d{6,})\b/i', '', $name );
			$name = trim( preg_replace( '/\s{2,}/', ' ', $name ) );
			if ( $name ) {
				return $this->shape( $name );
			}
		}

		// 4) Parent post/page name.
		if ( $attachment->post_parent ) {
			$parent = get_post( (int) $attachment->post_parent );
			if ( $parent ) {
				return $this->shape( get_the_title( $parent ) );
			}
		}

		return '';
	}

	/* ---------------- helpers ---------------- */

	/**
	 * Normalize to sentence case and clip gracefully.
	 */
	protected function shape( string $text ): string {
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		// Sentence-case if looks like a slug or all-caps.
		if ( preg_match( '/^[a-z0-9\-\_ ]+$/', $text ) || strtoupper( $text ) === $text ) {
			$text = ucwords( strtolower( $text ) );
		}

		$max = (int) apply_filters( 'tsc/imageseo/alt_max_length', 120 );
		if ( mb_strlen( $text ) > $max ) {
			$cut = mb_substr( $text, 0, $max - 1 );
			$space = mb_strrpos( $cut, ' ' );
			if ( false !== $space ) {
				$cut = mb_substr( $cut, 0, $space );
			}
			$text = rtrim( $cut, '.,;:–-|•' ) . '…';
		}

		/**
		 * Final filter for alt text.
		 *
		 * @param string $text
		 */
		return (string) apply_filters( 'tsc/imageseo/alt', $text );
	}
}

