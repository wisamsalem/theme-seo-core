<?php
namespace ThemeSeoCore\Modules\ImageSEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FilenameSanitizer — normalizes uploaded filenames:
 *   - remove accents (e.g., "café" → "cafe")
 *   - to lowercase
 *   - replace spaces/underscores with hyphens
 *   - keep only [a-z0-9-_.]
 *   - collapse duplicate hyphens
 *   - trim hyphens from ends
 *
 * Applies to all files by default; restrict to images via filter if desired.
 */
class FilenameSanitizer {

	/**
	 * Sanitize a file name string. Keep extension intact.
	 *
	 * @param string $filename Full filename including extension.
	 * @return string
	 */
	public function sanitize( string $filename ): string {
		$ext = '';
		$name = $filename;

		if ( false !== ($dot = strrpos( $filename, '.' )) ) {
			$ext  = substr( $filename, $dot + 1 );
			$name = substr( $filename, 0, $dot );
		}

		// Remove accents and lower-case.
		$name = remove_accents( $name );
		$name = strtolower( $name );
		$ext  = strtolower( $ext );

		// Replace separators with hyphens.
		$name = str_replace( array( ' ', '_', '+' ), '-', $name );

		// Strip anything not alnum, dot, or hyphen.
		$name = preg_replace( '/[^a-z0-9\.\-]/', '', $name );

		// Collapse multiple dots (avoid hidden files / path issues).
		$name = preg_replace( '/\.{2,}/', '.', $name );

		// Collapse multiple hyphens.
		$name = preg_replace( '/\-{2,}/', '-', $name );

		// Trim leading/trailing dots/hyphens.
		$name = trim( $name, '.-' );

		// Rebuild with extension if present.
		$out = $ext ? "{$name}.{$ext}" : $name;

		/**
		 * Filter final sanitized filename.
		 *
		 * @param string $out
		 * @param string $filename
		 */
		return (string) apply_filters( 'tsc/imageseo/sanitized_filename', $out, $filename );
	}
}

