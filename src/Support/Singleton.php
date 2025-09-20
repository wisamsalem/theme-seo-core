<?php
namespace ThemeSeoCore\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal Singleton helper trait (for simple stateless utilities).
 *
 * Usage:
 * class Foo { use Singleton; private function __construct() {} }
 * $foo = Foo::instance();
 */
trait Singleton {
	/** @var static|null */
	protected static $instance = null;

	/** Prevent direct construction. */
	private function __construct() {}

	/** Prevent cloning. */
	private function __clone() {}

	/** Prevent unserialize. */
	final public function __wakeup() {}

	/**
	 * Get/create the single instance.
	 *
	 * @return static
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}

