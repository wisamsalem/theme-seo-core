<?php
namespace ThemeSeoCore\Modules\Sitemaps;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sitemaps Module
 *
 * Pretty URLs:
 *   /sitemap.xml                      → index
 *   /sitemap-posts-1.xml              → posts (page 1)
 *   /sitemap-taxonomies-1.xml         → taxonomies
 *   /sitemap-authors-1.xml            → authors
 *   /sitemap-images-1.xml             → images from recent posts
 *
 * Query vars:
 *   tsc_sitemap = index|posts|taxonomies|authors|images
 *   tsc_page    = 1..N
 */
class Module extends BaseModule {

	protected static $slug = 'sitemaps';
	protected static $title = 'Sitemaps';
	protected static $description = 'XML sitemaps with index, posts, taxonomies, authors, images.';

	/** @var Router */
	protected $router;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->router = new Router();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'init'           => 'on_init',
			'query_vars'     => [ [ $this, 'query_vars' ], 10, 1, 'filter' ],
			'template_redirect' => 'maybe_render',
		];
	}

	public function on_init(): void {
		$this->router->add_rewrites();
	}

	/**
	 * @param array $vars
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'tsc_sitemap';
		$vars[] = 'tsc_page';
		return $vars;
	}

	/**
	 * Handle sitemap requests early in template flow.
	 */
	public function maybe_render(): void {
		$type = get_query_var( 'tsc_sitemap' );
		if ( ! $type ) {
			return;
		}

		$page = max( 1, (int) get_query_var( 'tsc_page', 1 ) );
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		switch ( $type ) {
			case 'index':
				$builder = new IndexBuilder();
				$sitemaps = $builder->index();
				$stylesheet = add_query_arg( array( 'tsc_sitemap' => 'xsl' ), home_url( '/' ) );
				$view = __DIR__ . '/Views/sitemap-xml.php';
				$kind = 'index';
				$entries = $sitemaps;
				include $view;
				exit;

			case 'posts':
				$provider = new Providers\PostsProvider();
				break;

			case 'taxonomies':
				$provider = new Providers\TaxonomiesProvider();
				break;

			case 'authors':
				$provider = new Providers\AuthorsProvider();
				break;

			case 'images':
				$provider = new Providers\ImagesProvider();
				break;

			case 'xsl':
				header( 'Content-Type: application/xslt+xml; charset=UTF-8' );
				include __DIR__ . '/Views/sitemap-xsl.php';
				exit;

			default:
				status_header( 404 );
				exit;
		}

		$chunk = $provider->page( $page );
		$stylesheet = add_query_arg( array( 'tsc_sitemap' => 'xsl' ), home_url( '/' ) );
		$view = __DIR__ . '/Views/sitemap-xml.php';
		$kind = 'urlset';
		$entries = $chunk['entries'];
		$total_pages = $chunk['total_pages'];
		$current_page = $chunk['current_page'];

		// If page out of range, 404
		if ( $total_pages > 0 && $current_page > $total_pages ) {
			status_header( 404 );
			exit;
		}

		include $view;
		exit;
	}
}

