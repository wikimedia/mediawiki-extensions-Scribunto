<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use LuaSandboxFunction;

abstract class LuaInterpreter {
	/**
	 * Load a string. Return an object which can later be passed to callFunction.
	 * If there is a pass error, a LuaError will be thrown.
	 *
	 * @param string $text The Lua source code
	 * @param string $chunkName
	 * @return mixed
	 */
	abstract public function loadString( $text, $chunkName );

	/**
	 * Call a Lua function. Return an array of results, with indices starting
	 * at zero. If an error occurs, a LuaError will be thrown.
	 *
	 * @param mixed $func The function object
	 * @param mixed ...$args Arguments to the function
	 * @return array
	 */
	abstract public function callFunction( $func, ...$args );

	/**
	 * Wrap a PHP callable as a Lua function, which can be passed back into
	 * Lua. If an error occurs, a LuaError will be thrown.
	 *
	 * @param callable $callable The PHP callable
	 * @return LuaSandboxFunction a Lua function
	 */
	abstract public function wrapPhpFunction( $callable );

	/**
	 * Test whether an object is a Lua function.
	 *
	 * @param mixed|LuaSandboxFunction $object
	 * @return bool
	 */
	abstract public function isLuaFunction( $object );

	/**
	 * Register a library of functions.
	 *
	 * @param string $name The global variable name to be created or added to.
	 * @param array<string,callable> $functions An associative array mapping the function name to the
	 *    callback. The callback may throw a LuaError, which will be
	 *    caught and raised in the Lua code as a Lua error, catchable with
	 *    pcall().
	 */
	abstract public function registerLibrary( $name, array $functions );

	/**
	 * Pause CPU usage and limits
	 * @return void
	 */
	abstract public function pauseUsageTimer();

	/**
	 * Unpause CPU usage and limits
	 * @return void
	 */
	abstract public function unpauseUsageTimer();
}
