<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

class SiteLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'SiteLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'SiteLibraryTests' => __DIR__ . '/SiteLibraryTests.lua',
		];
	}
}
