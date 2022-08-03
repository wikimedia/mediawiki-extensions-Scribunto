<?php

class LibraryUtilTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'LibraryUtilTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LibraryUtilTests' => __DIR__ . '/LibraryUtilTests.lua',
		];
	}
}
