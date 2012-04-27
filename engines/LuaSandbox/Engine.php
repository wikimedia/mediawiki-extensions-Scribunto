<?php

class Scribunto_LuaSandboxEngine extends Scribunto_LuaEngine {
	public $options, $loaded = false;
	
	public function getLimitReport() {
		$this->load();
		$lang = Language::factory( 'en' );
		return
			'Lua time usage: ' . sprintf( "%.3f", $this->interpreter->getCPUUsage() ) . "s\n" .
			'Lua memory usage: ' . $lang->formatSize( $this->interpreter->getMemoryUsage() ) . "\n";
	}

	function newInterpreter() {
		return new Scribunto_LuaSandboxInterpreter( $this, $this->options );
	}
}

class Scribunto_LuaSandboxInterpreter extends Scribunto_LuaInterpreter {
	var $engine, $sandbox, $libraries;

	function __construct( $engine, $options ) {
		if ( !extension_loaded( 'luasandbox' ) ) {
			throw new Scribunto_LuaInterpreterNotFoundError( 
				'The luasandbox extension is not present, this engine cannot be used.' );
		}
		$this->engine = $engine;
		$this->sandbox = new LuaSandbox;
		$this->sandbox->setMemoryLimit( $options['memoryLimit'] );
		$this->sandbox->setCPULimit( $options['cpuLimit'] );
	}

	protected function convertSandboxError( $e ) {
		$opts = array();
		if ( isset( $e->luaTrace ) ) {
			$opts['trace'] = $e->luaTrace;
		}
		$message = $e->getMessage();
		if ( preg_match( '/^(.*?):(\d+): (.*)$/', $message, $m ) ) {
			$opts['module'] = $m[1];
			$opts['line'] = $m[2];
			$message = $m[3];
		}		
		return $this->engine->newLuaError( $message, $opts );
	}

	public function loadString( $text, $chunkName ) {
		try {
			return $this->sandbox->loadString( $text, $chunkName );
		} catch ( LuaSandboxError $e ) {
			throw $this->convertSandboxError( $e );
		}
	}
	
	public function registerLibrary( $name, $functions ) {
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
		$args = func_get_args();
		$func = array_shift( $args );
		try {
			return call_user_func_array( array( $func, 'call' ), $args );
		} catch ( LuaSandboxTimeoutError $e ) {
			throw $this->engine->newException( 'scribunto-common-timeout' );
		} catch ( LuaSandboxError $e ) {
			throw $this->convertSandboxError( $e );
		}
	}

	public function getMemoryUsage() {
		return $this->sandbox->getMemoryUsage();
	}

	public function getCPUUsage() {
		return $this->sandbox->getCPUUsage();
	}
}

class Scribunto_LuaSandboxCallback {
	function __construct( $callback ) {
		$this->callback = $callback;
	}

	function call( /*...*/ ) {
		$args = func_get_args();
		try {
			return call_user_func_array( $this->callback, $args );
		} catch ( Scribunto_LuaError $e ) {
			throw new LuaSandboxRuntimeError( $e->getLuaMessage() );
		}
	}
}
