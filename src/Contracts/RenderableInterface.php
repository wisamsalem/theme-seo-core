<?php
namespace ThemeSeoCore\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for classes that render HTML (views, shortcodes, blocks server-side).
 */
interface RenderableInterface {

	/**
	 * Render HTML as a string (preferred for testability and output buffering).
	 *
	 * @param array<string,mixed> $data Optional data/context for the view.
	 * @return string HTML
	 */
	public function render( array $data = array() ): string;
}

