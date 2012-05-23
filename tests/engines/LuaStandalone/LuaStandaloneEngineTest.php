<?php

if ( php_sapi_name() !== 'cli' ) exit;
require_once( dirname( __FILE__ ) .'/../LuaCommon/LuaEngineTest.php' );

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
			'StandloneTests' => dirname( __FILE__ ) . '/StandaloneTests.lua'
		);
	}
}

