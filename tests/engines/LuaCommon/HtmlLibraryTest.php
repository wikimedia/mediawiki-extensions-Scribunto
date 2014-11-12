<?php

class Scribunto_LuaHtmlLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'HtmlLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'HtmlLibraryTests' => __DIR__ . '/HtmlLibraryTests.lua',
		);
	}
}
