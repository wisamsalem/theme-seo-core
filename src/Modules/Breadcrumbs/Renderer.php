<?php
namespace ThemeSeoCore\Modules\Breadcrumbs;

use ThemeSeoCore\Support\Html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer
 *
 * Turns a trail array into accessible HTML:
 * <nav aria-label="Breadcrumbs"><ol>…</ol></nav>
 */
class Renderer {

	/** @var TrailBuilder */
	protected $builder;

	public function __construct( TrailBuilder $builder ) {
		$this->builder = $builder;
	}

	/**
	 * Render breadcrumbs HTML.
	 *
	 * @param array{
	 *   separator?: string,
	 *   home_label?: string,
	 *   include_posts_page?: bool,
	 *   include_cpt_archive?: bool,
	 *   class?: string
	 * } $args
	 * @return string
	 */
	public function render( array $args = array() ): string {
		$defaults = array(
			'separator'           => '›',
			'home_label'          => get_bloginfo( 'name' ),
			'include_posts_page'  => true,
			'include_cpt_archive' => true,
			'class'               => 'tsc-breadcrumbs',
		);
		$args = wp_parse_args( $args, $defaults );

		$trail = $this->builder->build( $args );

		/**
		 * Filter the breadcrumb trail before rendering.
		 *
		 * @param array $trail
		 * @param array $args
		 */
		$trail = (array) apply_filters( 'tsc/breadcrumbs/trail', $trail, $args );

		// If there's only "Home", skip output unless explicitly allowed.
		if ( count( $trail ) <= 1 && ! is_singular() ) {
			return '';
		}

		// Mark current item (last) as current and strip URL.
		$last_index = count( $trail ) - 1;
		if ( $last_index >= 0 ) {
			$trail[ $last_index ]['current'] = true;
			$trail[ $last_index ]['url']     = '';
		}

		$items_html = array();
		foreach ( $trail as $i => $node ) {
			$title   = esc_html( $node['title'] ?? '' );
			$url     = esc_url( $node['url'] ?? '' );
			$current = ! empty( $node['current'] );

			if ( $current ) {
				$items_html[] = sprintf(
					'<li class="current" aria-current="page"><span>%s</span></li>',
					$title
				);
			} else {
				$items_html[] = sprintf(
					'<li><a href="%s">%s</a></li>',
					$url,
					$title
				);
			}
		}

		$sep = esc_html( $args['separator'] );
		$html = sprintf(
			'<nav class="%1$s" aria-label="%2$s"><ol>%3$s</ol></nav>',
			esc_attr( $args['class'] ),
			esc_attr__( 'Breadcrumbs', 'theme-seo-core' ),
			implode( "<li class=\"sep\" aria-hidden=\"true\">{$sep}</li>", $items_html )
		);

		/**
		 * Filter the final breadcrumbs HTML.
		 *
		 * @param string $html
		 * @param array  $trail
		 * @param array  $args
		 */
		return (string) apply_filters( 'tsc/breadcrumbs/html', $html, $trail, $args );
	}
}

