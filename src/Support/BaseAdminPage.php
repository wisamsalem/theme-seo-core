<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper for building admin pages with nonce/asset helpers.
 */
abstract class BaseAdminPage {
	use HasHooks;

	/** @var string Menu page slug (unique) */
	protected $slug = 'theme-seo-core';

	/** @var string Capability required */
	protected $capability = 'manage_options';

	/** @var string Page title */
	protected $page_title = 'Theme SEO Core';

	/** @var string Menu title */
	protected $menu_title = 'SEO';

	/** @var string Nonce action/key */
	protected $nonce_action = 'tsc_save_settings';

	/**
	 * Register WP Admin hooks (menus, assets).
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add top-level menu under Settings by default.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_menu_page(
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->slug,
			array( $this, 'render' ),
			'dashicons-chart-area',
			58
		);
	}

	/**
	 * Enqueue assets on our screen only.
	 *
	 * @param string $hook
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_' . $this->slug ) {
			return;
		}

		// Admin CSS/JS (use *.asset.php if you build with wp-scripts)
		wp_enqueue_style( 'tsc-admin', TSC_URL . 'assets/admin/css/admin.css', array(), TSC_VERSION );
		wp_enqueue_script( 'tsc-admin', TSC_URL . 'assets/admin/js/admin.js', array(), TSC_VERSION, true );

		wp_localize_script( 'tsc-admin', 'tscAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( $this->nonce_action ),
			'i18n'    => array(
				'saved' => __( 'Settings saved.', 'theme-seo-core' ),
				'error' => __( 'There was an error saving your settings.', 'theme-seo-core' ),
			),
		) );
	}

	/**
	 * Render callback — override this in child classes and include a view.
	 *
	 * @return void
	 */
	abstract public function render(): void;

	/**
	 * Nonce verification helper for form posts/AJAX.
	 *
	 * @param string $field
	 * @return bool
	 */
	protected function verify_nonce( string $field = '_tsc_nonce' ): bool {
		return isset( $_POST[ $field ] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $this->nonce_action );
	}
}

