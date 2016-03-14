<?php

class Scribunto_LuaHtmlLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'HtmlLibraryTests';

	protected function setUp() {
		parent::setUp();

		// For strip marker test
		$markers = array(
			'nowiki' => Parser::MARKER_PREFIX . '-test-nowiki-' . Parser::MARKER_SUFFIX,
		);
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( 'mw.html.stripMarkers = ...', 'fortest' ),
			$markers
		);
	}

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'HtmlLibraryTests' => __DIR__ . '/HtmlLibraryTests.lua',
		);
	}
}
