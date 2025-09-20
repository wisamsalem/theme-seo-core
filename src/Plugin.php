<?php
namespace ThemeSeoCore;

use ThemeSeoCore\Container\Container;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugin {

	protected array $context = [];
	protected Container $container;

	public function init( array $context ): void {
		$this->context   = $context;
		$this->container = new Container();

		// Security helpers (safe no-ops)
		if ( class_exists( __NAMESPACE__ . '\\Security\\Capabilities' ) ) {
			( new Security\Capabilities() )->register( $this->container );
		}
		if ( class_exists( __NAMESPACE__ . '\\Security\\Nonces' ) ) {
			( new Security\Nonces() )->register( $this->container );
		}

		// Modules (Lite/Pro gate)
		$this->register_modules( ! empty( $context['is_pro'] ) );
		
		// Ensure head output when theme prints wp_head
if ( class_exists( \ThemeSeoCore\Modules\Titles\TitleGenerator::class ) ) {
	\ThemeSeoCore\Modules\Titles\TitleGenerator::filter_document_title();
}
if ( class_exists( \ThemeSeoCore\Modules\Meta\MetaGenerator::class ) ) {
	\ThemeSeoCore\Modules\Meta\MetaGenerator::hook_head();
}
if ( class_exists( \ThemeSeoCore\Modules\Canonical\CanonicalUrl::class ) ) {
	\ThemeSeoCore\Modules\Canonical\CanonicalUrl::hook_head();
}


		// Admin provider — run both phases
		if ( is_admin() && class_exists( __NAMESPACE__ . '\\Admin\\Admin' ) ) {
			$admin = new Admin\Admin();
			$admin->register( $this->container );
			$admin->boot( $this->container );
		}

		// Fallback menu (safety net so you ALWAYS get a menu)
		if ( is_admin() && class_exists( __NAMESPACE__ . '\\Admin\\FallbackMenu' ) ) {
			Admin\FallbackMenu::register();
		}

		// Frontend bootstrap (if present)
		if ( class_exists( __NAMESPACE__ . '\\Frontend\\Frontend' ) ) {
			( new Frontend\Frontend( $this->container ) )->register( $this->container );
		}
	}

	protected function register_modules( bool $pro ): void {
		$core = [
			Modules\Titles\Module::class,
			Modules\Meta\Module::class,
			Modules\Canonical\Module::class,
			Modules\OpenGraph\Module::class,
			Modules\Schema\Module::class,
			Modules\Sitemaps\Module::class,
			Modules\Robots\Module::class,
			Modules\Breadcrumbs\Module::class,
			Modules\ImageSEO\Module::class,
			Modules\ThemeOptimization\Module::class,
			Modules\XmlRpc\Module::class,
		];

		$pro_only = [
			Modules\Redirects\Module::class,
			Modules\LinkManager\Module::class,
			Modules\ContentAnalysis\Module::class,
			Integrations\WooCommerce::class,
			Integrations\ACF::class,
			Integrations\YoastCompat::class,
			Rest\Controller::class,
			Cli\Commands::class,
		];

		$to_register = $pro ? array_merge( $core, $pro_only ) : $core;
		foreach ( $to_register as $class ) {
			if ( class_exists( $class ) ) {
				$instance = new $class();
				if ( method_exists( $instance, 'register' ) ) {
					$instance->register( $this->container );
				} elseif ( method_exists( $instance, 'init' ) ) {
					$instance->init( $this->container );
				}
			}
		}
	}
}

