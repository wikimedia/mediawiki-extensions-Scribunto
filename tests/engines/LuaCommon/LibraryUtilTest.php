<?php

class Scribunto_LuaLibraryUtilTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'LibraryUtilTests';

	function getTestModules() {
		return parent::getTestModules() + array(
			'LibraryUtilTests' => __DIR__ . '/LibraryUtilTests.lua',
		);
	}
}
