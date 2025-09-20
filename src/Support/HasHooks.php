<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait to map class methods to WP actions/filters declaratively.
 *
 * Example:
 * protected function hooks(): array {
 *   return [
 *     'init'                          => 'on_init',
 *     'wp_head'                       => ['render_head', 20],
 *     ['the_content', 'filter_body']  => [ [$this, 'filter_body'], 10, 1 ],
 *   ];
 * }
 *
 * $this->register_hooks(); // usually called in register()/boot()
 */
trait HasHooks {

	/**
	 * Return an array describing the hooks → handlers map.
	 *
	 * @return array<int|string,mixed>
	 */
	protected function hooks(): array {
		return array();
	}

	/**
	 * Register all declared hooks.
	 *
	 * @return void
	 */
	protected function register_hooks(): void {
		foreach ( $this->hooks() as $hook => $handler ) {
			// Support numeric keys with tuple: [hook, callable, priority, args]
			if ( is_int( $hook ) && is_array( $handler ) && ! isset( $handler[1] ) ) {
				// e.g. [ 'init', 'on_init' ]
				$hook    = $handler[0] ?? null;
				$handler = $handler[1] ?? null;
			}

			if ( ! $hook || ! $handler ) {
				continue;
			}

			$priority = 10;
			$args     = 1;
			$type     = 'action';

			if ( is_array( $handler ) ) {
				// [callback, priority?, args?, type?]
				$callback = $handler[0] ?? null;
				if ( ! $callback ) {
					continue;
				}
				$priority = isset( $handler[1] ) ? (int) $handler[1] : 10;
				$args     = isset( $handler[2] ) ? (int) $handler[2] : 1;
				$type     = isset( $handler[3] ) ? (string) $handler[3] : 'action';
			} else {
				$callback = array( $this, (string) $handler );
			}

			if ( 'filter' === $type ) {
				add_filter( (string) $hook, $callback, $priority, $args );
			} else {
				add_action( (string) $hook, $callback, $priority, $args );
			}
		}
	}
}

