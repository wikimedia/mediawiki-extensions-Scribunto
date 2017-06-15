<?php

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

require_once __DIR__ . '/../LuaCommon/LuaInterpreterTest.php';

/**
 * @group Lua
 * @group LuaStandalone
 */
// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaStandaloneInterpreterTest extends Scribunto_LuaInterpreterTest {
	public $stdOpts = [
		'errorFile' => null,
		'luaPath' => null,
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
	];

	private function getVsize( $pid ) {
		$size = wfShellExec( wfEscapeShellArg( 'ps', '-p', $pid, '-o', 'vsz', '--no-headers' ) );
		return $size * 1024;
	}

	protected function newInterpreter( $opts = [] ) {
		$opts = $opts + $this->stdOpts;
		$engine = new Scribunto_LuaStandaloneEngine( $this->stdOpts );
		return new Scribunto_LuaStandaloneInterpreter( $engine, $opts );
	}

	public function testIOErrorExit() {
		$interpreter = $this->newInterpreter();
		try {
			$interpreter->testquit();
			$this->fail( 'Expected exception not thrown' );
		} catch ( ScribuntoException $ex ) {
			$this->assertSame( 'scribunto-luastandalone-exited', $ex->getMessageName() );
			$this->assertSame( [ '[UNKNOWN]', 42 ], $ex->messageArgs );
		}
	}

	public function testIOErrorSignal() {
		$interpreter = $this->newInterpreter();
		try {
			proc_terminate( $interpreter->proc, 15 );
			// Some dummy protocol interaction to make it see the interpreter went away
			$interpreter->loadString( 'return ...', 'test' );
			$this->fail( 'Expected exception not thrown' );
		} catch ( ScribuntoException $ex ) {
			$this->assertSame( 'scribunto-luastandalone-signal', $ex->getMessageName() );
			$this->assertSame( [ '[UNKNOWN]', 15 ], $ex->messageArgs );
		}
	}

	public function testGetStatus() {
		$startTime = microtime( true );
		if ( php_uname( 's' ) !== 'Linux' ) {
			$this->markTestSkipped( "getStatus() not supported on platforms other than Linux" );
			return;
		}
		$interpreter = $this->newInterpreter();
		$status = $interpreter->getStatus();
		$pid = $status['pid'];
		$this->assertInternalType( 'integer', $status['pid'] );
		$initialVsize = $this->getVsize( $pid );
		$this->assertGreaterThan( 0, $initialVsize, 'Initial vsize' );

		$chunk = $this->getBusyLoop( $interpreter );

		while ( microtime( true ) - $startTime < 1 ) {
			$interpreter->callFunction( $chunk, 100 );
		}
		$status = $interpreter->getStatus();
		$vsize = $this->getVsize( $pid );
		$time = $status['time'] / $interpreter->engine->getClockTick();
		$this->assertGreaterThan( 0.1, $time, 'getStatus() time usage' );
		$this->assertLessThan( 1.5, $time, 'getStatus() time usage' );
		$this->assertEquals( $vsize, $status['vsize'], 'vsize', $vsize * 0.1 );
	}

	public function testFreeFunctions() {
		$interpreter = $this->newInterpreter();

		// Test #1: Make sure freeing actually works
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return function() return "testFreeFunction #1" end', 'test' )
		);
		$id = $ret[0]->id;
		$interpreter->cleanupLuaChunks();
		$this->assertEquals(
			[ 'testFreeFunction #1' ], $interpreter->callFunction( $ret[0] ),
			'Test that function #1 was not freed while a reference exists'
		);
		$ret = null;
		$interpreter->cleanupLuaChunks();
		$testfunc = new Scribunto_LuaStandaloneInterpreterFunction( $interpreter->id, $id );
		try {
			$interpreter->callFunction( $testfunc );
			$this->fail( "Expected exception because function #1 should have been freed" );
		} catch ( Scribunto_LuaError $e ) {
			$this->assertEquals(
				"function id $id does not exist", $e->messageArgs[1],
				'Testing for expected error when calling a freed function #1'
			);
		}

		// Test #2: Make sure constructing a new copy of the function works
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return function() return "testFreeFunction #2" end', 'test' )
		);
		$id = $ret[0]->id;
		$func = new Scribunto_LuaStandaloneInterpreterFunction( $interpreter->id, $id );
		$ret = null;
		$interpreter->cleanupLuaChunks();
		$this->assertEquals(
			[ 'testFreeFunction #2' ], $interpreter->callFunction( $func ),
			'Test that function #2 was not freed while a reference exists'
		);
		$func = null;
		$interpreter->cleanupLuaChunks();
		$testfunc = new Scribunto_LuaStandaloneInterpreterFunction( $interpreter->id, $id );
		try {
			$interpreter->callFunction( $testfunc );
			$this->fail( "Expected exception because function #2 should have been freed" );
		} catch ( Scribunto_LuaError $e ) {
			$this->assertEquals(
				"function id $id does not exist", $e->messageArgs[1],
				'Testing for expected error when calling a freed function #2'
			);
		}

		// Test #3: Make sure cloning works
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return function() return "testFreeFunction #3" end', 'test' )
		);
		$id = $ret[0]->id;
		$func = clone $ret[0];
		$ret = null;
		$interpreter->cleanupLuaChunks();
		$this->assertEquals(
			[ 'testFreeFunction #3' ], $interpreter->callFunction( $func ),
			'Test that function #3 was not freed while a reference exists'
		);
		$func = null;
		$interpreter->cleanupLuaChunks();
		$testfunc = new Scribunto_LuaStandaloneInterpreterFunction( $interpreter->id, $id );
		try {
			$interpreter->callFunction( $testfunc );
			$this->fail( "Expected exception because function #3 should have been freed" );
		} catch ( Scribunto_LuaError $e ) {
			$this->assertEquals(
				"function id $id does not exist", $e->messageArgs[1],
				'Testing for expected error when calling a freed function #3'
			);
		}
	}
}
