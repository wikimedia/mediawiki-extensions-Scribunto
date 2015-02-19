<?php

abstract class Scribunto_LuaInterpreterTest extends MediaWikiTestCase {
	protected abstract function newInterpreter( $opts = array() );

	protected function setUp() {
		parent::setUp();
		try {
			$this->newInterpreter();
		} catch ( Scribunto_LuaInterpreterNotFoundError $e ) {
			$this->markTestSkipped( "interpreter not available" );
		}
	}

	protected function getBusyLoop( $interpreter ) {
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

	/** @dataProvider provideRoundtrip */
	public function testRoundtrip( /*...*/ ) {
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
	public function testDoubleRoundtrip( /* ... */ ) {
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

	/**
	 * This cannot be done in testRoundtrip and testDoubleRoundtrip, because
	 * assertSame( NAN, NAN ) returns false.
	 */
	public function testRoundtripNAN() {
		$interpreter = $this->newInterpreter();

		$passthru = $interpreter->loadString( 'return ...', 'passthru' );
		$ret = $interpreter->callFunction( $passthru, NAN );
		$this->assertTrue( is_nan( $ret[0] ), 'NaN was not passed through' );

		$interpreter->registerLibrary( 'test',
			array( 'passthru' => array( $this, 'passthru' ) ) );
		$doublePassthru = $interpreter->loadString(
			'return test.passthru(...)', 'doublePassthru' );
		$ret = $interpreter->callFunction( $doublePassthru, NAN );
		$this->assertTrue( is_nan( $ret[0] ), 'NaN was not double passed through' );
	}

	private function normalizeOrder( $a ) {
		ksort( $a );
		foreach ( $a as &$value ) {
			if ( is_array( $value ) ) {
				$value = $this->normalizeOrder( $value );
			}
		}
		return $a;
	}

	public function passthru( /* ... */ ) {
		$args = func_get_args();
		return $args;
	}

	public function provideRoundtrip() {
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
			array( array( 'x' => 'foo', 'y' => 'bar', 'z' => array() ) ),
			array( INF ),
			array( -INF ),
			array( 'ok', null, 'ok' ),
			array( null, 'ok' ),
			array( 'ok', null ),
			array( null ),
		);
	}

	public function testTimeLimit() {
		if( php_uname( 's' ) === 'Darwin' ) {
			$this->markTestSkipped( "Darwin is lacking POSIX timer, skipping CPU time limiting test." );
		}

		$interpreter = $this->newInterpreter( array( 'cpuLimit' => 1 ) );
		$chunk = $this->getBusyLoop( $interpreter );
		try {
			$interpreter->callFunction( $chunk, 1e9 );
			$this->fail( "Expected ScribuntoException was not thrown" );
		} catch ( ScribuntoException $ex ) {
			$this->assertSame( 'scribunto-common-timeout', $ex->messageName );
		}
	}

	public function testTestMemoryLimit() {
		$interpreter = $this->newInterpreter( array( 'memoryLimit' => 20 * 1e6 ) );
		$chunk = $interpreter->loadString( '
			t = {}
			for i = 1, 10 do
				t[#t + 1] = string.rep("x" .. i, 1000000)
			end
			',
			'memoryLimit' );
		try {
			$interpreter->callFunction( $chunk );
			$this->fail( "Expected ScribuntoException was not thrown" );
		} catch ( ScribuntoException $ex ) {
			$this->assertSame( 'scribunto-lua-error', $ex->messageName );
			$this->assertSame( 'not enough memory', $ex->messageArgs[1] );
		}
	}

	public function testWrapPHPFunction() {
		$interpreter = $this->newInterpreter();
		$func = $interpreter->wrapPhpFunction( function ( $n ) {
			return array( 42, $n );
		} );
		$res = $interpreter->callFunction( $func, 'From PHP' );
		$this->assertEquals( array( 42, 'From PHP' ), $res );

		$chunk = $interpreter->loadString( '
			f = ...
			return f( "From Lua" )
			',
			'wrappedPhpFunction' );
		$res = $interpreter->callFunction( $chunk, $func );
		$this->assertEquals( array( 42, 'From Lua' ), $res );
	}
}
