<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use MediaWiki\Extension\Scribunto\ScribuntoException;
use MediaWiki\Extension\Scribunto\ScribuntoModuleBase;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Status\Status;

class LuaModule extends ScribuntoModuleBase {
	/**
	 * @var mixed
	 */
	protected $initChunk;

	/**
	 * @var LuaEngine
	 */
	protected $engine;

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
		$ret = $this->engine->executeModule( $this->getInitChunk(), $name, $frame );

		if ( $ret === null ) {
			throw $this->engine->newException(
				'scribunto-common-nosuchfunction', [ 'args' => [ $name ] ]
			);
		}
		if ( !$this->engine->getInterpreter()->isLuaFunction( $ret ) ) {
			throw $this->engine->newException(
				'scribunto-common-notafunction', [ 'args' => [ $name ] ]
			);
		}

		$result = $this->engine->executeFunctionChunk( $ret, $frame );
		return $result[0] ?? null;
	}
}
