<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Container\Container;
use ThemeSeoCore\Contracts\ServiceInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Columns
 *
 * Safe implementation for wp-admin list tables. Does NOT call front-end
 * generators or use conditional tags. Reads post meta directly and falls
 * back to standard WP fields.
 */
class Columns implements ServiceInterface {

	/** Post meta keys used by the plugin UIs */
	const META_TITLE = '_theme_seo_title';
	const META_DESC  = '_theme_seo_description';

	/**
	 * Register hooks (DI entrypoint).
	 *
	 * @param Container $c
	 */
	public function register( Container $c ): void {
		if ( ! is_admin() ) {
			return;
		}

		// Only wire on list table screens.
		add_action( 'load-edit.php', function () {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || empty( $screen->post_type ) ) {
				return;
			}
			$post_type = $screen->post_type;

			// Add our columns.
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_columns' ], 20 );

			// Render our column cells (support both method names).
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_column' ], 10, 2 );

			// (Optional) sortable example (left disabled by default).
			// add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_columns' ] );
			// add_action( 'pre_get_posts', [ $this, 'handle_sorting_query' ] );
		} );
	}

	/**
	 * Boot phase (required by ServiceInterface). No-op; hooks added in register().
	 *
	 * @param Container $c
	 */
	public function boot( Container $c ): void {
		// Intentionally empty.
	}

	/**
	 * Inject SEO columns after the Title column.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_columns( array $columns ): array {
		$new = [];
		$inserted = false;

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['tsc_seo_title'] = __( 'SEO Title', 'theme-seo-core' );
				$new['tsc_meta_desc'] = __( 'Meta Desc', 'theme-seo-core' );
				$inserted = true;
			}
		}

		// If 'title' wasn't present, append at the end (rare).
		if ( ! $inserted ) {
			$new['tsc_seo_title'] = __( 'SEO Title', 'theme-seo-core' );
			$new['tsc_meta_desc'] = __( 'Meta Desc', 'theme-seo-core' );
		}

		return $new;
	}

	/**
	 * Preferred cell renderer.
	 *
	 * @param string $column_name
	 * @param int    $post_id
	 */
	public function render_column( string $column_name, int $post_id ): void {
		if ( 'tsc_seo_title' === $column_name ) {
			$this->echo_cell( $this->get_seo_title_for_admin( $post_id ) );
			return;
		}
		if ( 'tsc_meta_desc' === $column_name ) {
			$this->echo_cell( $this->get_meta_desc_for_admin( $post_id ) );
			return;
		}
	}

	/**
	 * Compatibility alias if hooks were wired to ->render() previously.
	 */
	public function render( string $column_name, int $post_id ): void {
		$this->render_column( $column_name, $post_id );
	}

	/**
	 * Optional sortable columns.
	 */
	public function sortable_columns( array $columns ): array {
		$columns['tsc_seo_title'] = 'tsc_seo_title';
		// $columns['tsc_meta_desc'] = 'tsc_meta_desc'; // usually noisy; enable if needed
		return $columns;
	}

	/**
	 * Optional: handle sorting by our custom columns (meta-based).
	 */
	public function handle_sorting_query( \WP_Query $q ): void {
		if ( ! is_admin() || ! $q->is_main_query() ) {
			return;
		}
		$orderby = $q->get( 'orderby' );
		if ( 'tsc_seo_title' === $orderby ) {
			$q->set( 'meta_key', self::META_TITLE );
			$q->set( 'orderby', 'meta_value' );
		}
		// if ( 'tsc_meta_desc' === $orderby ) { ... }
	}

	/* =========================
	 * Data access (admin-safe)
	 * ========================= */

	protected function get_seo_title_for_admin( int $post_id ): string {
		$custom = get_post_meta( $post_id, self::META_TITLE, true );
		$title  = is_string( $custom ) && $custom !== '' ? $custom : get_post_field( 'post_title', $post_id );
		return $this->shorten( (string) $title, 90 );
	}

	protected function get_meta_desc_for_admin( int $post_id ): string {
		$custom = get_post_meta( $post_id, self::META_DESC, true );
		if ( is_string( $custom ) && $custom !== '' ) {
			return $this->shorten( $custom, 120 );
		}
		$excerpt = get_post_field( 'post_excerpt', $post_id );
		if ( is_string( $excerpt ) && $excerpt !== '' ) {
			return $this->shorten( $excerpt, 120 );
		}
		$content = get_post_field( 'post_content', $post_id );
		$content = is_string( $content ) ? wp_strip_all_tags( $content ) : '';
		return $this->shorten( $content, 120 );
	}

	/* =========================
	 * Helpers
	 * ========================= */

	protected function echo_cell( string $text ): void {
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
		if ( $text === '' ) {
			echo '<span class="tsc-col--muted" style="color:#666;">' . esc_html__( '—', 'theme-seo-core' ) . '</span>';
			return;
		}
		echo esc_html( $text );
	}

	protected function shorten( string $text, int $limit ): string {
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $text ) ) );
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		return rtrim( mb_substr( $text, 0, max( 1, $limit - 1 ) ) ) . '…';
	}
}
