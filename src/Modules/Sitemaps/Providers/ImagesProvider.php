<?php
namespace ThemeSeoCore\Modules\Sitemaps\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides <url> entries for recent posts + image:image children.
 * Not a full image sitemap (keeps it simple/light).
 */
class ImagesProvider {

	public function page( int $page ): array {
		$per_page = (int) apply_filters( 'tsc/sitemaps/images/per_page', 100 );

		$q = new \WP_Query( array(
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'no_found_rows'  => false,
		) );

		$total_pages = (int) max( 1, $q->max_num_pages );
		$entries = array();

		while ( $q->have_posts() ) {
			$q->the_post();
			$post_id = get_the_ID();
			$loc     = get_permalink( $post_id );
			$images  = $this->collect_images( $post_id );
			$entries[] = array(
				'loc'     => $loc,
				'images'  => $images, // array of [loc, title]
				'lastmod' => get_post_modified_time( 'c', true, $post_id ),
			);
		}
		wp_reset_postdata();

		return compact( 'entries', 'total_pages', 'page' ) + array( 'current_page' => $page );
	}

	protected function collect_images( int $post_id ): array {
		$out = array();

		// Featured image first.
		$thumb = get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$src = wp_get_attachment_image_src( $thumb, 'full' );
			if ( $src ) {
				$out[] = array( 'loc' => $src[0], 'title' => get_the_title( $thumb ) );
			}
		}

		// Inline attachments in content.
		$ids = array();
		$media = get_attached_media( 'image', $post_id );
		foreach ( $media as $m ) {
			$ids[] = (int) $m->ID;
		}
		$ids = array_unique( $ids );

		foreach ( $ids as $id ) {
			if ( $id === $thumb ) continue;
			$src = wp_get_attachment_image_src( $id, 'full' );
			if ( $src ) {
				$out[] = array( 'loc' => $src[0], 'title' => get_the_title( $id ) );
			}
		}

		return $out;
	}
}

