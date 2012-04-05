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
		$modules = array(),
		$moduleTitles = array();

	/**
	 * Creates a new module object within this engine
	 */
	abstract protected function newModule( $title, $code, $revisionID, $source );

	/**
	 * Returns the default options of the engine.
	 */
	public function getDefaultOptions() {
		return array();
	}

	/**
	 * Is called by setOptions() in order to notify the engine
	 * that the options were changed.
	 */
	protected function updateOptions() { /* No-op */ }

	/**
	 * Constructor.
	 * 
	 * @param $parser Parser Wikitext parser
	 */
	public function __construct( $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Loads the module either from instance cache or from the actual revision.
	 * Does not initialize the module, i.e. do not expect it to complain if the module
	 * text is garbage or has syntax error. Returns a module or throws an exception.
	 * 
	 * @param $title Title/string The title or the name of the module.
	 * @param $source string Source of the module
	 * @return ScriptingEngineModule
	 */
	public function getModule( $title, $source = Scripting::LOCAL ) {
		// Convert string to title
		if( !$title instanceof Title ) {
			$titleobj = Title::newFromText( (string)$title, NS_MODULE );
			if( !$titleobj || $titleobj->getNamespace() != NS_MODULE ) {
				throw new ScriptingException( 'badtitle', 'common' );	// scripting-exceptions-common-badtitle
			}
			$title = $titleobj;
		}

		// Check if it is already loaded
		$key = $title->getPrefixedText();
		if( !isset( $this->modules[$key] ) ) {
			// Fetch the text
			$rev = $this->getModuleRev( $title, $source );
			if( !$rev ) {
				throw new ScriptingException( 'nosuchmodule', 'common' );	// scripting-exceptions-common-nosuchmodule
			}
			if( $rev->getTitle()->getNamespace() != NS_MODULE ) {
				throw new ScriptingException( 'badnamespace', 'common' );	// scripting-exceptions-common-badnamespace
			}

			// Create the class
			$this->modules[$key] = $this->newModule( $title, $rev->getText(), $rev->getID(), $source );
			$this->moduleTitles[] = $title;
		}
		return $this->modules[$key];
	}

	/**
	 * Fetches the revision for given module title.
	 */
	private function getModuleRev( $title, $source ) {
		if( $source != Scripting::LOCAL ) {
			throw new MWException( 'Non-local scripts are not supported at this point' );
		}

		$rev = Revision::newFromTitle( $title );
		if( $rev && $real = Title::newFromRedirect( $rev->getText() ) ) {
			$rev = Revision::newFromTitle( $real );
		}
		return $rev;
	}

	/**
	 * Sets the engine-specific options from $wgScriptingEngineConf.
	 */
	function setOptions( $options ) {
		$this->options = array_merge( $this->getDefaultOptions(), $options );
		$this->updateOptions();
	}

	/**
	 * Validates the script and returns an array of the syntax errors for the
	 * given code.
	 * 
	 * @param $code Code to validate
	 * @param $title Title of the code page
	 * @return array
	 */
	function validate( $code, $title ) {
		$module = $this->newModule( $title, $code, 0, Scripting::LOCAL );

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
	 * Returns the titles of all the modules used by this instance of the
	 * engine.
	 */
	public function getUsedModules() {
		return $this->moduleTitles;
	}

	/**
	 * Invalidates the cache of the given module by its title. Should be
	 * redefined if the engine uses any form of bytecode or other cache.
	 */
	function invalidateModuleCache( $title ) {
		/* No-op by default */
	}

	/**
	 * Get the language for GeSHi syntax highlighter.
	 */
	function getGeSHiLangauge() {
		return false;
	}
	
	/**
	 * Get the langauge for Ace code editor.
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
	var $engine, $title, $code, $revisionID, $source;

	public function __construct( $engine, $title, $code, $revisionID, $source ) {
		$this->engine = $engine;
		$this->title = $title;
		$this->code = $code;
		$this->revisionID = $revisionID;
		$this->source = $source;
	}

	/** Accessors **/
	public function getEngine()     { return $this->engine; }
	public function getTitle()      { return $this->title; }
	public function getCode()       { return $this->code; }
	public function getRevisionID() { return $this->revisionID; }
	public function getSource()     { return $this->source; }
	
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
