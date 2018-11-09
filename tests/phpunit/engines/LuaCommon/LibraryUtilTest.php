<?php

class Scribunto_LuaLibraryUtilTest extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'LibraryUtilTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LibraryUtilTests' => __DIR__ . '/LibraryUtilTests.lua',
		];
	}
}
