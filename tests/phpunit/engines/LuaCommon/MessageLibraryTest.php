<?php

// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaMessageLibraryTest extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'MessageLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'MessageLibraryTests' => __DIR__ . '/MessageLibraryTests.lua',
		];
	}
}
