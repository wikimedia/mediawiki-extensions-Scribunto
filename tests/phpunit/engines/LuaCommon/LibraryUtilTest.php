<?php

// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaLibraryUtilTest extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'LibraryUtilTests';

	function getTestModules() {
		return parent::getTestModules() + [
			'LibraryUtilTests' => __DIR__ . '/LibraryUtilTests.lua',
		];
	}
}
