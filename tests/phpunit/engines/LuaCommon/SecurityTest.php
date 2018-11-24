<?php
class Scribunto_LuaSecurityTest extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'SecurityTests';
	protected function getTestModules() {
		return parent::getTestModules() + [
			'SecurityTests' => __DIR__ . '/SecurityTests.lua',
		];
	}
}
