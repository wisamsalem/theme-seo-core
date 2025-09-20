<?php
/**
 * Plugin Name:       Theme SEO Core — Lite
 * Description:       Core SEO toolkit (titles, meta, schema, sitemaps, robots, breadcrumbs).
 * Version:           1.0.0
 * Author:            Minsifir
 * Author URI:        https://minsifir.com
 * Plugin URI:        https://minsifir.com/theme-seo-core
 * Text Domain:       theme-seo-core-lite
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 *
 * @package ThemeSeoCoreLite
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** ------------------------------------------------------------------------
 * Prevent conflicts: auto-deactivate Lite if Pro is active
 * --------------------------------------------------------------------- */
add_action( 'admin_init', static function () {
	if ( is_plugin_active( 'theme-seo-core/theme-seo-core.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', static function () {
			echo '<div class="notice notice-warning"><p>' .
				esc_html__( 'Theme SEO Core — Lite was deactivated because Theme SEO Core (Pro) is active.', 'theme-seo-core-lite' ) .
				'</p></div>';
		});
	}
});

/** ------------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------- */
if ( ! defined( 'TSC_FILE' ) )     define( 'TSC_FILE', __FILE__ );
if ( ! defined( 'TSC_PATH' ) )     define( 'TSC_PATH', plugin_dir_path( TSC_FILE ) );
if ( ! defined( 'TSC_URL' ) )      define( 'TSC_URL', plugin_dir_url( TSC_FILE ) );
if ( ! defined( 'TSC_BASENAME' ) ) define( 'TSC_BASENAME', plugin_basename( TSC_FILE ) );
if ( ! defined( 'TSC_VERSION' ) )  define( 'TSC_VERSION', '1.0.0' );
if ( ! defined( 'TSC_SAFE_MODE' ) ) define( 'TSC_SAFE_MODE', false );
if ( ! defined( 'TSC_IS_PRO' ) )    define( 'TSC_IS_PRO', false ); // ← LITE build

/** ------------------------------------------------------------------------
 * Env, i18n, autoload — same shape as Pro, with lite text domain
 * --------------------------------------------------------------------- */
function tsc_env_ok(): bool {
	$min_php = '7.4'; $min_wp = '6.0';
	if ( version_compare( PHP_VERSION, $min_php, '<' ) ) {
		add_action( 'admin_notices', static function () use ( $min_php ) {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf( __( 'Theme SEO Core requires PHP %s or higher.', 'theme-seo-core-lite' ), $min_php ) )
			);
		});
		return false;
	}
	if ( version_compare( get_bloginfo( 'version' ), $min_wp, '<' ) ) {
		add_action( 'admin_notices', static function () use ( $min_wp ) {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf( __( 'Theme SEO Core requires WordPress %s or higher.', 'theme-seo-core-lite' ), $min_wp ) )
			);
		});
		return false;
	}
	return true;
}

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'theme-seo-core-lite', false, dirname( TSC_BASENAME ) . '/languages' );
}, 0);

(function (): void {
	$composer = TSC_PATH . 'vendor/autoload.php';
	if ( file_exists( $composer ) ) { require $composer; return; }
	spl_autoload_register( static function ( $class ): void {
		$prefix = 'ThemeSeoCore\\'; $base = TSC_PATH . 'src/'; $len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) return;
		$file = $base . str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, $len ) ) . '.php';
		if ( is_readable( $file ) ) require $file;
	});
})();

/** ------------------------------------------------------------------------
 * Activation / Deactivation
 * --------------------------------------------------------------------- */
register_activation_hook( TSC_FILE, static function ( $network_wide ) {
	if ( ! tsc_env_ok() ) { deactivate_plugins( TSC_BASENAME ); return; }
	if ( class_exists( 'ThemeSeoCore\\Setup\\Activator' ) ) {
		ThemeSeoCore\Setup\Activator::run( (bool) $network_wide );
	}
});
register_deactivation_hook( TSC_FILE, static function ( $network_wide ) {
	if ( class_exists( 'ThemeSeoCore\\Setup\\Deactivator' ) ) {
		ThemeSeoCore\Setup\Deactivator::run( (bool) $network_wide );
	}
});

/** ------------------------------------------------------------------------
 * Bootstrap
 * --------------------------------------------------------------------- */
add_action( 'plugins_loaded', static function () {
	if ( ! tsc_env_ok() ) return;
	if ( ! class_exists( 'ThemeSeoCore\\Plugin' ) ) {
		add_action( 'admin_notices', static function () {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Theme SEO Core core classes missing. Run composer install.', 'theme-seo-core-lite' )
			);
		});
		return;
	}
	try {
		$plugin = new ThemeSeoCore\Plugin();
		if ( method_exists( $plugin, 'init' ) ) {
			$plugin->init([
				'version'   => TSC_VERSION,
				'file'      => TSC_FILE,
				'path'      => TSC_PATH,
				'url'       => TSC_URL,
				'basename'  => TSC_BASENAME,
				'safe_mode' => (bool) TSC_SAFE_MODE,
				'is_pro'    => (bool) TSC_IS_PRO,
			]);
		}
	} catch ( \Throwable $e ) {
		add_action( 'admin_notices', static function () use ( $e ) {
			printf('<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf( __( 'Theme SEO Core failed to initialize: %s', 'theme-seo-core-lite' ), $e->getMessage() ) )
			);
		});
	}
}, 5);

/** ------------------------------------------------------------------------
 * Plugins screen links
 * --------------------------------------------------------------------- */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$url = admin_url( 'admin.php?page=theme-seo-core' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'theme-seo-core-lite' ) . '</a>' );
	return $links;
});
add_filter( 'plugin_row_meta', function( $links, $file ) {
	if ( plugin_basename( __FILE__ ) !== $file ) return $links;
	$extras = [
		'<a href="https://minsifir.com/theme-seo-core/docs" target="_blank" rel="noopener">' . esc_html__( 'Docs', 'theme-seo-core-lite' ) . '</a>',
		'<a href="https://minsifir.com/support" target="_blank" rel="noopener">' . esc_html__( 'Support', 'theme-seo-core-lite' ) . '</a>',
		'<a href="https://minsifir.com/theme-seo-core/pro" target="_blank" rel="noopener"><strong>' . esc_html__( 'Get Full Features', 'theme-seo-core-lite' ) . '</strong></a>',
	];
	return array_merge( $links, $extras );
}, 10, 2 );

