<?php

require_once( __DIR__ . '/UstringLibraryTest.php' );

class Scribunto_LuaUstringLibraryPureLuaTests extends Scribunto_LuaUstringLibraryTests {
	protected function setUp() {
		parent::setUp();

		// Override mw.ustring with the pure-Lua version
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( '
				local ustring = require( "ustring" )
				ustring.maxStringLength = mw.ustring.maxStringLength
				ustring.maxPatternLength = mw.ustring.maxPatternLength
				mw.ustring = ustring
			', 'fortest' )
		);
	}
}
