<?php

abstract class Scribunto_LuaEngine extends ScribuntoEngineBase {
	protected $loaded = false;
	protected $executeModuleFunc, $interpreter;
	protected $mw;
	protected $currentFrame = false;
	protected $expandCache = array();

	const MAX_EXPAND_CACHE_SIZE = 100;

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
		$this->mw = $this->loadLibraryFromFile( dirname( __FILE__ ) .'/lualib/mw.lua' );

		$this->loadLibraryFromFile( dirname( __FILE__ ) .'/lualib/package.lua' );

		$this->interpreter->registerLibrary( 'mw_php', 
			array(
				'loadPackage' => array( $this, 'loadPackage' ),
				'parentFrameExists' => array( $this, 'parentFrameExists' ),
				'getExpandedArgument' => array( $this, 'getExpandedArgument' ),
				'getAllExpandedArguments' => array( $this, 'getAllExpandedArguments' ),
				'expandTemplate' => array( $this, 'expandTemplate' ),
				'preprocess' => array( $this, 'preprocess' ),
			) );

		$this->interpreter->callFunction( $this->mw['setup'],
			array( 'allowEnvFuncs' => $this->options['allowEnvFuncs'] ) );
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
		return $this->getInterpreter()->callFunction( $this->mw['executeModule'], $chunk );
	}

	/**
	 * Execute a module function chunk
	 */
	public function executeFunctionChunk( $chunk, $frame ) {
		$oldFrame = $this->currentFrame;
		$this->currentFrame = $frame;
		$result = $this->getInterpreter()->callFunction(
			$this->mw['executeFunction'],
			$chunk );
		$this->currentFrame = $oldFrame;
		return $result;
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

	public function runConsole( $params ) {
		/**
		 * TODO: provide some means for giving correct line numbers for errors
		 * in console input, and for producing an informative error message
		 * if there is an error in prevQuestions.
		 *
		 * Maybe each console line could be evaluated as a different chunk, 
		 * apparently that's what lua.c does.
		 */
		$code = "return function (__init)\n" .
			"local p = mw.executeModule(__init)\n" .
			"local print = mw.log\n";
		foreach ( $params['prevQuestions'] as $q ) {
			if ( substr( $q, 0, 1 ) === '=' ) {
				$code .= "print(" . substr( $q, 1 ) . ")";
			} else {
				$code .= $q;
			}
			$code .= "\n";
		}
		$code .= "mw.clearLogBuffer()\n";
		if ( substr( $params['question'], 0, 1 ) === '=' ) {
			// Treat a statement starting with "=" as a return statement, like in lua.c
			$code .= "return tostring(" . substr( $params['question'], 1 ) . "), mw.getLogBuffer()\n";
		} else {
			$code .= $params['question'] . "\n" .
				"return nil, mw.getLogBuffer()\n";
		}
		$code .= "end\n";

		$contentModule = $this->newModule( 
			$params['content'], $params['title']->getPrefixedDBkey() );
		$contentInit = $contentModule->getInitChunk();

		$consoleModule = $this->newModule( $code, wfMsg( 'scribunto-console-current-src' ) );
		$consoleInit = $consoleModule->getInitChunk();
		$ret = $this->getInterpreter()->callFunction( $consoleInit );
		$func = $ret[0];
		$ret = $this->getInterpreter()->callFunction( $func, $contentInit );
		return array(
			'return' => isset( $ret[0] ) ? $ret[0] : null,
			'print' => isset( $ret[1] ) ? $ret[1] : '',
		);
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
	 * Handler for the mw_php.loadPackage() callback. Load the specified
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

	/**
	 * Helper function for the implementation of frame methods
	 */
	protected function getFrameById( $frameId ) {
		if ( !$this->currentFrame ) {
			return false;
		}
		if ( $frameId === 'parent' ) {
			if ( !isset( $this->currentFrame->parent ) ) {
				return false;
			} else {
				return $this->currentFrame->parent;
			}
		} elseif ( $frameId === 'current' ) {
			return $this->currentFrame;
		} else {
			throw new Scribunto_LuaError( 'invalid frame ID' );
		}
	}

	/**
	 * Handler for mw_php.parentFrameExists()
	 */
	function parentFrameExists() {
		$frame = $this->getFrameById( 'parent' );
		return array( $frame !== false );
	}

	/**
	 * Handler for mw_php.getExpandedArgument()
	 */
	function getExpandedArgument( $frameId, $name ) {
		$args = func_get_args();
		$this->checkString( 'getExpandedArgument', $args, 0 );

		$frame = $this->getFrameById( $frameId );
		if ( $frame === false ) {
			return array();
		}
		$result = $frame->getArgument( $name );
		if ( $result === false ) {
			return array();
		} else {
			return array( $result );
		}
	}

	/**
	 * Handler for mw_php.getAllExpandedArguments()
	 */
	function getAllExpandedArguments( $frameId ) {
		$frame = $this->getFrameById( $frameId );
		if ( $frame === false ) {
			return array();
		}
		return array( $frame->getArguments() );
	}

	/**
	 * Handler for mw_php.expandTemplate
	 */
	function expandTemplate( $frameId, $titleText, $args ) {
		$frame = $this->getFrameById( $frameId );
		if ( $frame === false ) {
			throw new Scribunto_LuaError( 'attempt to call mw.expandTemplate with no frame' );
		}

		$title = Title::newFromText( $titleText, NS_TEMPLATE );
		if ( !$title ) {
			throw new Scribunto_LuaError( 'expandTemplate: invalid title' );
		}

		if ( $frame->depth >= $this->parser->mOptions->getMaxTemplateDepth() ) {
			throw new Scribunto_LuaError( 'expandTemplate: template depth limit exceeded' );
		}
		if ( MWNamespace::isNonincludable( $title->getNamespace() ) ) {
			throw new Scribunto_LuaError( 'expandTemplate: template inclusion denied' );
		}

		list( $dom, $finalTitle ) = $this->parser->getTemplateDom( $title );
		if ( $dom === false ) {
			throw new Scribunto_LuaError( "expandTemplate: template \"$titleText\" does not exist" );
		}
		if ( !$frame->loopCheck( $finalTitle ) ) {
			throw new Scribunto_LuaError( 'expandTemplate: template loop detected' );
		}

		$newFrame = $this->parser->getPreprocessor()->newCustomFrame( $args );
		$text = $this->doCachedExpansion( $newFrame, $dom, 
			array(
				'template' => $finalTitle->getPrefixedDBkey(),
				'args' => $args
			) );
		return array( $text );
	}

	/**
	 * Handler for mw_php.preprocess()
	 */
	function preprocess( $frameId, $text ) {
		$args = func_get_args();
		$this->checkString( 'preprocess', $args, 0 );

		$frame = $this->getFrameById( $frameId );

		if ( !$frame ) {
			throw new Scribunto_LuaError( 'attempt to call mw.preprocess with no frame' );
		}
		$text = $this->doCachedExpansion( $frame, $text,
			array(
				'inputText' => $text,
				'args' => $frame->getArguments()
			) );
		return array( $text );
	}

	function doCachedExpansion( $frame, $input, $cacheKey ) {
		$hash = md5( serialize( $cacheKey ) );
		if ( !isset( $this->expandCache[$hash] ) ) {
			if ( is_scalar( $input ) ) {
				$dom = $this->parser->getPreprocessor()->preprocessToObj( 
					$input, Parser::PTD_FOR_INCLUSION );
			} else {
				$dom = $input;
			}
			if ( count( $this->expandCache ) > self::MAX_EXPAND_CACHE_SIZE ) {
				reset( $this->expandCache );
				$oldHash = key( $this->expandCache );
				unset( $this->expandCache[$oldHash] );
			}
			$this->expandCache[$hash] = $frame->expand( $dom );
		}
		return $this->expandCache[$hash];
	}
}

class Scribunto_LuaModule extends ScribuntoModuleBase {
	protected $initChunk;

	/**
	 * @param $name string
	 * @return Scribunto_LuaFunction
	 */
	protected function newFunction( $name ) {
		return new Scribunto_LuaFunction( $this, $name, $contents ); // FIXME: $contents is undefined
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
	 * Invoke a function within the module. Return the expanded wikitext result.
	 */
	public function invoke( $name, $frame ) {
		$exports = $this->execute();
		if ( !isset( $exports[$name] ) ) {
			throw $this->engine->newException( 'scribunto-common-nosuchfunction' );
		}

		$result = $this->engine->executeFunctionChunk( $exports[$name], $frame );
		if ( isset( $result[0] ) ) {
			return $result[0];
		} else {
			return null;
		}
	}
}

class Scribunto_LuaError extends ScribuntoException {
	var $luaMessage, $lineMap = array();

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

	function setLineMap( $map ) {
		$this->lineMap = $map;
	}

	function getScriptTraceHtml( $options = array() ) {
		if ( !isset( $this->params['trace'] ) ) {
			return false;
		}
		if ( isset( $options['msgOptions'] ) ){
			$msgOptions = $options['msgOptions'];
		} else {
			$msgOptions = array();
		}

		$s = '<ol class="scribunto-trace">';
		foreach ( $this->params['trace'] as $info ) {
			$short_src = $srcdefined = $info['short_src'];
			$currentline = $info['currentline'];
			$linedefined = $info['linedefined'];

			$src = htmlspecialchars( $short_src );
			if ( $currentline > 0 ) {
				$src .= ':' . htmlspecialchars( $currentline );

				$title = Title::newFromText( $short_src );
				if ( $title && $title->getNamespace() === NS_MODULE ) {
					$title->setFragment( '#mw-ce-l' . $currentline );
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
					$msgOptions, $srcdefined, $linedefined );
			}
			$s .= "<li>\n\t" . 
				wfMsgExt( 'scribunto-lua-backtrace-line', $msgOptions, "<strong>$src</strong>", $function ) .
				"\n</li>\n";
		}
		$s .= '</ol>';
		return $s;
	}
}
