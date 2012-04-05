<?php

/**
 * Wikitext scripting infrastructure for MediaWiki: base classes.
 * Copyright (C) 2012 Victor Vasiliev <vasilvv@gmail.com> et al
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Base class for all scripting engines. Includes all code
 * not related to particular modules, like tracking links between
 * modules or loading module texts.
 */
abstract class ScriptingEngineBase {
	protected
		$parser,
		$options,
		$modules = array();

	/**
	 * Creates a new module object within this engine
	 */
	abstract protected function newModule( $text, $chunkName );

	/**
	 * Constructor.
	 * 
	 * @param $options Associative array of options:
	 *    - parser:            A Parser object
	 */
	public function __construct( $options ) {
		$this->options = $options;
		if ( isset( $options['parser'] ) ) {
			$this->parser = $options['parser'];
		}
	}

	/**
	 * Load a module from some parser-defined template loading mechanism and 
	 * register a parser output dependency.
	 *
	 * Does not initialize the module, i.e. do not expect it to complain if the module
	 * text is garbage or has syntax error. Returns a module or throws an exception.
	 *
	 * @param $title The title of the module
	 * @return ScriptingEngineModule
	 */
	function fetchModuleFromParser( Title $title ) {
		list( $text, $finalTitle ) = $this->parser->fetchTemplateAndTitle( $title );
		if ( $text === false ) {
			throw new ScriptingException( 'scripting-common-nosuchmodule' );
		}

		$key = $finalTitle->getPrefixedDBkey();
		if ( !isset( $this->modules[$key] ) ) {
			$this->modules[$key] = $this->newModule( $text, $key );
		}
		return $this->modules[$key];
	}

	/**
	 * Validates the script and returns an array of the syntax errors for the
	 * given code.
	 * 
	 * @param $code Code to validate
	 * @param $title Title of the code page
	 * @return array
	 */
	function validate( $text, $chunkName = false ) {
		$module = $this->newModule( $text, $chunkName );

		try {
			$module->initialize();
		} catch( ScriptingException $e ) {
			return array( $e->getMessage() );
		}

		return array();
	}

	/**
	 * Allows the engine to append their information to the limits
	 * report.
	 */
	public function getLimitsReport() {
		/* No-op by default */
		return '';
	}

	/**
	 * Get the language for GeSHi syntax highlighter.
	 */
	function getGeSHiLanguage() {
		return false;
	}
	
	/**
	 * Get the language for Ace code editor.
	 */
	function getCodeEditorLanguage() {
		return false;
	}
}

/**
 * Class that represents a module. Responsible for initial module parsing
 * and maintaining the contents of the module.
 */
abstract class ScriptingModuleBase {
	var $engine, $code, $chunkName;

	public function __construct( $engine, $code, $chunkName ) {
		$this->engine = $engine;
		$this->code = $code;
		$this->chunkName = $chunkName;
	}

	/** Accessors **/
	public function getEngine()     { return $this->engine; }
	public function getCode()       { return $this->code; }
	public function getChunkName()  { return $this->chunkName; }

	/**
	 * Initialize the module. That means parse it and load the
	 * functions/constants/whatever into the object.
	 * 
	 * Protection of double-initialization is the responsibility of this method.
	 */
	abstract function initialize();
	
	/**
	 * Returns the object for a given function. Should return null if it does not exist.
	 * 
	 * @return ScriptingFunctionBase or null
	 */
	abstract function getFunction( $name );

	/**
	 * Returns the list of the functions in the module.
	 * 
	 * @return array(string)
	 */
	abstract function getFunctions();
}

abstract class ScriptingFunctionBase {
	protected $mName, $mContents, $mModule, $mEngine;
	
	public function __construct( $module, $name, $contents ) {
		$this->name = $name;
		$this->contents = $contents;
		$this->module = $module;
		$this->engine = $module->getEngine();
	}
	
	/**
	 * Calls the function. Returns its first result or null if no result.
	 * 
	 * @param $args array Arguments to the function
	 * @param $frame PPFrame 
	 */
	abstract public function call( $args, $frame );
	
	/** Accessors **/
	public function getName()   { return $this->name; }
	public function getModule() { return $this->module; }
	public function getEngine() { return $this->engine; }
}
