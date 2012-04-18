<?php

class Scribunto_LuaStandaloneInterpreterTest extends MediaWikiTestCase {
	var $stdOpts = array(
		'errorFile' => null,
		'luaPath' => null,
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
	);

	function setUp() {
		try {
			$interpreter = $this->newInterpreter();
		} catch ( MWException $e ) {
			if ( preg_match( '/^No Lua interpreter/', $e->getMessage() ) ) {
				$this->markTestSkipped( "No Lua interpreter available" );
				return;
			}
		}
	}

	function getBusyLoop( $interpreter ) {
		$chunk = $interpreter->loadString( '
			local args = {...}
			local x, i
			local s = string.rep("x", 1000000)
			local n = args[1]
			for i = 1, n do
				x = x or string.find(s, "y", 1, true)
			end', 
			'busy' );
		return $chunk;
	}

	function getPassthru( $interpreter ) {
		return $interpreter->loadString( 'return ...', 'passthru' );
	}

	function getVsize( $pid ) {
		$size = wfShellExec( wfEscapeShellArg( 'ps', '-p', $pid, '-o', 'vsz', '--no-headers' ) );
		return $size * 1024;
	}

	function newInterpreter( $opts = array() ) {
		$opts = $opts + $this->stdOpts;
		$engine = new Scribunto_LuaStandaloneEngine( $this->stdOpts );
		return new Scribunto_LuaStandaloneInterpreter( $engine, $opts );
	}

	/** @dataProvider provideRoundtrip */
	function testRoundtrip( /*...*/ ) {
		$args = func_get_args();
		$args = $this->normalizeOrder( $args );
		$interpreter = $this->newInterpreter();
		$passthru = $interpreter->loadString( 'return ...', 'passthru' );
		$finalArgs = $args;
		array_unshift( $finalArgs, $passthru );
		$ret = call_user_func_array( array( $interpreter, 'callFunction' ), $finalArgs );
		$ret = $this->normalizeOrder( $ret );
		$this->assertSame( $args, $ret );
	}

	/** @dataProvider provideRoundtrip */
	function testDoubleRoundtrip( /* ... */ ) {
		$args = func_get_args();
		$args = $this->normalizeOrder( $args );

		$interpreter = $this->newInterpreter();
		$interpreter->registerLibrary( 'test',
			array( 'passthru' => array( $this, 'passthru' ) ) );
		$doublePassthru = $interpreter->loadString( 
			'return test.passthru(...)', 'doublePassthru' );

		$finalArgs = $args;
		array_unshift( $finalArgs, $doublePassthru );
		$ret = call_user_func_array( array( $interpreter, 'callFunction' ), $finalArgs );
		$ret = $this->normalizeOrder( $ret );
		$this->assertSame( $args, $ret );
	}

	function normalizeOrder( $a ) {
		ksort( $a );
		foreach ( $a as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->normalizeOrder( $value );
			}
		}
		return $a;
	}

	function passthru( /* ... */ ) {
		$args = func_get_args();
		return $args;
	}

	function provideRoundtrip() {
		return array(
			array( 1 ),
			array( true ),
			array( false ),
			array( 'hello' ),
			array( implode( '', array_map( 'chr', range( 0, 255 ) ) ) ),
			array( 1, 2, 3 ),
			array( array() ),
			array( array( 0 => 'foo', 1 => 'bar' ) ),
			array( array( 1 => 'foo', 2 => 'bar' ) ),
			array( array( 'x' => 'foo', 'y' => 'bar', 'z' => array() ) )
		);
	}

	function testGetStatus() {
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

	/** 
	 * @expectedException ScribuntoException
	 * @expectedExceptionMessage The time allocated for running scripts has expired.
	 */
	function testTimeLimit() {
		$interpreter = $this->newInterpreter( array( 'cpuLimit' => 1 ) );
		$chunk = $this->getBusyLoop( $interpreter );
		$interpreter->callFunction( $chunk, 1e9 );
	}

	/**
	 * @expectedException ScribuntoException
	 * @expectedExceptionMessage Lua error: not enough memory
	 */
	function testTestMemoryLimit() {
		$interpreter = $this->newInterpreter( array( 'memoryLimit' => 20 * 1e6 ) );
		$chunk = $interpreter->loadString( '
			t = {}
			for i = 1, 10 do
				t[#t + 1] = string.rep("x" .. i, 1000000)
			end
			',
			'memoryLimit' );
		$interpreter->callFunction( $chunk );
	}
}
