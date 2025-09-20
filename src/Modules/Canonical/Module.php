<?php
namespace ThemeSeoCore\Modules\Canonical;

use ThemeSeoCore\Support\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical Module
 *
 * Supplies the canonical URL via the 'tsc/head/canonical' filter so the
 * central Head pipeline can output <link rel="canonical">.
 */
class Module extends BaseModule {

	protected static $slug = 'canonical';
	protected static $title = 'Canonical URLs';
	protected static $description = 'Compute and output canonical link tags for all contexts.';

	/** @var CanonicalUrl */
	protected $resolver;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->resolver = new CanonicalUrl();

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// Provide the canonical link to Head via filter.
			'tsc/head/canonical' => [ [ $this, 'canonical' ], 10, 1, 'filter' ],
		];
	}

	/**
	 * Return canonical URL string for current query.
	 *
	 * @param string $current Existing canonical (may be empty).
	 * @return string
	 */
	public function canonical( string $current ): string {
		if ( ! $this->enabled() ) {
			return $current;
		}
		$resolved = $this->resolver->current();
		return $resolved ?: $current;
	}
}

