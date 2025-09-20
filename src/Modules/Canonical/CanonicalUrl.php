<?php
namespace ThemeSeoCore\Modules\Canonical;

if ( ! defined( 'ABSPATH' ) ) exit;

class CanonicalUrl {

	public static function for_context(): string {
		if ( is_singular() ) {
			$pid = get_queried_object_id();
			$override = get_post_meta( $pid, '_tsc_canonical', true );
			if ( $override ) return esc_url_raw( $override );
			return get_permalink( $pid );
		}
		if ( is_home() || is_front_page() ) return home_url( '/' );
		if ( is_category() || is_tag() || is_tax() ) return get_term_link( get_queried_object() );
		if ( is_author() ) return get_author_posts_url( get_queried_object_id() );
		if ( is_search() ) return get_search_link();
		if ( is_post_type_archive() ) return get_post_type_archive_link( get_post_type() );
		if ( is_archive() ) return get_permalink();
		return home_url( add_query_arg( null, null ) );
	}

	public static function hook_head(): void {
		add_action( 'wp_head', function () {
			$url = self::for_context();
			if ( $url ) {
				echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
			}
		}, 4 );
	}
}
