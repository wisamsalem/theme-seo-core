<?php
namespace ThemeSeoCore\Modules\XmlRpc;

use ThemeSeoCore\Support\BaseModule;
use ThemeSeoCore\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * XML-RPC Module
 *
 * Disable or limit XML-RPC access (recommended: disabled unless you use a client).
 */
class Module extends BaseModule {

	protected static $slug = 'xmlrpc';
	protected static $title = 'XML-RPC';
	protected static $description = 'Disable or limit XML-RPC, remove pingback headers, and optional rate-limiting.';

	/** @var Options */
	protected $options;

	/** @var Controls */
	protected $controls;

	public function register( \ThemeSeoCore\Container\Container $container ): void {
		parent::register( $container );

		$this->options  = new Options( 'tsc_settings' );
		$this->controls = new Controls();

		$this->controls->register( $this->config() );
		$this->register_hooks();
	}

	protected function hooks(): array {
		return [
			'tsc/xmlrpc/defaults' => 'defaults',
		];
	}

	/**
	 * Defaults (filterable).
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return [
			// Hard-disable all XML-RPC (recommended unless you need it).
			'disable'                => true,

			// If not fully disabled, you can still block pingback abuse vectors:
			'disable_pingbacks'      => true,   // removes pingback.* methods
			'disable_multicall'      => true,   // removes system.multicall

			// Remove public exposure of XML-RPC endpoint / pingback URL:
			'remove_pingback_header' => true,   // strips X-Pingback and <link rel="pingback">

			// Only allow these XML-RPC methods if not disabled (empty = allow all remaining).
			'allow_methods'          => [],

			// Optional: only allow requests from these IPs (empty = all).
			'allowlist_ips'          => [],

			// Optional rate limiting (very light, per-IP per minute).
			'rate_limit'             => [
				'enabled'        => false,
				'max_per_minute' => 60,
			],
		];
	}

	/**
	 * Merge stored settings with defaults.
	 */
	protected function config(): array {
		$stored = (array) $this->options->get( 'xmlrpc', [] );
		$cfg    = wp_parse_args( $stored, $this->defaults() );

		/**
		 * Filter the resolved XML-RPC settings.
		 *
		 * @param array $cfg
		 */
		return (array) apply_filters( 'tsc/xmlrpc/config', $cfg );
	}
}

