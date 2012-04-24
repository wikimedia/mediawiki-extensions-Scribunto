<?php

abstract class Scribunto_LuaEngine extends ScribuntoEngineBase {
	protected $loaded = false;
	protected $executeModuleFunc, $interpreter;

	var $libraryPaths = array(
		'.',
		'luabit',
		'stringtools',
	);

	/**
	 * Create a new interpreter object
	 */
	abstract function newInterpreter();

	protected function newModule( $text, $chunkName ) {
		return new Scribunto_LuaModule( $this, $text, $chunkName );
	}

	public function newLuaError( $message, $params = array() ) {
		return new Scribunto_LuaError( $message, $this->getDefaultExceptionParams() + $params );
	}

	/**
	 * Initialise the interpreter and the base environment
	 */
	public function load() {
		if( $this->loaded ) {
			return;
		}
		$this->loaded = true;

		$this->interpreter = $this->newInterpreter();
		$mw = $this->loadLibraryFromFile( dirname( __FILE__ ) .'/lualib/mw.lua' );
		$this->executeModuleFunc = $mw['executeModule'];
		$this->loadLibraryFromFile( dirname( __FILE__ ) .'/lualib/package.lua' );

		$this->interpreter->registerLibrary( 'mw_internal', 
			array( 'loadPackage' => array( $this, 'loadPackage' ) ) );

		$this->interpreter->callFunction( $mw['setup'] );

	}

	/**
	 * Get the current interpreter object
	 */
	public function getInterpreter() {
		$this->load();
		return $this->interpreter;
	}

	/**
	 * Execute a module chunk in a new isolated environment
	 */
	public function executeModule( $chunk ) {
		return $this->getInterpreter()->callFunction( $this->executeModuleFunc, $chunk );
	}

	/**
	 * Load a library from the given file and execute it in the base environment.
	 * Return the export list, or null if there isn't one.
	 */
	protected function loadLibraryFromFile( $fileName ) {
		$code = file_get_contents( $fileName );
		if ( $code === false ) {
			throw new MWException( 'Lua file does not exist: ' . $fileName );
		}
		# Prepending an "@" to the chunk name makes Lua think it is a filename
		$module = $this->getInterpreter()->loadString( $code, '@' . basename( $fileName ) );
		$ret = $this->getInterpreter()->callFunction( $module );
		return isset( $ret[0] ) ? $ret[0] : null;
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

	/**
	 * Handler for the mw.internal.loadPackage() callback. Load the specified
	 * module and return its chunk. It's not necessary to cache the resulting
	 * chunk in the object instance, since there is caching in a wrapper on the
	 * Lua side.
	 */
	function loadPackage( $name ) {
		$args = func_get_args();
		$this->checkString( 'loadPackage', $args, 0 );

		foreach ( $this->libraryPaths as $path ) {
			$fileName = dirname( __FILE__ ) . "/lualib/$path/$name.lua";
			if ( !file_exists( $fileName ) ) {
				continue;
			}
			$code = file_get_contents( $fileName );
			$init = $this->interpreter->loadString( $code, "@$name.lua" );
			return array( $init );
		}

		$title = Title::newFromText( $name );
		if ( !$title || $title->getNamespace() != NS_MODULE ) {
			return array();
		}

		$module = $this->fetchModuleFromParser( $title );
		if ( $module ) {
			return array( $module->getInitChunk() );
		} else {
			return array();
		}
	}
}

class Scribunto_LuaModule extends ScribuntoModuleBase {
	protected $initChunk;

	protected function newFunction( $name ) {
		return new Scribunto_LuaFunction( $this, $name, $contents );
	}

	public function validate() {
		try {
			$this->getInitChunk();
		} catch ( ScribuntoException $e ) {
			return $e->toStatus();
		}
		return Status::newGood();
	}

	/**
	 * Execute the module function and return the export table.
	 */
	public function execute() {
		$init = $this->getInitChunk();
		$ret = $this->engine->executeModule( $init );
		if( !$ret ) {
			throw $this->engine->newException( 'scribunto-lua-noreturn' );
		}
		if( !is_array( $ret[0] ) ) {
			throw $this->engine->newException( 'scribunto-lua-notarrayreturn' );
		}
		return $ret[0];
	}

	/**
	 * Get the chunk which, when called, will return the export table.
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
	 * Invoke a function within the module. Return the first return value.
	 */
	public function invoke( $name, $args, $frame ) {
		$exports = $this->execute();
		if ( !isset( $exports[$name] ) ) {
			throw $this->engine->newException( 'scribunto-common-nosuchfunction' );
		}

		array_unshift( $args, $exports[$name] );
		$result = call_user_func_array( 
			array( $this->engine->getInterpreter(), 'callFunction' ), $args );
		if ( isset( $result[0] ) ) {
			return $result[0];
		} else {
			return null;
		}
	}
}

class Scribunto_LuaError extends ScribuntoException {
	var $luaMessage;

	function __construct( $message, $options = array() ) {
		$this->luaMessage = $message;
		$options = $options + array( 'args' => array( $message ) );
		if ( isset( $options['module'] ) && isset( $options['line'] ) ) {
			$msg = 'scribunto-lua-error-location';
		} else {
			$msg = 'scribunto-lua-error';
		}

		parent::__construct( $msg, $options );
	}

	function getLuaMessage() {
		return $this->luaMessage;
	}

	function getScriptTraceHtml( $options = array() ) {
		if ( !isset( $this->params['trace'] ) ) {
			return false;
		}
		global $wgUser;
		$skin = $wgUser->getSkin();
		if ( isset( $options['msgOptions'] ) ){
			$msgOptions = $options['msgOptions'];
		} else {
			$msgOptions = array();
		}

		$s = '<ol class="scribunto-trace">';
		foreach ( $this->params['trace'] as $info ) {
			$src = htmlspecialchars( $info['short_src'] );
			if ( $info['currentline'] > 0 ) {
				$src .= ':' . htmlspecialchars( $info['currentline'] );

				$title = Title::newFromText( $info['short_src'] );
				if ( $title && $title->getNamespace() === NS_MODULE ) {
					$title->setFragment( '#mw-ce-l' . $info['currentline'] );
					$src = Html::rawElement( 'a', 
						array( 'href' => $title->getFullURL( 'action=edit' ) ),
						$src );
				}
			}

			if ( strval( $info['namewhat'] ) !== '' ) {
				$function = wfMsgExt( 'scribunto-lua-in-function', $msgOptions, $info['name'] );
			} elseif ( $info['what'] == 'main' ) {
				$function = wfMsgExt( 'scribunto-lua-in-main', $msgOptions );
			} elseif ( $info['what'] == 'C' || $info['what'] == 'tail' ) {
				$function = '?';
			} else {
				$function = wfMsgExt( 'scribunto-lua-in-function-at', 
					$msgOptions, $info['short_src'], $info['linedefined'] );
			}
			$s .= "<li>\n\t" . 
				wfMsgExt( 'scribunto-lua-backtrace-line', $msgOptions, "<strong>$src</strong>", $function ) .
				"\n</li>\n";
		}
		$s .= '</ol>';
		return $s;
	}
}
