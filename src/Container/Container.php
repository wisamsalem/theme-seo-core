<?php
namespace ThemeSeoCore\Container;

use ReflectionClass;
use ReflectionParameter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ultra-light DI Container
 *
 * Features:
 * - bind(id, concrete)         : factory binding (callable|string class)
 * - singleton(id, concrete)    : shared instance binding
 * - instance(id, object)       : register an existing object
 * - make(id, [parameters])     : resolve by id or FQCN (auto-wire ctor)
 * - call(callable, [params])   : invoke with auto-resolved dependencies
 * - tag(object, tag), tagged(tag) : group instances for later iteration
 *
 * @package ThemeSeoCore
 */
class Container {

	/** @var array<string, callable|string> */
	protected $bindings = array();

	/** @var array<string, callable|string> */
	protected $singletons = array();

	/** @var array<string, object> */
	protected $instances = array();

	/** @var array<string, array<int,object>> */
	protected $tags = array();

	/**
	 * Register a factory binding.
	 *
	 * @param string                 $id
	 * @param callable|string|object $concrete
	 * @return void
	 */
	public function bind( string $id, $concrete ): void {
		$this->bindings[ $id ] = $concrete;
	}

	/**
	 * Register a singleton binding.
	 *
	 * @param string                 $id
	 * @param callable|string|object $concrete
	 * @return void
	 */
	public function singleton( string $id, $concrete ): void {
		$this->singletons[ $id ] = $concrete;
	}

	/**
	 * Register an existing instance.
	 *
	 * @param string $id
	 * @param object $instance
	 * @return void
	 */
	public function instance( string $id, $instance ): void {
		$this->instances[ $id ] = $instance;
	}

	/**
	 * Determine if an id is bound/known.
	 *
	 * @param string $id
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] ) || isset( $this->bindings[ $id ] ) || isset( $this->singletons[ $id ] ) || class_exists( $id );
	}

	/**
	 * Resolve an id or class name.
	 *
	 * @param string               $id
	 * @param array<string,mixed>  $parameters
	 * @return mixed
	 */
	public function make( string $id, array $parameters = array() ) {
		// Existing instance
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		// Singleton: build once and cache
		if ( isset( $this->singletons[ $id ] ) ) {
			$this->instances[ $id ] = $this->build( $this->singletons[ $id ], $parameters );
			return $this->instances[ $id ];
		}

		// Factory binding
		if ( isset( $this->bindings[ $id ] ) ) {
			return $this->build( $this->bindings[ $id ], $parameters );
		}

		// FQCN auto-wire
		if ( class_exists( $id ) ) {
			return $this->build( $id, $parameters );
		}

		throw new \RuntimeException( sprintf( 'Container: Cannot resolve "%s".', $id ) );
	}

	/**
	 * Invoke a callable with auto-resolved dependencies.
	 *
	 * @param callable|array|string $callable
	 * @param array<string,mixed>   $parameters
	 * @return mixed
	 */
	public function call( $callable, array $parameters = array() ) {
		if ( is_string( $callable ) && strpos( $callable, '@' ) !== false ) {
			// "Class@method" syntax
			list( $class, $method ) = explode( '@', $callable, 2 );
			$instance = $this->make( $class );
			return $instance->$method( ...$this->resolveParameters( array(), $parameters, new \ReflectionMethod( $instance, $method ) ) );
		}

		if ( is_array( $callable ) ) {
			$ref = new \ReflectionMethod( $callable[0], $callable[1] );
			return $callable[0]->{$callable[1]}( ...$this->resolveParameters( array(), $parameters, $ref ) );
		}

		if ( is_object( $callable ) && method_exists( $callable, '__invoke' ) ) {
			$ref = new \ReflectionMethod( $callable, '__invoke' );
			return $callable( ...$this->resolveParameters( array(), $parameters, $ref ) );
		}

		$ref = new \ReflectionFunction( $callable );
		return $callable( ...$this->resolveParameters( array(), $parameters, $ref ) );
	}

	/**
	 * Tag an instance for grouped retrieval.
	 *
	 * @param object $instance
	 * @param string $tag
	 * @return void
	 */
	public function tag( $instance, string $tag ): void {
		if ( ! isset( $this->tags[ $tag ] ) ) {
			$this->tags[ $tag ] = array();
		}
		$this->tags[ $tag ][] = $instance;
	}

	/**
	 * Get all instances for a tag.
	 *
	 * @param string $tag
	 * @return array<int,object>
	 */
	public function tagged( string $tag ): array {
		return $this->tags[ $tag ] ?? array();
	}

	/**
	 * Build a concrete binding/class.
	 *
	 * @param callable|string|object $concrete
	 * @param array<string,mixed>    $parameters
	 * @return mixed
	 */
	protected function build( $concrete, array $parameters = array() ) {
		// Already an object → return as-is.
		if ( is_object( $concrete ) && ! ( $concrete instanceof \Closure ) ) {
			return $concrete;
		}

		// Factory closure/callable.
		if ( is_callable( $concrete ) && ! is_string( $concrete ) ) {
			return $concrete( $this, $parameters );
		}

		// FQCN string → reflect and auto-wire.
		if ( is_string( $concrete ) ) {
			$ref = new ReflectionClass( $concrete );

			if ( ! $ref->isInstantiable() ) {
				throw new \RuntimeException( sprintf( 'Container: Class "%s" is not instantiable.', $concrete ) );
			}

			$ctor = $ref->getConstructor();
			if ( null === $ctor || 0 === $ctor->getNumberOfParameters() ) {
				return new $concrete();
			}

			$args = $this->resolveParameters( $ctor->getParameters(), $parameters );
			return $ref->newInstanceArgs( $args );
		}

		throw new \RuntimeException( 'Container: Unsupported binding type.' );
	}

	/**
	 * Resolve parameters for constructors/callables using provided overrides
	 * and container lookups for type-hinted class dependencies.
	 *
	 * @param ReflectionParameter[]        $signature
	 * @param array<string,mixed>          $overrides
	 * @param \ReflectionFunctionAbstract|null $ref
	 * @return array<int,mixed>
	 */
	protected function resolveParameters( array $signature, array $overrides, $ref = null ): array {
		// If a reflection object is given (call/ReflectionFunction/Method), use its params.
		if ( $ref ) {
			$signature = $ref->getParameters();
		}

		$args = array();

		foreach ( $signature as $param ) {
			$name = $param->getName();

			// Explicit override by name.
			if ( array_key_exists( $name, $overrides ) ) {
				$args[] = $overrides[ $name ];
				continue;
			}

			// Type-hinted class → resolve from container.
			$type = $param->getType();
			if ( $type && ! $type->isBuiltin() ) {
				$class = $type instanceof \ReflectionNamedType ? $type->getName() : null;
				if ( $class ) {
					$args[] = $this->make( $class );
					continue;
				}
			}

			// Default value or null.
			if ( $param->isDefaultValueAvailable() ) {
				$args[] = $param->getDefaultValue();
			} else {
				$args[] = null;
			}
		}

		return $args;
	}
}

