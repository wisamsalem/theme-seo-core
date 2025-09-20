<?php
namespace ThemeSeoCore\Admin;

use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post edit SEO meta box (classic + block).
 */
class MetaBox {

	const META_TITLE     = '_tsc_title';
	const META_DESC      = '_tsc_desc';
	const META_NOINDEX   = '_tsc_noindex';
	const META_NOFOLLOW  = '_tsc_nofollow';
	const META_CANONICAL = '_tsc_canonical';

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );

		// Show in block editor sidebar using meta fields (simple approach).
		add_action( 'init', array( $this, 'register_post_meta' ) );
	}

	public function register_post_meta(): void {
		$args_text = array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback'=> fn() => current_user_can( Capabilities::MANAGE_SEO ),
			'sanitize_callback' => 'sanitize_text_field',
		);
		register_post_meta( '', self::META_TITLE, $args_text );
		register_post_meta( '', self::META_DESC,  $args_text );

		register_post_meta( '', self::META_CANONICAL, array_merge( $args_text, array( 'sanitize_callback' => 'esc_url_raw' ) ) );
		register_post_meta( '', self::META_NOINDEX, array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'boolean',
			'auth_callback'=> fn() => current_user_can( Capabilities::MANAGE_SEO ),
		) );
		register_post_meta( '', self::META_NOFOLLOW, array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'boolean',
			'auth_callback'=> fn() => current_user_can( Capabilities::MANAGE_SEO ),
		) );
	}

	public function add_meta_box(): void {
		$screens = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $screens as $pt ) {
			add_meta_box(
				'tsc_seo',
				__( 'SEO', 'theme-seo-core' ),
				array( $this, 'render' ),
				$pt,
				'side',
				'default'
			);
		}
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'tsc_meta', '_tsc_meta_nonce' );

		$title     = get_post_meta( $post->ID, self::META_TITLE, true );
		$desc      = get_post_meta( $post->ID, self::META_DESC, true );
		$noindex   = (bool) get_post_meta( $post->ID, self::META_NOINDEX, true );
		$nofollow  = (bool) get_post_meta( $post->ID, self::META_NOFOLLOW, true );
		$canonical = get_post_meta( $post->ID, self::META_CANONICAL, true );

		echo '<p><label for="tsc_title"><strong>' . esc_html__( 'SEO Title', 'theme-seo-core' ) . '</strong></label>';
		echo '<input type="text" id="tsc_title" name="tsc_title" value="' . esc_attr( $title ) . '" class="widefat" maxlength="70" data-counter-max="70" /></p>';

		echo '<p><label for="tsc_desc"><strong>' . esc_html__( 'Meta Description', 'theme-seo-core' ) . '</strong></label>';
		echo '<textarea id="tsc_desc" name="tsc_desc" class="widefat" rows="3" maxlength="160" data-counter-max="160">' . esc_textarea( $desc ) . '</textarea></p>';

		echo '<p><label for="tsc_canonical"><strong>' . esc_html__( 'Canonical URL', 'theme-seo-core' ) . '</strong></label>';
		echo '<input type="url" id="tsc_canonical" name="tsc_canonical" value="' . esc_attr( $canonical ) . '" class="widefat" /></p>';

		echo '<p><label><input type="checkbox" name="tsc_noindex" value="1" ' . checked( $noindex, true, false ) . '> ' . esc_html__( 'Noindex', 'theme-seo-core' ) . '</label></p>';
		echo '<p><label><input type="checkbox" name="tsc_nofollow" value="1" ' . checked( $nofollow, true, false ) . '> ' . esc_html__( 'Nofollow', 'theme-seo-core' ) . '</label></p>';
	}

	public function save( int $post_id, \WP_Post $post ): void {
		// Autosave/permissions/nonces.
		if ( ! isset( $_POST['_tsc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_tsc_meta_nonce'] ) ), 'tsc_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( Capabilities::MANAGE_SEO ) ) {
			return;
		}

		$title     = isset( $_POST['tsc_title'] ) ? sanitize_text_field( wp_unslash( $_POST['tsc_title'] ) ) : '';
		$desc      = isset( $_POST['tsc_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tsc_desc'] ) ) : '';
		$canonical = isset( $_POST['tsc_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['tsc_canonical'] ) ) : '';
		$noindex   = isset( $_POST['tsc_noindex'] ) ? '1' : '';
		$nofollow  = isset( $_POST['tsc_nofollow'] ) ? '1' : '';

		update_post_meta( $post_id, self::META_TITLE, $title );
		update_post_meta( $post_id, self::META_DESC, $desc );
		update_post_meta( $post_id, self::META_CANONICAL, $canonical );
		update_post_meta( $post_id, self::META_NOINDEX, $noindex );
		update_post_meta( $post_id, self::META_NOFOLLOW, $nofollow );
	}
}

