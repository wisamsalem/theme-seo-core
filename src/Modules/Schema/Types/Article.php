<?php
namespace ThemeSeoCore\Modules\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Article/BlogPosting for singular content.
 */
class Article {

	/**
	 * Build Article schema for a post ID if suitable.
	 *
	 * @param int $post_id
	 * @return array<string,mixed>|null
	 */
	public static function maybe_build( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return null;
		}

		$type = ( 'post' === $post->post_type ) ? 'BlogPosting' : 'Article';

		$img  = get_the_post_thumbnail_url( $post_id, 'full' );
		$img  = $img ? [ [ '@type' => 'ImageObject', 'url' => $img ] ] : null;

		$author = get_the_author_meta( 'display_name', $post->post_author );
		$author_obj = $author ? [ '@type' => 'Person', 'name' => $author ] : null;

		return [
			'@type'         => $type,
			'@id'           => get_permalink( $post_id ) . '#article',
			'headline'      => wp_strip_all_tags( get_the_title( $post_id ) ),
			'datePublished' => get_post_time( 'c', false, $post ),
			'dateModified'  => get_post_modified_time( 'c', false, $post ),
			'author'        => $author_obj,
			'publisher'     => self::publisher(),
			'mainEntityOfPage' => get_permalink( $post_id ),
			'image'         => $img,
			'articleSection'=> self::section( $post ),
			'description'   => self::excerpt( $post ),
		];
	}

	protected static function publisher() {
		$name = get_bloginfo( 'name' );
		$logo = function_exists( 'has_custom_logo' ) && has_custom_logo()
			? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' )
			: ( function_exists( 'has_site_icon' ) && has_site_icon() ? get_site_icon_url( 512 ) : '' );

		return [
			'@type' => 'Organization',
			'name'  => $name,
			'logo'  => $logo ? [ '@type' => 'ImageObject', 'url' => $logo ] : null,
		];
	}

	protected static function section( \WP_Post $post ): string {
		if ( 'post' !== $post->post_type ) {
			return ucfirst( $post->post_type );
		}
		$cats = get_the_category( $post->ID );
		return $cats ? $cats[0]->name : 'Blog';
	}

	protected static function excerpt( \WP_Post $post ): string {
		$txt = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '…' );
		return trim( wp_strip_all_tags( $txt ) );
	}
}

