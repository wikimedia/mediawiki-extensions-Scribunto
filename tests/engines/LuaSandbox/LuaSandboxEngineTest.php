<?php

if ( php_sapi_name() !== 'cli' ) exit;
require_once( dirname( __FILE__ ) .'/../LuaCommon/LuaEngineTest.php' );

class Scribunto_LuaSandboxEngineTest extends Scribunto_LuaEngineTest {
	var $stdOpts = array(
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
	);

	function newEngine( $opts = array() ) {
		$opts = $opts + $this->stdOpts;
		return new Scribunto_LuaSandboxEngine( $opts );
	}

	function getTestModules() {
		return parent::getTestModules() + array(
			'SandboxTests' => dirname( __FILE__ ) . '/SandboxTests.lua'
		);
	}
}

