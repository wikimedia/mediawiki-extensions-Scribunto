<?php

class Scribunto_LuaSandboxEngine extends Scribunto_LuaEngine {
	public $options, $loaded = false;
	
	public function getLimitReport() {
		$this->load();
		$usage = $this->interpreter->getMemoryUsage();
		$lang = Language::factory( 'en' );
		$usageStr = $lang->formatSize( $usage );
		return "Lua memory usage: {$usageStr}\n";
	}

	function newInterpreter() {
		return new Scribunto_LuaSandboxInterpreter( $this, $this->options );
	}
}

class Scribunto_LuaSandboxInterpreter extends Scribunto_LuaInterpreter {
	var $engine, $sandbox, $libraries;

	function __construct( $engine, $options ) {
		wfDebug( __METHOD__ . "\n" );
		$this->engine = $engine;
		$this->sandbox = new LuaSandbox;
		$this->sandbox->setMemoryLimit( $options['memoryLimit'] );
		$this->sandbox->setCPULimit( $options['cpuLimit'] );
	}

	public function loadString( $text, $chunkName ) {
		wfDebug( __METHOD__ . "\n" );
		try {
			return $this->sandbox->loadString( $text, $chunkName );
		} catch ( LuaSandboxError $e ) {
			throw new Scribunto_LuaError( $e->getMessage() );
		}
	}
	
	public function registerLibrary( $name, $functions ) {
		wfDebug( __METHOD__ . "\n" );
		$realLibrary = array();
		foreach ( $functions as $funcName => $callback ) {
			$realLibrary[$funcName] = array(
				new Scribunto_LuaSandboxCallback( $callback ),
				'call' );
		}
		$this->sandbox->registerLibrary( $name, $realLibrary );

		# TODO: replace this with
		#$this->sandbox->registerVirtualLibrary(
		#	$name, array( $this, 'callback' ), $functions );
	}

	public function callFunction( $func /*, ... */ ) {
		wfDebug( __METHOD__ . "\n" );
		$args = func_get_args();
		$func = array_shift( $args );
		try {
			return call_user_func_array( array( $func, 'call' ), $args );
		} catch ( LuaSandboxError $e ) {
			throw new Scribunto_LuaError( $e->getMessage() );
		}
	}

	public function getMemoryUsage() {
		wfDebug( __METHOD__ . "\n" );
		return $this->sandbox->getMemoryUsage();
	}
}

class Scribunto_LuaSandboxCallback {
	function __construct( $callback ) {
		$this->callback = $callback;
	}

	function call( /*...*/ ) {
		wfDebug( __METHOD__ . "\n" );
		$args = func_get_args();
		try {
			return call_user_func_array( $this->callback, $args );
		} catch ( Scribunto_LuaError $e ) {
			throw new LuaSandboxRuntimeError( $e->getLuaMessage() );
		}
	}
}
