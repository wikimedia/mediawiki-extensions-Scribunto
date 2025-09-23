<?php

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Config\ConfigException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaInterpreterBadVersionError;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaInterpreterNotFoundError;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxInterpreter;

/**
 * Factory class to create a new lua engine
 */
class EngineFactory {
	/** @internal For use by ServiceWiring */
	public const CONSTRUCTOR_OPTIONS = [
		'ScribuntoDefaultEngine',
		'ScribuntoEngineConf',
	];

	private readonly ?string $defaultEngine;
	/** @var array<string,array> */
	private readonly array $engineConf;

	/** @internal For use by ServiceWiring */
	public function __construct(
		ServiceOptions $options,
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->defaultEngine = $options->get( 'ScribuntoDefaultEngine' );
		$this->engineConf = $options->get( 'ScribuntoEngineConf' );
	}

	/**
	 * Create a new engine object with specified parameters.
	 */
	public function newEngine( array $options = [] ): ScribuntoEngineBase {
		if ( isset( $options['factory'] ) ) {
			return $options['factory']( $options );
		}
		if ( isset( $options['implementation'] ) ) {
			$implementation = $options['implementation'];
			unset( $options['implementation'] );
			switch ( $implementation ) {
				case 'autodetect':
					return $this->newAutodetectEngine( $options );
				default:
					throw new ConfigException( "Unknown implementation" );
			}
		}

		$class = $options['class'];
		return new $class( $options );
	}

	/**
	 * Create a new engine object with default parameters
	 *
	 * @param array $extraOptions Extra options to pass to the constructor,
	 *  in addition to the configured options
	 */
	public function newDefaultEngine( array $extraOptions = [] ): ScribuntoEngineBase {
		if ( !$this->defaultEngine ) {
			throw new ConfigException(
				'Scribunto extension is enabled but $wgScribuntoDefaultEngine is not set'
			);
		}

		// @phan-suppress-next-line PhanAccessReadOnlyProperty False positive, related to phan#5062
		if ( !isset( $this->engineConf[$this->defaultEngine] ) ) {
			throw new ConfigException( 'Invalid scripting engine is specified in $wgScribuntoDefaultEngine' );
		}
		$options = $extraOptions + $this->engineConf[$this->defaultEngine];
		return $this->newEngine( $options );
	}

	/**
	 * If luasandbox is installed and usable then use it,
	 * otherwise
	 */
	private function newAutodetectEngine( array $options ): ScribuntoEngineBase {
		$engine = 'luastandalone';
		try {
			LuaSandboxInterpreter::checkLuaSandboxVersion();
			$engine = 'luasandbox';
		} catch ( LuaInterpreterNotFoundError | LuaInterpreterBadVersionError ) {
			// pass
		}

		return $this->newEngine( $options + $this->engineConf[$engine] );
	}
}
