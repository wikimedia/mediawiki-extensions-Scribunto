<?php

if ( php_sapi_name() !== 'cli' ) exit;
require_once( __DIR__ . '/../LuaCommon/LuaEngineTest.php' );

class Scribunto_LuaSandboxEngineTest extends Scribunto_LuaEngineTest {
	var $stdOpts = array(
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
		'allowEnvFuncs' => true,
	);

	function newEngine( $opts = array() ) {
		$opts = $opts + $this->stdOpts;
		return new Scribunto_LuaSandboxEngine( $opts );
	}

	function getTestModules() {
		return parent::getTestModules() + array(
			'SandboxTests' => __DIR__ . '/SandboxTests.lua'
		);
	}

	function provideSandboxTests() {
		return $this->getTestProvider( 'SandboxTests' );
	}

	/** @dataProvider provideSandboxTests */
	function testSandboxTests( $key, $testName, $expected ) {
		$this->runTestProvider( 'SandboxTests', $key, $testName, $expected );
	}
}

