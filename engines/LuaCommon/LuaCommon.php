<?php

abstract class Scribunto_LuaEngine extends ScribuntoEngineBase {
	public $interpreter, $loaded = false;
	protected $executeModuleFunc;

	abstract function newInterpreter();

	protected function newModule( $text, $chunkName ) {
		return new Scribunto_LuaModule( $this, $text, $chunkName );
	}

	public function load() {
		if( $this->loaded ) {
			return;
		}

		if( !MWInit::classExists( 'luasandbox' ) ) {
			throw new MWException( 'luasandbox PHP extension is not installed' );
		}

		$this->interpreter = $this->newInterpreter();
		$this->interpreter->registerLibrary( 'mw', array( 'import' => array( $this, 'importModule' ) ) );
		$module = $this->loadLibraryFromFile( dirname( __FILE__ ) .'/lualib/mw.lua' );
		$this->executeModuleFunc = $module['executeModule'];

		$this->loaded = true;
	}

	public function executeModule( $chunk ) {
		return $this->interpreter->callFunction( $this->executeModuleFunc, $chunk );
	}

	protected function loadLibraryFromFile( $fileName ) {
		$code = file_get_contents( $fileName );
		if ( $code === false ) {
			throw new MWException( 'Lua file does not exist: ' . $fileName );
		}
		$module = $this->interpreter->loadString( $code, '@' . basename( $fileName ) );
		$ret = $this->interpreter->callFunction( $module );
		return $ret[0];
	}

	public function getGeSHiLanguage() {
		return 'lua';
	}
	
	public function getCodeEditorLanguage() {
		return 'lua';
	}

	/**
	 * Workalike for luaL_checktype()
	 *
	 * @param $funcName The Lua function name, for use in error messages
	 * @param $args The argument array
	 * @param $index0 The zero-based argument index
	 * @param $type The type name as given by gettype()
	 * @param $msgType The type name used in the error message
	 */
	public function checkType( $funcName, $args, $index0, $type, $msgType ) {
		if ( !isset( $args[$index0] ) || gettype( $args[$index0] ) !== $type ) {
			$index1 = $index0 + 1;
			throw new Scribunto_LuaError( "bad argument #$index1 to '$funcName' ($msgType expected)" );
		}
	}

	/**
	 * Workalike for luaL_checkstring()
	 *
	 * @param $funcName The Lua function name, for use in error messages
	 * @param $args The argument array
	 * @param $index0 The zero-based argument index
	 */
	public function checkString( $funcName, $args, $index0 ) {
		$this->checkType( $funcName, $args, $index0, 'string', 'string' );
	}

	/**
	 * Workalike for luaL_checknumber()
	 *
	 * @param $funcName The Lua function name, for use in error messages
	 * @param $args The argument array
	 * @param $index0 The zero-based argument index
	 */
	public function checkNumber( $funcName, $args, $index0 ) {
		$this->checkType( $funcName, $args, $index0, 'double', 'number' );
	}

	function importModule() {
		$args = func_get_args();
		$this->checkString( 'import', $args, 0 );
		$title = Title::makeTitleSafe( NS_MODULE, $args[0] );
		if ( !$title ) {
			throw new Scribunto_LuaError( 'no such module' );
		}
		$module = $this->fetchModuleFromParser( $title );
		return $module->getContents();
	}
}

class Scribunto_LuaModule extends ScribuntoModuleBase {
	protected $initialized;

	function newFunction( $name, $contents ) {
		return new Scribunto_LuaFunction( $this, $name, $contents );
	}

	function initialize() {
		if( $this->initialized ) {
			return;
		}
		$this->engine->load();

		// FIXME: caching?

		$this->body = $this->engine->interpreter->loadString(
			$this->code, 
			// Prepending an "@" to the chunk name makes Lua think it is a file name
			'@' . $this->chunkName );
		$output = $this->engine->executeModule( $this->body );
		
		if( !$output ) {
			throw new ScribuntoException( 'scribunto-luasandbox-noreturn' );
		}
		if( count( $output ) > 2 ) {
			throw new ScribuntoException( 'scribunto-luasandbox-toomanyreturns' );
		}
		if( !is_array( $output[0] ) ) {
			throw new ScribuntoException( 'scribunto-luasandbox-notarrayreturn' );
		}
		
		$this->contents = $output[0];
		$this->functions = array();
		foreach( $this->contents as $key => $content ) {
			if( $content instanceof LuaSandboxFunction )
				$this->functions[] = $key;
		}

		$this->initialized = true;
	}

	function getFunction( $name ) {
		$this->initialize();

		if( isset( $this->contents[$name] ) ) {
			return new Scribunto_LuaFunction( $this, $name, $this->contents[$name] );
		} else {
			return null;
		}
	}

	function getFunctions() {
		$this->initialize();
		return $this->functions;
	}

	function getContents() {
		$this->initialize();
		return $this->contents;
	}
}

class Scribunto_LuaFunction extends ScribuntoFunctionBase {
	public function call( $args, $frame ) {
		array_unshift( $args, $this->contents );
		$result = call_user_func_array( 
			array( $this->engine->interpreter, 'callFunction' ), $args );
		if ( isset( $result[0] ) ) {
			return $result[0];
		} else {
			return null;
		}
	}
}

class Scribunto_LuaError extends ScribuntoException {
	var $luaMessage;

	function __construct( $message ) {
		$this->luaMessage = $message;
		parent::__construct( 'scribunto-lua-error', array( 'args' => array( $message ) ) );
	}

	function getLuaMessage() {
		return $this->luaMessage;
	}
}
