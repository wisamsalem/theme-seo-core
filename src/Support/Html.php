<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal HTML tag builder with safe escaping.
 */
class Html {

	/**
	 * Build a self-closing tag.
	 *
	 * @param string               $name
	 * @param array<string,mixed>  $attrs
	 * @return string
	 */
	public static function void( string $name, array $attrs = array() ): string {
		return '<' . esc_attr( $name ) . ' ' . self::attrs( $attrs ) . ' />';
	}

	/**
	 * Build a normal tag with content.
	 *
	 * @param string               $name
	 * @param string               $content
	 * @param array<string,mixed>  $attrs
	 * @return string
	 */
	public static function tag( string $name, string $content = '', array $attrs = array() ): string {
		return sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			esc_attr( $name ),
			self::attrs( $attrs ),
			$content
		);
	}

	/**
	 * Build a link tag rel=... href=...
	 *
	 * @param string $rel
	 * @param string $href
	 * @param array<string,mixed> $extra
	 * @return string
	 */
	public static function link( string $rel, string $href, array $extra = array() ): string {
		$attrs = array_merge( array( 'rel' => $rel, 'href' => esc_url_raw( $href ) ), $extra );
		return self::void( 'link', $attrs );
	}

	/**
	 * Convert attributes array to HTML.
	 *
	 * @param array<string,mixed> $attrs
	 * @return string
	 */
	public static function attrs( array $attrs ): string {
		$out = array();

		foreach ( $attrs as $k => $v ) {
			if ( null === $v || false === $v ) {
				continue;
			}

			$key = esc_attr( $k );

			if ( true === $v ) {
				$out[] = $key;
				continue;
			}

			if ( is_array( $v ) ) {
				$v = implode( ' ', array_map( 'sanitize_html_class', $v ) );
			}

			if ( 'href' === $k || 'src' === $k ) {
				$out[] = sprintf( '%s="%s"', $key, esc_url( (string) $v ) );
			} else {
				$out[] = sprintf( '%s="%s"', $key, esc_attr( (string) $v ) );
			}
		}

		return implode( ' ', $out );
	}
}

