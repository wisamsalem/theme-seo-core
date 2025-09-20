<?php
namespace ThemeSeoCore\Modules\Redirects;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects Module
 *
 * - Intercepts front-end requests and performs safe 3xx redirects.
 * - Tracks hits/last_hit in custom table (created by Activator/Migration).
 * - Admin UI (list/add/import/export) under SEO menu.
 */
class Module extends BaseModule {

	protected static $slug = 'redirects';
	protected static $title = 'Redirects';
	protected static $description = 'Manage 301/302/307/308 redirects with wildcard/regex support.';

	/** @var DB */
	protected $db;

	/** @var Admin */
	protected $admin;

	/** @var ImportExport */
	protected $io;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->db   = new DB();
		$this->admin = new Admin( $this->db );
		$this->io   = new ImportExport( $this->db );

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// Execute early but after query vars are set.
			'template_redirect' => [ [ $this, 'maybe_redirect' ], 1 ],
			// Admin UI
			'admin_menu'        => 'admin_menu',
			'admin_post_tsc_redirects_add'   => 'handle_add',
			'admin_post_tsc_redirects_bulk'  => 'handle_bulk',
			'admin_post_tsc_redirects_export'=> 'handle_export',
			'admin_post_tsc_redirects_import'=> 'handle_import',
		];
	}

	/**
	 * Perform redirect if a rule matches the current request.
	 */
	public function maybe_redirect(): void {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		// Avoid loops on common endpoints.
		$path = wp_parse_url( home_url( add_query_arg( null, null ) ), PHP_URL_PATH );
		if ( $path && in_array( $path, array( '/robots.txt', '/sitemap.xml' ), true ) ) {
			return;
		}

		$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$host    = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
		if ( '' === $req_uri ) {
			return;
		}

		// Normalize to just path+query, no scheme/host.
		$parsed = wp_parse_url( $req_uri );
		$path_q = ( $parsed['path'] ?? '/' ) . ( isset( $parsed['query'] ) ? '?' . $parsed['query'] : '' );

		$rule = $this->db->match_rule( $path_q, $host );
		if ( ! $rule ) {
			return;
		}

		$target = $rule['target'] ?? '';
		$status = (int) ( $rule['status'] ?? 301 );

		// Prevent self-redirect loops (path equivalence).
		if ( $this->is_same_destination( $path_q, $target ) ) {
			return;
		}

		// Track hit (best-effort).
		$this->db->track_hit( (int) $rule['id'] );

		// Final chance for customizations.
		$target = (string) apply_filters( 'tsc/redirects/target', $target, $rule, $path_q );
		$status = (int) apply_filters( 'tsc/redirects/status', $status, $rule, $path_q );

		wp_redirect( $target, $status );
		exit;
	}

	protected function is_same_destination( string $from, string $to ): bool {
		$a = wp_parse_url( $from );
		$b = wp_parse_url( $to );
		if ( empty( $b['host'] ) ) {
			// Relative target; compare paths only.
			return ( rtrim( (string) ( $a['path'] ?? '' ), '/' ) === rtrim( (string) ( $b['path'] ?? '' ), '/' ) )
				&& ( (string) ( $a['query'] ?? '' ) === (string) ( $b['query'] ?? '' ) );
		}
		return false;
	}

	/* ----------------- Admin wiring ----------------- */

	public function admin_menu(): void {
		add_submenu_page(
			'theme-seo-core',
			__( 'Redirects', 'theme-seo-core' ),
			__( 'Redirects', 'theme-seo-core' ),
			'tsc_manage_redirects',
			'theme-seo-redirects',
			function () { $this->admin->render(); },
			30
		);
	}

	public function handle_add(): void        { $this->admin->handle_add(); }
	public function handle_bulk(): void       { $this->admin->handle_bulk(); }
	public function handle_export(): void     { $this->io->export_csv(); }
	public function handle_import(): void     { $this->io->import_csv(); }
}

