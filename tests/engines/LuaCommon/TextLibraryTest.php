<?php

class Scribunto_LuaTextLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'TextLibraryTests';

	protected function setUp() {
		parent::setUp();

		// For unstrip test
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( 'mw.text.stripTest = ...', 'fortest' ),
			$this->getEngine()->getParser()->insertStripItem( 'ok' )
		);
	}

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'TextLibraryTests' => __DIR__ . '/TextLibraryTests.lua',
		);
	}
}
