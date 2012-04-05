<?php

class LuaSandboxEngine extends ScriptingEngineBase {
	public $sandbox, $options, $loaded = false;

	public function newModule( $text, $chunkName ) {
		return new LuaSandboxEngineModule( $this, $text, $chunkName );
	}

	public function load() {
		if( $this->loaded ) {
			return;
		}

		if( !MWInit::classExists( 'luasandbox' ) ) {
			throw new MWException( 'luasandbox PHP extension is not installed' );
		}

		$this->sandbox = new LuaSandbox;
		$this->sandbox->setMemoryLimit( $this->options['memoryLimit'] );
		$this->sandbox->setCPULimit( $this->options['cpuLimit'] );
		$this->sandbox->registerLibrary( 'mw', array( 'import' => array( $this, 'importModule' ) ) );
		
		$this->loaded = true;
	}

	protected function getModuleClassName() {
		return 'LuaSandboxEngineModule';
	}

	public function getGeSHiLanguage() {
		return 'lua';
	}
	
	public function getCodeEditorLanguage() {
		return 'lua';
	}
	
	public function getLimitReport() {
		$this->load();
		
		$usage = $this->sandbox->getMemoryUsage();
		if( $usage < 8 * 1024 ) {
			$usageStr = $usage . " bytes";
		} elseif( $usage < 8 * 1024 * 1024 ) {
			$usageStr = round( $usage / 1024, 2 ) . " kilobytes";
		} else {
			$usageStr = round( $usage / 1024 / 1024, 2 ) . " megabytes";
		}

		return "Lua scripts memory usage: {$usageStr}\n";
	}
	
	function importModule() {
		$args = func_get_args();
		if( count( $args ) < 1 ) {
			// FIXME: LuaSandbox PHP extension should provide proper context
			throw new ScriptingException( 'scripting-common-toofewargs',
				array( 'args' => array( 'mw.import' ) ) );
		}

		$title = Title::makeTitleSafe( NS_MODULE, $args[0] );
		if ( !$title ) {
			throw new ScriptingException( 'scripting-common-nosuchmodule' );
		}
		$module = $this->fetchModuleFromParser( $title );
		return $module->getContents();
	}
}

class LuaSandboxEngineModule extends ScriptingModuleBase {
	protected $initialized;

	function initialize() {
		if( $this->initialized ) {
			return;
		}
		$this->engine->load();

		// FIXME: caching?

		try {
			$this->body = $this->engine->sandbox->loadString(
				$this->code, 
				// Prepending an "@" to the chunk name makes Lua think it is a file name
				'@' . $this->chunkName );
			$output = $this->body->call();
		} catch( LuaSandboxError $e ) {
			throw new ScriptingException( 'scripting-luasandbox-error', 
				array( 'args' => array( $e->getMessage() ) ) );
		}
		
		if( !$output ) {
			throw new ScriptingException( 'scripting-luasandbox-noreturn' );
		}
		if( count( $output ) > 2 ) {
			throw new ScriptingException( 'scripting-luasandbox-toomanyreturns' );
		}
		if( !is_array( $output[0] ) ) {
			throw new ScriptingException( 'scripting-luasandbox-notarrayreturn' );
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
			return new LuaSandboxEngineFunction( $this, $name, $this->contents[$name] );
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

class LuaSandboxEngineFunction extends ScriptingFunctionBase {
	public function call( $args, $frame ) {
		try {
			$result = call_user_func_array( array( $this->contents, 'call' ), $args );
		} catch( LuaSandboxError $e ) {
			throw new ScriptingException( 'scripting-luasandbox-error', 
				array( 'args' => array( $e->getMessage() ) ) );
		}
		
		if ( isset( $result[0] ) ) {
			return $result[0];
		} else {
			return null;
		}
	}
}
