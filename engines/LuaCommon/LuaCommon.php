<?php

abstract class Scribunto_LuaEngine extends ScribuntoEngineBase {
	/**
	 * Libraries to load. See also the 'ScribuntoExternalLibraries' hook.
	 * @var array Maps module names to PHP classes or definition arrays
	 */
	protected static $libraryClasses = array(
		'mw.site' => 'Scribunto_LuaSiteLibrary',
		'mw.uri' => 'Scribunto_LuaUriLibrary',
		'mw.ustring' => 'Scribunto_LuaUstringLibrary',
		'mw.language' => 'Scribunto_LuaLanguageLibrary',
		'mw.message' => 'Scribunto_LuaMessageLibrary',
		'mw.title' => 'Scribunto_LuaTitleLibrary',
		'mw.text' => 'Scribunto_LuaTextLibrary',
		'mw.html' => 'Scribunto_LuaHtmlLibrary',
	);

	/**
	 * Paths for modules that may be loaded from Lua. See also the
	 * 'ScribuntoExternalLibraryPaths' hook.
	 * @var array Paths
	 */
	protected static $libraryPaths = array(
		'.',
		'luabit',
		'ustring',
	);

	protected $loaded = false;

	/**
	 * @var Scribunto_LuaInterpreter
	 */
	protected $interpreter;

	/**
	 * @var array
	 */
	protected $mw;

	/**
	 * @var array
	 */
	protected $currentFrames = array();
	protected $expandCache = array();
	protected $availableLibraries = array();
	protected $loadedLibraries = array();

	const MAX_EXPAND_CACHE_SIZE = 100;

	/**
	 * Create a new interpreter object
	 * @return Scribunto_LuaInterpreter
	 */
	abstract function newInterpreter();

	/**
	 * @param string $text
	 * @param string|bool $chunkName
	 * @return Scribunto_LuaModule
	 */
	protected function newModule( $text, $chunkName ) {
		return new Scribunto_LuaModule( $this, $text, $chunkName );
	}

	/**
	 * @param string $message
	 * @param array $params
	 * @return Scribunto_LuaError
	 */
	public function newLuaError( $message, $params = array() ) {
		return new Scribunto_LuaError( $message, $this->getDefaultExceptionParams() + $params );
	}

	public function destroy() {
		// Break reference cycles
		$this->interpreter = null;
		$this->mw = null;
		$this->expandCache = null;
		$this->loadedLibraries = null;
		parent::destroy();
	}

	/**
	 * Initialise the interpreter and the base environment
	 */
	public function load() {
		if( $this->loaded ) {
			return;
		}
		$this->loaded = true;

		try {
			$this->interpreter = $this->newInterpreter();

			$funcs = array(
				'loadPackage',
				'loadPHPLibrary',
				'frameExists',
				'newChildFrame',
				'getExpandedArgument',
				'getAllExpandedArguments',
				'expandTemplate',
				'callParserFunction',
				'preprocess',
				'incrementExpensiveFunctionCount',
				'isSubsting',
				'getFrameTitle',
				'setTTL',
			);

			$lib = array();
			foreach ( $funcs as $name ) {
				$lib[$name] = array( $this, $name );
			}

			$this->registerInterface( 'mwInit.lua', array() );
			$this->mw = $this->registerInterface( 'mw.lua', $lib,
				array( 'allowEnvFuncs' => $this->options['allowEnvFuncs'] ) );

			$this->availableLibraries = $this->getLibraries( 'lua', self::$libraryClasses );
			foreach ( $this->availableLibraries as $name => $def ) {
				$this->instantiatePHPLibrary( $name, $def, false );
			}
		} catch ( Exception $ex ) {
			$this->loaded = false;
			$this->interpreter = null;
			throw $ex;
		}
	}

	/**
	 * Register a Lua Library
	 *
	 * This should be called from the library's PHP module's register() method.
	 *
	 * The value for $interfaceFuncs is used to populate the mw_interface
	 * global that is defined when the library's Lua module is loaded. Values
	 * must be PHP callables, which will be seen in Lua as functions.
	 *
	 * @param $moduleFileName string The path to the Lua portion of the library
	 *         (absolute, or relative to $this->getLuaLibDir())
	 * @param $interfaceFuncs array Populates mw_interface
	 * @param $setupOptions array Passed to the modules setupInterface() method.
	 * @return array Lua package
	 */
	public function registerInterface( $moduleFileName, $interfaceFuncs, $setupOptions = array() ) {
		$this->interpreter->registerLibrary( 'mw_interface', $interfaceFuncs );
		$moduleFileName = $this->normalizeModuleFileName( $moduleFileName );
		$package = $this->loadLibraryFromFile( $moduleFileName );
		if ( !empty( $package['setupInterface'] ) ) {
			$this->interpreter->callFunction( $package['setupInterface'], $setupOptions );
		}
		return $package;
	}

	/**
	 * Return the base path for Lua modules.
	 * @return string
	 */
	public function getLuaLibDir() {
		return __DIR__ . '/lualib';
	}

	/**
	 * Normalize a lua module to its full path. If path does not look like an
	 * absolute path (i.e. begins with DIRECTORY_SEPARATOR or "X:"), prepend
	 * getLuaLibDir()
	 *
	 * @param $file String name of the lua module file
	 * @return string
	 */
	protected function normalizeModuleFileName( $fileName ) {
		if ( !preg_match( '<^(?:[a-zA-Z]:)?' . preg_quote( DIRECTORY_SEPARATOR ) . '>', $fileName ) ) {
			$fileName = "{$this->getLuaLibDir()}/{$fileName}";
		}
		return $fileName;
	}

	/**
	 * Get performance characteristics of the Lua engine/interpreter
	 *
	 * phpCallsRequireSerialization: boolean
	 *   whether calls between PHP and Lua functions require (slow)
	 *   serialization of parameters and return values
	 *
	 * @return array
	 */
	public abstract function getPerformanceCharacteristics();

	/**
	 * Get the current interpreter object
	 * @return Scribunto_LuaInterpreter
	 */
	public function getInterpreter() {
		$this->load();
		return $this->interpreter;
	}

	/**
	 * Replaces the list of current frames, and return a ScopedCallback that
	 * will reset them when it goes out of scope.
	 *
	 * @param PPFrame|null $frame If null, an empty frame with no parent will be used
	 * @return ScopedCallback
	 */
	private function setupCurrentFrames( PPFrame $frame = null ) {
		if ( !$frame ) {
			$frame = $this->getParser()->getPreprocessor()->newFrame();
		}

		$oldFrames = $this->currentFrames;
		$this->currentFrames = array(
			'current' => $frame,
			'parent' => isset( $frame->parent ) ? $frame->parent : null,
		);

		// @todo Once support for PHP 5.3 is dropped, lose $ref and just use
		// $this->currentFrames directly in the callback.
		$ref = &$this->currentFrames;
		return new ScopedCallback( function () use ( &$ref, $oldFrames ) {
			$ref = $oldFrames;
		} );
	}

	/**
	 * Execute a module chunk in a new isolated environment, and return the specified function
	 */
	public function executeModule( $chunk, $functionName, $frame ) {
		$resetFrames = null;
		if ( !$this->currentFrames || !isset( $this->currentFrames['current'] ) ) {
			// Only reset frames if there isn't already current frame
			// $resetFrames is a ScopedCallback, so it has a purpose even though it appears unused.
			$resetFrames = $this->setupCurrentFrames( $frame );
		}

		$retval = $this->getInterpreter()->callFunction( $this->mw['executeModule'], $chunk, $functionName );
		if ( !$retval[0] ) {
			// If we get here, it means we asked for an element from the table the module returned,
			// but it returned something other than a table. In this case, $retval[1] contains the type
			// of what it did returned, instead of the value we asked for.
			throw $this->newException( 'scribunto-lua-notarrayreturn', array( 'args' => array( $retval[1] ) ) );
		}
		return $retval[1];
	}

	/**
	 * Execute a module function chunk
	 */
	public function executeFunctionChunk( $chunk, $frame ) {
		// $resetFrames is a ScopedCallback, so it has a purpose even though it appears unused.
		$resetFrames = $this->setupCurrentFrames( $frame );

		return $this->getInterpreter()->callFunction(
			$this->mw['executeFunction'],
			$chunk );
	}

	/**
	 * Get data logged by modules
	 * @return string Logged data
	 */
	protected function getLogBuffer() {
		if ( !$this->loaded ) {
			return '';
		}
		try {
			$log = $this->getInterpreter()->callFunction( $this->mw['getLogBuffer'] );
			return $log[0];
		} catch ( ScribuntoException $ex ) {
			// Probably time expired, ignore it.
			return '';
		}
	}

	/**
	 * Format the logged data for HTML output
	 * @param string $logs Logged data
	 * @param boolean $localize Whether to localize the message key
	 * @return string HTML
	 */
	protected function formatHtmlLogs( $logs, $localize ) {
		$keyMsg = wfMessage( 'scribunto-limitreport-logs' );
		if ( !$localize ) {
			$keyMsg->inLanguage( 'en' )->useDatabase( false );
		}
		return Html::openElement( 'tr' ) .
			Html::rawElement( 'th', array( 'colspan' => 2 ), $keyMsg->parse() ) .
			Html::closeElement( 'tr' ) .
			Html::openElement( 'tr' ) .
			Html::openElement( 'td', array( 'colspan' => 2 ) ) .
			Html::openElement( 'div', array( 'class' => 'mw-collapsible mw-collapsed' ) ) .
			Html::element( 'pre', array( 'class' => 'scribunto-limitreport-logs' ), $logs ) .
			Html::closeElement( 'div' ) .
			Html::closeElement( 'td' ) .
			Html::closeElement( 'tr' );
	}

	/**
	 * Load a library from the given file and execute it in the base environment.
	 * @param string File name/path to load
	 * @return mixed the export list, or null if there isn't one.
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

	public function runConsole( array $params ) {
		// $resetFrames is a ScopedCallback, so it has a purpose even though it appears unused.
		$resetFrames = $this->setupCurrentFrames();

		/**
		 * TODO: provide some means for giving correct line numbers for errors
		 * in console input, and for producing an informative error message
		 * if there is an error in prevQuestions.
		 *
		 * Maybe each console line could be evaluated as a different chunk,
		 * apparently that's what lua.c does.
		 */
		$code = "return function (__init, exe)\n" .
			"if not exe then exe = function(...) return true, ... end end\n" .
			"local p = select(2, exe(__init) )\n" .
			"__init, exe = nil, nil\n" .
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
			$code .= "local ret = mw.allToString(" . substr( $params['question'], 1 ) . ")\n" .
				"return ret, mw.getLogBuffer()\n";
		} else {
			$code .= $params['question'] . "\n" .
				"return nil, mw.getLogBuffer()\n";
		}
		$code .= "end\n";

		if ( $params['title']->hasContentModel( 'Scribunto' ) ) {
			$contentModule = $this->newModule(
				$params['content'], $params['title']->getPrefixedDBkey() );
			$contentInit = $contentModule->getInitChunk();
			$contentExe = $this->mw['executeModule'];
		} else {
			$contentInit = $params['content'];
			$contentExe = null;
		}

		$consoleModule = $this->newModule(
			$code,
			wfMessage( 'scribunto-console-current-src' )->text()
		);
		$consoleInit = $consoleModule->getInitChunk();
		$ret = $this->getInterpreter()->callFunction( $this->mw['executeModule'], $consoleInit, false );
		$func = $ret[1];
		$ret = $this->getInterpreter()->callFunction( $func, $contentInit, $contentExe );

		return array(
			'return' => isset( $ret[0] ) ? $ret[0] : null,
			'print' => isset( $ret[1] ) ? $ret[1] : '',
		);
	}

	/**
	 * Workalike for luaL_checktype()
	 *
	 * @param string $funcName The Lua function name, for use in error messages
	 * @param array $args The argument array
	 * @param int $index0 The zero-based argument index
	 * @param string $type The type name as given by gettype()
	 * @param string $msgType The type name used in the error message
	 * @throws Scribunto_LuaError
	 */
	public function checkType( $funcName, $args, $index0, $type, $msgType ) {
		if ( !is_array( $type ) ) {
			$type = array( $type );
		}
		if ( !isset( $args[$index0] ) || !in_array( gettype( $args[$index0] ), $type, true ) ) {
			$index1 = $index0 + 1;
			throw new Scribunto_LuaError( "bad argument #$index1 to '$funcName' ($msgType expected)" );
		}
	}

	/**
	 * Workalike for luaL_checkstring()
	 *
	 * @param string $funcName The Lua function name, for use in error messages
	 * @param array $args The argument array
	 * @param int $index0 The zero-based argument index
	 */
	public function checkString( $funcName, $args, $index0 ) {
		$this->checkType( $funcName, $args, $index0, 'string', 'string' );
	}

	/**
	 * Workalike for luaL_checknumber()
	 *
	 * @param string $funcName The Lua function name, for use in error messages
	 * @param array $args The argument array
	 * @param int $index0 The zero-based argument index
	 */
	public function checkNumber( $funcName, $args, $index0 ) {
		$this->checkType( $funcName, $args, $index0, array( 'integer', 'double' ), 'number' );
	}

	/**
	 * Instantiate and register a library.
	 * @param string $name
	 * @param array|string $def
	 * @param bool $loadDeferred
	 * @throws MWException
	 * @return array|null
	 */
	private function instantiatePHPLibrary( $name, $def, $loadDeferred ) {
		$def = $this->availableLibraries[$name];
		if ( is_string( $def ) ) {
			$class = new $def( $this );
		} else {
			if ( !$loadDeferred && !empty( $def['deferLoad'] ) ) {
				return null;
			}
			if ( isset( $def['class'] ) ) {
				$class = new $def['class']( $this );
			} else {
				throw new MWException( "No class for library \"$name\"" );
			}
		}
		$this->loadedLibraries[$name] = $class;
		$ret = $this->loadedLibraries[$name]->register();

		// @todo $this->loadedLibraries[$name] should always be unset when $ret
		// is null, but we can't do that in the non-deferred case yet, since we
		// need to maintain BC with extensions that don't yet return the output
		// of registerInterface.
		if ( $ret === null && $loadDeferred ) {
			unset( $this->loadedLibraries[$name] );
		}

		return $ret;
	}

	/**
	 * Handler for the loadPHPLibrary() callback. Register the specified
	 * library and return its function table. It's not necessary to cache the
	 * function table in the object instance, since there is caching in a
	 * wrapper on the Lua side.
	 */
	function loadPHPLibrary( $name ) {
		$args = func_get_args();
		$this->checkString( 'loadPHPLibrary', $args, 0 );

		$ret = null;
		if ( isset( $this->availableLibraries[$name] ) ) {
			$ret = $this->instantiatePHPLibrary( $name, $this->availableLibraries[$name], true );
		}

		return array( $ret );
	}

	/**
	 * Handler for the loadPackage() callback. Load the specified
	 * module and return its chunk. It's not necessary to cache the resulting
	 * chunk in the object instance, since there is caching in a wrapper on the
	 * Lua side.
	 */
	function loadPackage( $name ) {
		$args = func_get_args();
		$this->checkString( 'loadPackage', $args, 0 );

		# This is what Lua does for its built-in loaders
		$luaName = str_replace( '.', '/', $name ) . '.lua';
		$paths = $this->getLibraryPaths( 'lua', self::$libraryPaths );
		foreach ( $paths as $path ) {
			$fileName = $this->normalizeModuleFileName( "$path/$luaName" );
			if ( !file_exists( $fileName ) ) {
				continue;
			}
			$code = file_get_contents( $fileName );
			$init = $this->interpreter->loadString( $code, "@$luaName" );
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
	 *
	 * @param string $frameId
	 * @return PPFrame
	 *
	 * @throws Scribunto_LuaError
	 */
	protected function getFrameById( $frameId ) {
		if ( $frameId === 'empty' ) {
			return  $this->getParser()->getPreprocessor()->newFrame();
		} elseif ( isset( $this->currentFrames[$frameId] ) ) {
			return $this->currentFrames[$frameId];
		} else {
			throw new Scribunto_LuaError( 'invalid frame ID' );
		}
	}

	/**
	 * Handler for frameExists()
	 *
	 * @param string $frameId
	 * @return array
	 */
	function frameExists( $frameId ) {
		return array( $frameId === 'empty' || isset( $this->currentFrames[$frameId] ) );
	}

	/**
	 * Handler for newChildFrame()
	 *
	 * @throws Scribunto_LuaError
	 */
	function newChildFrame( $frameId, $title, array $args ) {
		if ( count( $this->currentFrames ) > 100 ) {
			throw new Scribunto_LuaError( 'newChild: too many frames' );
		}

		$frame = $this->getFrameById( $frameId );
		if ( $title === false ) {
			$title = $frame->getTitle();
		} else {
			$title = Title::newFromText( $title );
			if ( !$title ) {
				throw new Scribunto_LuaError( 'newChild: invalid title' );
			}
		}
		$args = $this->getParser()->getPreprocessor()->newPartNodeArray( $args );
		$newFrame = $frame->newChild( $args, $title );
		$newFrameId = 'frame' . count( $this->currentFrames );
		$this->currentFrames[$newFrameId] = $newFrame;
		return array( $newFrameId );
	}

	/**
	 * Handler for getTitle()
	 *
	 * @param $frameId
	 *
	 * @return array
	 */
	function getFrameTitle( $frameId ) {
		$frame = $this->getFrameById( $frameId );
		return array( $frame->getTitle()->getPrefixedText() );
	}

	/**
	 * Handler for setTTL()
	 */
	function setTTL( $ttl ) {
		$args = func_get_args();
		$this->checkNumber( 'setTTL', $args, 0 );

		$frame = $this->getFrameById( 'current' );
		if ( is_callable( array( $frame, 'setTTL' ) ) ) {
			$frame->setTTL( $ttl );
		}
	}

	/**
	 * Handler for getExpandedArgument()
	 */
	function getExpandedArgument( $frameId, $name ) {
		$args = func_get_args();
		$this->checkString( 'getExpandedArgument', $args, 0 );

		$frame = $this->getFrameById( $frameId );
		$this->getInterpreter()->pauseUsageTimer();
		$result = $frame->getArgument( $name );
		if ( $result === false ) {
			return array();
		} else {
			return array( $result );
		}
	}

	/**
	 * Handler for getAllExpandedArguments()
	 */
	function getAllExpandedArguments( $frameId ) {
		$frame = $this->getFrameById( $frameId );
		$this->getInterpreter()->pauseUsageTimer();
		return array( $frame->getArguments() );
	}

	/**
	 * Handler for expandTemplate()
	 */
	function expandTemplate( $frameId, $titleText, $args ) {
		$frame = $this->getFrameById( $frameId );
		$title = Title::newFromText( $titleText, NS_TEMPLATE );
		if ( !$title ) {
			throw new Scribunto_LuaError( "expandTemplate: invalid title \"$titleText\"" );
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

		$fargs = $this->getParser()->getPreprocessor()->newPartNodeArray( $args );
		$newFrame = $frame->newChild( $fargs, $finalTitle );
		$text = $this->doCachedExpansion( $newFrame, $dom,
			array(
				'template' => $finalTitle->getPrefixedDBkey(),
				'args' => $args
			) );
		return array( $text );
	}

	/**
	 * Handler for callParserFunction()
	 * @param $frameId
	 * @param $function
	 * @param $args
	 * @throws MWException
	 * @throws Scribunto_LuaError
	 * @return array
	 */
	function callParserFunction( $frameId, $function, $args ) {
		$frame = $this->getFrameById( $frameId );

		# Make zero-based, without screwing up named args
		$args = array_merge( array(), $args );

		# Sort, since we can't rely on the order coming in from Lua
		uksort( $args, function ( $a, $b ) {
			if ( is_int( $a ) !== is_int( $b ) ) {
				return is_int( $a ) ? -1 : 1;
			}
			if ( is_int( $a ) ) {
				return $a - $b;
			}
			return strcmp( $a, $b );
		} );

		# Be user-friendly
		$colonPos = strpos( $function, ':' );
		if ( $colonPos !== false ) {
			array_unshift( $args, trim( substr( $function, $colonPos + 1 ) ) );
			$function = substr( $function, 0, $colonPos );
		}
		if ( !isset( $args[0] ) ) {
			# It's impossible to call a parser function from wikitext without
			# supplying an arg 0. Insist that one be provided via Lua, too.
			throw new Scribunto_LuaError( 'callParserFunction: At least one unnamed parameter ' .
				'(the parameter that comes after the colon in wikitext) ' .
				'must be provided'
			);
		}

		$result = $this->parser->callParserFunction( $frame, $function, $args );
		if ( !$result['found'] ) {
			throw new Scribunto_LuaError( "callParserFunction: function \"$function\" was not found" );
		}

		# Set defaults for various flags
		$result += array(
			'nowiki' => false,
			'isChildObj' => false,
			'isLocalObj' => false,
			'isHTML' => false,
			'title' => false,
		);

		$text = $result['text'];
		if ( $result['isChildObj'] ) {
			$fargs = $this->getParser()->getPreprocessor()->newPartNodeArray( $args );
			$newFrame = $frame->newChild( $fargs, $result['title'] );
			if ( $result['nowiki'] ) {
				$text = $newFrame->expand( $text, PPFrame::RECOVER_ORIG );
			} else {
				$text = $newFrame->expand( $text );
			}
		}
		if ( $result['isLocalObj'] && $result['nowiki'] ) {
			$text = $frame->expand( $text, PPFrame::RECOVER_ORIG );
			$result['isLocalObj'] = false;
		}

		# Replace raw HTML by a placeholder
		if ( $result['isHTML'] ) {
			$text = $this->parser->insertStripItem( $text );
		} elseif ( $result['nowiki'] ) {
			# Escape nowiki-style return values
			$text = wfEscapeWikiText( $text );
		}

		if ( $result['isLocalObj'] ) {
			$text = $frame->expand( $text );
		}

		return array( "$text" );
	}

	/**
	 * Handler for preprocess()
	 */
	function preprocess( $frameId, $text ) {
		$args = func_get_args();
		$this->checkString( 'preprocess', $args, 0 );

		$frame = $this->getFrameById( $frameId );

		if ( !$frame ) {
			throw new Scribunto_LuaError( 'attempt to call mw.preprocess with no frame' );
		}

		// Don't count the time for expanding all the frame arguments against
		// the Lua time limit.
		$this->getInterpreter()->pauseUsageTimer();
		$args = $frame->getArguments();
		$this->getInterpreter()->unpauseUsageTimer();

		$text = $this->doCachedExpansion( $frame, $text,
			array(
				'inputText' => $text,
				'args' => $args,
			) );
		return array( $text );
	}

	/**
	 * Increment the expensive function count, and throw if limit exceeded
	 *
	 * @throws Scribunto_LuaError
	 * @return null
	 */
	public function incrementExpensiveFunctionCount() {
		if ( !$this->getParser()->incrementExpensiveFunctionCount() ) {
			throw new Scribunto_LuaError( "too many expensive function calls" );
		}
		return null;
	}

	/**
	 * Return whether the parser is currently substing
	 *
	 * @return array
	 */
	public function isSubsting() {
		// See Parser::braceSubstitution, OT_WIKI is the switch
		return array( $this->getParser()->OutputType() === Parser::OT_WIKI );
	}

	function doCachedExpansion( $frame, $input, $cacheKey ) {
		$hash = md5( serialize( $cacheKey ) );
		if ( isset( $this->expandCache[$hash] ) ) {
			return $this->expandCache[$hash];
		}

		if ( is_scalar( $input ) ) {
			$input = str_replace( array( "\r\n", "\r" ), "\n", $input );
			$dom = $this->parser->getPreprocessor()->preprocessToObj(
				$input, $frame->depth ? Parser::PTD_FOR_INCLUSION : 0 );
		} else {
			$dom = $input;
		}
		$ret = $frame->expand( $dom );
		if ( !is_callable( array( $frame, 'isVolatile' ) ) || !$frame->isVolatile() ) {
			if ( count( $this->expandCache ) > self::MAX_EXPAND_CACHE_SIZE ) {
				reset( $this->expandCache );
				$oldHash = key( $this->expandCache );
				unset( $this->expandCache[$oldHash] );
			}
			$this->expandCache[$hash] = $ret;
		}
		return $ret;
	}
}

class Scribunto_LuaModule extends ScribuntoModuleBase {
	/**
	 * @var string
	 */
	protected $initChunk;

	/**
	 * @param Scribunto_LuaEngine $engine
	 * @param string $code
	 * @param string|bool $chunkName
	 */
	public function __construct( Scribunto_LuaEngine $engine, $code, $chunkName ) {
		parent::__construct( $engine, $code, $chunkName );
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
	 *
	 * @param string $name
	 * @param PPFrame $frame
	 * @throws ScribuntoException
	 * @return string|null
	 */
	public function invoke( $name, $frame ) {
		$ret = $this->engine->executeModule( $this->getInitChunk(), $name, $frame );

		if ( !isset( $ret ) ) {
			throw $this->engine->newException( 'scribunto-common-nosuchfunction', array( 'args' => array( $name ) ) );
		}
		if ( !$this->engine->getInterpreter()->isLuaFunction( $ret ) ) {
			throw $this->engine->newException( 'scribunto-common-notafunction', array( 'args' => array( $name ) ) );
		}

		$result = $this->engine->executeFunctionChunk( $ret, $frame );
		if ( isset( $result[0] ) ) {
			return $result[0];
		} else {
			return null;
		}
	}
}

class Scribunto_LuaError extends ScribuntoException {
	public $luaMessage, $lineMap = array();

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

	/**
	 * @param array $options Options for message processing. Currently supports:
	 * $options['msgOptions']['content'] to use content language.
	 * @return bool|string
	 */
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
				$function = wfMessage( 'scribunto-lua-in-function', wfEscapeWikiText( $info['name'] ) );
				in_array( 'content', $msgOptions ) ?
					$function = $function->inContentLanguage()->plain() :
					$function = $function->plain();
			} elseif ( $info['what'] == 'main' ) {
				$function = wfMessage( 'scribunto-lua-in-main' );
				in_array( 'content', $msgOptions ) ?
					$function = $function->inContentLanguage()->plain() :
					$function = $function->plain();
			} else {
				// C function, tail call, or a Lua function where Lua can't
				// guess the name
				$function = '?';
			}

			$backtraceLine = wfMessage( 'scribunto-lua-backtrace-line' )
				->rawParams( "<strong>$src</strong>" )
				->params( $function );
			in_array( 'content', $msgOptions ) ?
				$backtraceLine = $backtraceLine->inContentLanguage()->parse() :
				$backtraceLine = $backtraceLine->parse();

			$s .= "<li>\n\t" . $backtraceLine  . "\n</li>\n";
		}
		$s .= '</ol>';
		return $s;
	}
}
