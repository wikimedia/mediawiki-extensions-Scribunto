<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use MediaWiki\Extension\Scribunto\ScribuntoException;
use MediaWiki\Extension\Scribunto\ScribuntoModuleBase;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Status\Status;

/**
 * @property LuaEngine $engine
 */
class LuaModule extends ScribuntoModuleBase {
	/**
	 * @var mixed
	 */
	protected $initChunk;

	/**
	 * @param LuaEngine $engine
	 * @param string $code
	 * @param string|bool $chunkName
	 */
	public function __construct( LuaEngine $engine, $code, $chunkName ) {
		parent::__construct( $engine, $code, $chunkName );
	}

	/** @inheritDoc */
	public function validate() {
		try {
			$this->getInitChunk();
		} catch ( ScribuntoException $e ) {
			return $e->toStatus();
		}
		return Status::newGood();
	}

	/**
	 * Get the chunk which, when called, will return the export table.
	 * @return mixed
	 */
	public function getInitChunk() {
		if ( !$this->initChunk ) {
			$this->initChunk = $this->engine->getInterpreter()->loadString(
				$this->code,
				// Prepending an "=" to the chunk name avoids truncation or a "[string" prefix
				'=' . $this->chunkName );
		}
		return $this->initChunk;
	}

	/**
	 * Invoke a function within the module. Return the expanded wikitext result.
	 *
	 * @param string $name
	 * @param PPFrame $frame
	 * @throws ScribuntoException
	 * @return string|null
	 */
	public function invoke( $name, $frame ) {
		// $resetModule is a ScopedCallback; it restores the previous module
		// name when it goes out of scope.
		$resetModule = $this->engine->setupCurrentModule( $this->chunkName );

		return $this->engine->invokeFunction( $this->getInitChunk(), $name, $frame );
	}

	/**
	 * Execute the module and return the table it exports.
	 *
	 * The whole table is converted to PHP, losing metatables and anything
	 * else the interpreter cannot represent, so prefer invoke() or
	 * callFunction() where they fit.
	 *
	 * @throws ScribuntoException
	 * @return mixed The module's return value
	 */
	public function getExportTable() {
		// $resetModule is a ScopedCallback; it restores the previous module
		// name when it goes out of scope.
		$resetModule = $this->engine->setupCurrentModule( $this->chunkName );

		return $this->engine->getModuleExportTable( $this->getInitChunk() );
	}

	/**
	 * Call a function within the module.
	 *
	 * @param string $name
	 * @param mixed ...$args
	 * @throws ScribuntoException
	 * @return array The function's return values
	 */
	public function callFunction( string $name, ...$args ): array {
		// $resetModule is a ScopedCallback; it restores the previous module
		// name when it goes out of scope.
		$resetModule = $this->engine->setupCurrentModule( $this->chunkName );

		return $this->engine->callModuleFunction( $this->getInitChunk(), $name, $args );
	}
}
