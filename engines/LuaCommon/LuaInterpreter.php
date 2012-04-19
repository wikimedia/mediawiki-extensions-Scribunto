<?php

abstract class Scribunto_LuaInterpreter {
	/**
	 * Load a string. Return an object which can later be passed to callFunction.
	 * If there is a pass error, a Scribunto_LuaError will be thrown.
	 *
	 * @param $text The Lua source code
	 * @param $chunkName The chunk name
	 */
	abstract public function loadString( $text, $chunkName );

	/**
	 * Call a Lua function. Return an array of results, with indices starting
	 * at zero. If an error occurs, a Scribunto_LuaError will be thrown.
	 *
	 * @param $func The function object
	 */
	abstract public function callFunction( $func /*...*/ );

	/**
	 * Register a library of functions.
	 *
	 * @param $name The global variable name to be created or added to.
	 * @param $functions An associative array mapping the function name to the 
	 *    callback. The callback may throw a Scribunto_LuaError, which will be
	 *    caught and raised in the Lua code as a Lua error, catchable with 
	 *    pcall(). 
	 */
	abstract public function registerLibrary( $name, $functions );
}

class Scribunto_LuaInterpreterNotFoundError extends MWException {}
