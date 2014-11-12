<?php

class Scribunto_LuaSiteLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'SiteLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'SiteLibraryTests' => __DIR__ . '/SiteLibraryTests.lua',
		);
	}
}
