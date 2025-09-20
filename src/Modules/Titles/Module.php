<?php
namespace ThemeSeoCore\Modules\Titles;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Titles Module
 *
 * Replaces the document title using pattern templates.
 */
class Module extends BaseModule {

	protected static $slug = 'titles';
	protected static $title = 'Titles';
	protected static $description = 'Control title templates for all contexts.';

	/** @var \ThemeSeoCore\Support\Options */
	protected $options;

	/** @var \ThemeSeoCore\Modules\Titles\TitleGenerator */
	protected $generator;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->options   = new Options( 'tsc_settings' );
		$this->generator = new TitleGenerator( $this->options );

		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			// Use our separator everywhere.
			'document_title_separator' => [ [ $this, 'filter_separator' ], 10, 1, 'filter' ],

			// Provide a fully composed string; WP will use it if non-empty.
			'pre_get_document_title'   => [ [ $this, 'filter_document_title' ], 20, 1, 'filter' ],

			// Fallback: tweak parts if another SEO plugin isn't already handling it.
			'document_title_parts'     => [ [ $this, 'filter_title_parts' ], 20, 1, 'filter' ],

			// Optional: expose patterns to REST or admin UIs via filter.
			'tsc/titles/patterns/defaults' => 'default_patterns',
		];
	}

	/**
	 * Return the configured separator (string).
	 *
	 * @param string $sep
	 * @return string
	 */
	public function filter_separator( $sep ) {
		$cfg = $this->options->all();
		return isset( $cfg['separator'] ) && $cfg['separator'] !== ''
			? (string) $cfg['separator']
			: '–';
	}

	/**
	 * Provide a complete title string. If empty, WP will assemble from parts.
	 *
	 * @param string $title
	 * @return string
	 */
	public function filter_document_title( $title ) {
		if ( ! $this->enabled() ) {
			return $title;
		}
		$composed = $this->generator->generate();
		return is_string( $composed ) && $composed !== '' ? $composed : $title;
	}

	/**
	 * As a safety net, modify parts when pre_get_document_title was bypassed.
	 *
	 * @param array $parts
	 * @return array
	 */
	public function filter_title_parts( $parts ) {
		if ( ! $this->enabled() ) {
			return $parts;
		}

		// If another plugin already set a complete title via 'title' key, respect it.
		if ( ! empty( $parts['title'] ) && ( is_front_page() || is_singular() ) ) {
			return $parts;
		}

		$composed = $this->generator->generate();
		if ( $composed ) {
			$sep  = $this->filter_separator( '–' );
			$bits = array_map( 'trim', explode( $sep, $composed ) );

			if ( count( $bits ) >= 2 ) {
				$parts['title']   = array_shift( $bits );
				$parts['site']    = end( $bits ); // last bit often sitename
				$parts['tagline'] = '';
				$parts['page']    = '';
			} else {
				$parts['title'] = $composed;
			}
		}

		return $parts;
	}

	/**
	 * Default pattern set (filterable).
	 *
	 * @return array<string,string>
	 */
	public function default_patterns(): array {
		return [
			'home'    => '%%sitename%% %%sep%% %%tagline%%',
			'post'    => '%%title%% %%sep%% %%sitename%%',
			'page'    => '%%title%% %%sep%% %%sitename%%',
			'archive' => '%%archive_title%% %%sep%% %%sitename%%',
			'search'  => '%%search_query%% %%sep%% %%sitename%%',
			'404'     => '%%not_found%% %%sep%% %%sitename%%',
			'author'  => '%%author%% %%sep%% %%sitename%%',
		];
	}
}
