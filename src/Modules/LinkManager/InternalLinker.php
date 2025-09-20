<?php
namespace ThemeSeoCore\Modules\LinkManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InternalLinker
 *
 * Generates internal link suggestions for a post based on:
 * - Content keywords (title + content, minus stopwords)
 * - Taxonomy terms overlap
 * - Freshness (slight boost to newer posts)
 *
 * Returns array of: [ ['post_id'=>ID, 'title'=>..., 'url'=>..., 'score'=>float], ... ]
 */
class InternalLinker {

	/**
	 * Suggest internal links for a post.
	 *
	 * @param int $post_id
	 * @param int $limit
	 * @return array<int,array<string,mixed>>
	 */
	public function suggest( int $post_id, int $limit = 10 ): array {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return array();
		}

		$keywords = $this->extract_keywords( $post );
		if ( empty( $keywords ) ) {
			return array();
		}

		// Gather candidate posts (exclude current), public types only.
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );

		$q = new \WP_Query( array(
			'post_type'      => array_values( $types ),
			'post_status'    => 'publish',
			'posts_per_page' => 200, // limit candidates for speed
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			's'              => '', // we score manually
			'fields'         => 'ids',
		) );

		$scores = array();
		foreach ( $q->posts as $id ) {
			$scores[ $id ] = $this->score_post( (int) $id, $keywords, $post );
		}
		wp_reset_postdata();

		// Keep top N
		arsort( $scores, SORT_NUMERIC );
		$top = array_slice( $scores, 0, max( 1, $limit ), true );

		$out = array();
		foreach ( $top as $id => $score ) {
			if ( $score <= 0 ) {
				continue;
			}
			$out[] = array(
				'post_id' => (int) $id,
				'title'   => get_the_title( $id ),
				'url'     => get_permalink( $id ),
				'score'   => round( (float) $score, 3 ),
			);
		}

		/**
		 * Filter final suggestions list.
		 *
		 * @param array $out
		 * @param int   $post_id
		 */
		return (array) apply_filters( 'tsc/linker/suggestions', $out, $post_id );
	}

	/* ---------------- scoring ---------------- */

	protected function score_post( int $id, array $keywords, \WP_Post $source ): float {
		$title  = strtolower( wp_strip_all_tags( get_the_title( $id ) ) );
		$excerpt= strtolower( wp_strip_all_tags( get_the_excerpt( $id ) ) );
		$terms  = $this->terms_for_post( $id );

		// Keyword hit score
		$score = 0.0;
		foreach ( $keywords as $kw => $w ) {
			if ( false !== strpos( $title, $kw ) ) {
				$score += 4 * $w;
			}
			if ( false !== strpos( $excerpt, $kw ) ) {
				$score += 1.5 * $w;
			}
		}

		// Taxonomy overlap bonus
		$src_terms = $this->terms_for_post( $source->ID );
		$overlap = array_intersect( $terms, $src_terms );
		$score += count( $overlap ) * 2.0;

		// Freshness boost (newer gets + up to 3)
		$age_days = max( 1, ( time() - get_post_time( 'U', true, $id ) ) / DAY_IN_SECONDS );
		$score += max( 0, 3.0 - log( $age_days ) );

		// Penalty: very short or media-only posts
		$content_len = strlen( wp_strip_all_tags( get_post_field( 'post_content', $id ) ) );
		if ( $content_len < 200 ) {
			$score -= 1.0;
		}

		return $score;
	}

	protected function terms_for_post( int $id ): array {
		$terms = array();
		foreach ( get_taxonomies( array( 'public' => true ) ) as $tax ) {
			$t = get_the_terms( $id, $tax );
			if ( ! is_wp_error( $t ) && is_array( $t ) ) {
				foreach ( $t as $term ) {
					$terms[] = strtolower( $term->slug );
				}
			}
		}
		return array_values( array_unique( $terms ) );
	}

	/**
	 * Extract lightweight keyword map from a post (kw => weight).
	 */
	protected function extract_keywords( \WP_Post $post ): array {
		$text = strtolower( wp_strip_all_tags( $post->post_title . ' ' . $post->post_content ) );
		$text = preg_replace( '/[^\p{L}\p{N}\s\-]+/u', ' ', $text );
		$parts= preg_split( '/\s+/', $text ) ?: array();

		$stop = $this->stopwords();
		$freq = array();
		foreach ( $parts as $p ) {
			$p = trim( $p, "-'" );
			if ( $p === '' || isset( $stop[ $p ] ) || mb_strlen( $p ) < 3 ) {
				continue;
			}
			$freq[ $p ] = ($freq[ $p ] ?? 0) + 1;
		}

		// Normalize to weights 0..1, keep top 40
		arsort( $freq );
		$freq = array_slice( $freq, 0, 40, true );
		$max  = max( 1, reset( $freq ) ?: 1 );
		return array_map( fn( $v ) => $v / $max, $freq );
	}

	protected function stopwords(): array {
		$list = array(
			'the','and','for','you','your','are','with','that','this','from','have','not','but','was','were','has',
			'how','why','what','when','who','which','can','all','any','into','out','more','less','over','under',
			'our','their','them','they','his','her','she','him','its','about','also','just','like','than','then',
			'will','would','should','could','there','here','one','two','three','into','onto','via','per','each',
		);
		return array_fill_keys( $list, true );
	}
}

