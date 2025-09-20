<?php
namespace ThemeSeoCore\Rest\v1;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use ThemeSeoCore\Security\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * /wp-json/theme-seo/v1/...
 */
class SeoController extends WP_REST_Controller {

	/** @var string */
	protected $namespace = 'theme-seo/v1';

	/** @var string */
	protected $rest_base = 'settings';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET/UPDATE plugin settings
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage_seo' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE, // PUT/PATCH
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'can_manage_seo' ),
					'args'                => array(
						'payload' => array(
							'required' => true,
							'type'     => 'object',
							'description' => 'Full or partial settings object to merge & save.',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Utility: preview a title pattern with site/post context
		register_rest_route(
			$this->namespace,
			'/preview-title',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'preview_title' ),
					'permission_callback' => array( $this, 'can_manage_seo' ),
					'args'                => array(
						'pattern' => array(
							'type'        => 'string',
							'required'    => true,
							'description' => 'Title pattern (e.g., "%%title%% %%sep%% %%sitename%%").',
						),
						'post' => array(
							'type'        => 'integer',
							'required'    => false,
							'description' => 'Post ID to use for %%title%% context.',
						),
					),
				),
			)
		);
	}

	/**
	 * Permissions callback.
	 */
	public function can_manage_seo(): bool {
		return current_user_can( Capabilities::manage_seo() );
	}

	/**
	 * GET /settings
	 */
	public function get_settings( WP_REST_Request $req ): WP_REST_Response {
		$settings = (array) get_option( 'tsc_settings', array() );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $settings,
			),
			200
		);
	}

	/**
	 * PUT/PATCH /settings
	 *
	 * Accepts an object under "payload" and merges it into stored settings,
	 * running a light sanitize (same shape as SettingsRegistry::sanitize()).
	 */
	public function update_settings( WP_REST_Request $req ): WP_REST_Response {
		$payload = $req->get_param( 'payload' );
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid payload.' ), 400 );
		}

		$existing = (array) get_option( 'tsc_settings', array() );
		$merged   = $this->sanitize( array_replace_recursive( $existing, $payload ) );

		update_option( 'tsc_settings', $merged );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $merged,
			),
			200
		);
	}

	/**
	 * GET /preview-title?pattern=...&post=ID
	 *
	 * A tiny token replacer for admin previews (not for final front-end titles).
	 */
	public function preview_title( WP_REST_Request $req ): WP_REST_Response {
		$pattern = (string) $req->get_param( 'pattern' );
		$post_id = (int) $req->get_param( 'post' );

		$sep      = $this->site_separator();
		$sitename = get_bloginfo( 'name' );
		$tagline  = get_bloginfo( 'description' );
		$title    = $sitename;

		if ( $post_id > 0 ) {
			$t = get_the_title( $post_id );
			if ( $t ) {
				$title = $t;
			}
		}

		$replacements = array(
			'%%title%%'    => $title,
			'%%sitename%%' => $sitename,
			'%%tagline%%'  => $tagline,
			'%%sep%%'      => $sep,
		);

		$out = trim( strtr( $pattern, $replacements ) );
		$out = preg_replace( '/\s{2,}/', ' ', (string) $out );

		return new WP_REST_Response( array( 'success' => true, 'preview' => $out ), 200 );
	}

	/**
	 * Public schema for /settings
	 */
	public function get_public_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ThemeSEO Settings',
			'type'       => 'object',
			'properties' => array(
				'settings' => array(
					'type'       => 'object',
					'properties' => array(
						'separator' => array( 'type' => 'string' ),
						'modules'   => array( 'type' => 'object' ),
						'noindex_search' => array( 'type' => 'boolean' ),
					),
				),
			),
		);
	}

	/**
	 * Light sanitize similar to Admin\SettingsRegistry.
	 */
	protected function sanitize( array $in ): array {
		$out = $in;

		$out['separator'] = isset( $out['separator'] ) ? wp_kses_post( $out['separator'] ) : '–';

		if ( isset( $out['modules'] ) && is_array( $out['modules'] ) ) {
			$out['modules'] = array_map( 'boolval', $out['modules'] );
		}

		$out['noindex_search'] = ! empty( $out['noindex_search'] );

		// Patterns (optional).
		if ( isset( $out['patterns'] ) && is_array( $out['patterns'] ) ) {
			foreach ( $out['patterns'] as $k => $v ) {
				$out['patterns'][ $k ] = is_string( $v ) ? trim( wp_kses_post( $v ) ) : '';
			}
		}

		return $out;
	}

	protected function site_separator(): string {
		$s = (array) get_option( 'tsc_settings', array() );
		return isset( $s['separator'] ) ? (string) $s['separator'] : '–';
	}
}

