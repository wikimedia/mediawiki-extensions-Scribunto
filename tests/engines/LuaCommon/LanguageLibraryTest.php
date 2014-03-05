<?php

class Scribunto_LuaLanguageLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'LanguageLibraryTests';

	function getTestModules() {
		return parent::getTestModules() + array(
			'LanguageLibraryTests' => __DIR__ . '/LanguageLibraryTests.lua',
		);
	}
}
