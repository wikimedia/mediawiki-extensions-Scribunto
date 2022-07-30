<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

class MessageLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'MessageLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'MessageLibraryTests' => __DIR__ . '/MessageLibraryTests.lua',
		];
	}
}
