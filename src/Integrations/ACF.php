<?php
namespace ThemeSeoCore\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Custom Fields integration:
 * - Fallback to ACF fields when our post meta is empty.
 *
 * Convention (override via filters if you use different keys):
 *   seo_title       → _tsc_title
 *   seo_description → _tsc_desc
 *   seo_canonical   → _tsc_canonical
 */
class ACF {

	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_boot' ), 12 );
	}

	public function maybe_boot(): void {
		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		add_filter( 'get_post_metadata', array( $this, 'fallback_meta' ), 10, 4 );
	}

	/**
	 * When our meta is missing/empty, fall back to ACF fields.
	 *
	 * @param mixed  $value    Current meta value (null to fetch from DB).
	 * @param int    $post_id
	 * @param string $meta_key
	 * @param bool   $single
	 * @return mixed
	 */
	public function fallback_meta( $value, $post_id, $meta_key, $single ) {
		$map = apply_filters( 'tsc/acf/meta_map', array(
			'_tsc_title'     => 'seo_title',
			'_tsc_desc'      => 'seo_description',
			'_tsc_canonical' => 'seo_canonical',
		) );

		if ( ! isset( $map[ $meta_key ] ) ) {
			return $value;
		}

		// If DB already has our value, respect it.
		if ( null === $value ) {
			$value = get_metadata_raw( 'post', $post_id, $meta_key, $single );
		}
		$empty = ( '' === $value || $value === array() || null === $value );

		if ( ! $empty ) {
			return $value;
		}

		$acf_key = $map[ $meta_key ];
		$acf_val = get_field( $acf_key, $post_id );

		if ( $acf_val ) {
			// Shape like get_post_meta() would return.
			if ( $single ) {
				return $acf_val;
			}
			return array( $acf_val );
		}

		return $value;
	}
}

/**
 * Helper: get raw meta without filters (so we can check if DB has a value).
 *
 * @param string   $meta_type
 * @param int      $object_id
 * @param string   $meta_key
 * @param bool     $single
 * @return mixed
 */
function get_metadata_raw( $meta_type, $object_id, $meta_key, $single ) {
	global $wpdb;

	$table = $wpdb->prefix . $meta_type . 'meta';
	$sql   = $wpdb->prepare(
		"SELECT meta_value FROM {$table} WHERE {$meta_type}_id = %d AND meta_key = %s",
		$object_id,
		$meta_key
	);

	$rows = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	if ( $single ) {
		return isset( $rows[0 ] ) ? maybe_unserialize( $rows[0] ) : null;
	}
	return array_map( 'maybe_unserialize', $rows );
}

