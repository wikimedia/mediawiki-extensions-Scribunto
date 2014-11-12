<?php

class Scribunto_LuaMessageLibraryTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'MessageLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'MessageLibraryTests' => __DIR__ . '/MessageLibraryTests.lua',
		);
	}
}
