<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around WP conditionals for testability and central logic.
 */
class Conditionals {

	/**
	 * True when current query is a singular public post type (post/page/cpt).
	 *
	 * @return bool
	 */
	public function is_content(): bool {
		return ( is_singular() && ! is_front_page() ) || is_attachment();
	}

	/**
	 * True on the front page (static or posts).
	 *
	 * @return bool
	 */
	public function is_home_like(): bool {
		return is_front_page() || is_home();
	}

	/**
	 * True on search results.
	 *
	 * @return bool
	 */
	public function is_search(): bool {
		return is_search();
	}

	/**
	 * True on taxonomy archives (category, tag, custom tax).
	 *
	 * @return bool
	 */
	public function is_tax_like(): bool {
		return is_category() || is_tag() || is_tax();
	}

	/**
	 * True on author archives.
	 *
	 * @return bool
	 */
	public function is_author(): bool {
		return is_author();
	}

	/**
	 * True on paginated views (page 2+).
	 *
	 * @return bool
	 */
	public function is_paged(): bool {
		return is_paged();
	}
}

