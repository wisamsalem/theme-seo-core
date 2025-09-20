<?php
namespace ThemeSeoCore\Modules\ThemeOptimization;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Removes common noise from <head>.
 */
class CleanHead {

	public function register(): void {
		add_action( 'init', [ $this, 'strip' ] );
	}

	public function strip(): void {
		// RSD, WLW, shortlink, generator, emoji, oEmbed discovery, REST link
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );

		// Feeds (keep if you rely on them)
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );

		// Emojis
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );

		// Remove oEmbed wp-json proxy endpoint?
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Remove DNS prefetch for emojis
		add_filter( 'wp_resource_hints', function( $urls, $relation ) {
			if ( 'dns-prefetch' === $relation ) {
				$urls = array_filter( (array) $urls, fn( $u ) => false === strpos( (string) $u, 's.w.org' ) );
			}
			return $urls;
		}, 10, 2 );
	}
}

