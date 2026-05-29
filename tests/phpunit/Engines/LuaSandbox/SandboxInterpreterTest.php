<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaSandbox;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaError;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxInterpreter;
use MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon\LuaInterpreterTestBase;
use MediaWiki\Title\Title;

if ( !wfIsCLI() ) {
	exit;
}

/**
 * @group Lua
 * @group LuaSandbox
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxInterpreter
 */
class SandboxInterpreterTest extends LuaInterpreterTestBase {
	/** @var array */
	public $stdOpts = [
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
	];

	protected function newInterpreter( $opts = [] ) {
		$opts += $this->stdOpts;
		$engine = new LuaSandboxEngine( $this->stdOpts + [
			'title' => Title::makeTitle( NS_MAIN, 'Dummy' ),
		] );
		return new LuaSandboxInterpreter( $engine, $opts );
	}

	public function testGetMemoryUsage() {
		$interpreter = $this->newInterpreter();
		$chunk = $interpreter->loadString( 's = string.rep("x", 1000000)', 'mem' );
		$interpreter->callFunction( $chunk );
		$mem = $interpreter->getPeakMemoryUsage();
		$this->assertGreaterThan( 1000000, $mem, 'memory usage' );
		$this->assertLessThan( 10000000, $mem, 'memory usage' );
	}

	/**
	 * Regression test for T426525: convertSandboxError() must not infinitely
	 * recurse when the Lua state is out of memory.
	 *
	 * Before the fix, a LuaSandboxError during error conversion would trigger
	 * getLogBuffer() → callFunction() → convertSandboxError() → ... causing
	 * infinite mutual recursion that exhausted PHP memory.
	 */
	public function testMemoryErrorDoesNotCrash() {
		// Use a very small memory limit so OOM is triggered quickly
		$interpreter = $this->newInterpreter( [ 'memoryLimit' => 1_000_000 ] );
		$chunk = $interpreter->loadString(
			'local t = {} for i = 1, math.huge do t[i] = {} end',
			'oom_test'
		);

		$this->expectException( LuaError::class );
		$this->expectExceptionMessageMatches( '/not enough memory/' );

		// This should throw a LuaError, NOT crash PHP
		$interpreter->callFunction( $chunk );
	}
}
