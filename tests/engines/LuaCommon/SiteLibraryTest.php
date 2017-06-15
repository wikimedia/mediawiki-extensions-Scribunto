<?php

// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaSiteLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'SiteLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'SiteLibraryTests' => __DIR__ . '/SiteLibraryTests.lua',
		];
	}
}
