<?php

if ( php_sapi_name() !== 'cli' ) exit;
require_once( __DIR__ . '/../LuaCommon/LuaEngineTest.php' );

class Scribunto_LuaStandaloneEngineTest extends Scribunto_LuaEngineTest {
	var $stdOpts = array(
		'errorFile' => null,
		'luaPath' => null,
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
		'allowEnvFuncs' => true,
	);

	function newEngine( $opts = array() ) {
		$opts = $opts + $this->stdOpts;
		return new Scribunto_LuaStandaloneEngine( $opts );
	}

	function getTestModules() {
		return parent::getTestModules() + array(
			'StandaloneTests' => __DIR__ . '/StandaloneTests.lua'
		);
	}

	function provideStandaloneTests() {
		return $this->getTestProvider( 'StandaloneTests' );
	}

	/** @dataProvider provideStandaloneTests */
	function testStandaloneTests( $key, $testName, $expected ) {
		$this->runTestProvider( 'StandaloneTests', $key, $testName, $expected );
	}
}

