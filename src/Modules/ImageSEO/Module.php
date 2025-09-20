<?php
namespace ThemeSeoCore\Modules\ImageSEO;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image SEO Module
 *
 * - Generates sensible alt text on upload if missing.
 * - Injects alt text when rendering <img> if still empty.
 * - Sanitizes filenames on upload.
 */
class Module extends BaseModule {

	protected static $slug = 'imageseo';
	protected static $title = 'Image SEO';
	protected static $description = 'Auto alt text and better filenames for uploaded images.';

	/** @var AltGenerator */
	protected $alts;

	/** @var FilenameSanitizer */
	protected $names;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->alts  = new AltGenerator();
		$this->names = new FilenameSanitizer();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// 1) Filenames: sanitize during upload.
			'sanitize_file_name' => [ [ $this, 'sanitize_file_name' ], 10, 2, 'filter' ],

			// 2) On attachment creation: set _wp_attachment_image_alt if empty.
			'add_attachment' => 'maybe_set_initial_alt',

			// 3) When WP renders <img>, ensure an alt is present.
			'wp_get_attachment_image_attributes' => [ [ $this, 'inject_alt_when_missing' ], 10, 3, 'filter' ],
		];
	}

	/* ---------------- File names ---------------- */

	/**
	 * Filter: sanitize uploaded file names (images only by default).
	 *
	 * @param string $filename
	 * @param string $rawname
	 * @return string
	 */
	public function sanitize_file_name( string $filename, string $rawname ): string {
		if ( ! $this->enabled() ) {
			return $filename;
		}
		return $this->names->sanitize( $filename );
	}

	/* ---------------- Alt text on upload ---------------- */

	/**
	 * When a new attachment is created, set alt if it's an image and the alt is empty.
	 *
	 * @param int $attachment_id
	 * @return void
	 */
	public function maybe_set_initial_alt( int $attachment_id ): void {
		if ( ! $this->enabled() ) {
			return;
		}
		$mime = get_post_mime_type( $attachment_id );
		if ( ! is_string( $mime ) || strpos( $mime, 'image/' ) !== 0 ) {
			return;
		}

		$existing = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( '' !== (string) $existing ) {
			return; // respect user-provided alt
		}

		$alt = $this->alts->generate_for_attachment( $attachment_id );
		if ( $alt !== '' ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}
	}

	/* ---------------- Alt text at render time ---------------- */

	/**
	 * Ensure <img> has an alt attribute when rendered.
	 *
	 * @param array        $attr
	 * @param \WP_Post     $attachment
	 * @param string|array $size
	 * @return array
	 */
	public function inject_alt_when_missing( array $attr, $attachment, $size ): array {
		if ( ! $this->enabled() ) {
			return $attr;
		}

		$has = isset( $attr['alt'] ) ? trim( (string) $attr['alt'] ) : '';
		if ( '' !== $has ) {
			return $attr;
		}

		$alt = $this->alts->generate_for_attachment( (int) $attachment->ID );
		if ( $alt !== '' ) {
			$attr['alt'] = $alt;
		}

		return $attr;
	}
}

