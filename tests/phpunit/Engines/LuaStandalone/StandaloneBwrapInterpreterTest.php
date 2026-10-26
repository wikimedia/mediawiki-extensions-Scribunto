<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaStandalone;

if ( !wfIsCLI() ) {
	exit;
}

use MediaWiki\Shell\Shell;
use Wikimedia\TestingAccessWrapper;

/**
 * Runs the standalone interpreter tests with useBubblewrap, and checks
 * that the sandbox actually isolates the interpreter.
 *
 * @group Lua
 * @group LuaStandalone
 * @group Standalone
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaStandalone\LuaStandaloneInterpreter
 */
class StandaloneBwrapInterpreterTest extends StandaloneInterpreterTest {

	private const WRAPPER = __DIR__ . '/../../../../includes/Engines/LuaStandalone/lua_bwrap.sh';
	private const LUA = __DIR__ . '/../../../../includes/Engines/LuaStandalone/binaries/lua5_1_5_linux_64_generic/lua';

	/** @var bool|null */
	private static $bwrapUsable = null;

	protected function setUp(): void {
		if ( php_uname( 's' ) !== 'Linux' || PHP_INT_SIZE !== 8 ) {
			$this->markTestSkipped( 'bubblewrap tests need 64-bit Linux' );
		}
		// bwrap may be missing, or unprivileged user namespaces may be
		// disabled (e.g. in containers or by AppArmor)
		self::$bwrapUsable ??= Shell::isDisabled() ? false : Shell::command(
			'bwrap', '--unshare-all', '--ro-bind', '/', '/', 'true'
		)->execute()->getExitCode() === 0;
		if ( !self::$bwrapUsable ) {
			$this->markTestSkipped( 'bubblewrap is not usable' );
		}
		$this->stdOpts['useBubblewrap'] = true;
		parent::setUp();
	}

	/**
	 * The pid reported from inside the sandbox belongs to its own PID
	 * namespace, so it can't be checked with ps like the parent test does.
	 */
	public function testGetStatus() {
		$interpreter = $this->newInterpreter();
		$engine = TestingAccessWrapper::newFromObject( $interpreter->engine );
		$chunk = $this->getBusyLoop( $interpreter );
		$startTime = microtime( true );
		while ( microtime( true ) - $startTime < 1 ) {
			$interpreter->callFunction( $chunk, 100 );
		}
		$status = $interpreter->getStatus();
		$time = $status['time'] / $engine->getClockTick();
		$this->assertGreaterThan( 0.1, $time, 'getStatus() time usage' );
		$this->assertLessThan( 1.5, $time, 'getStatus() time usage' );
		$this->assertGreaterThan( 0, $status['vsize'], 'getStatus() vsize' );
	}

	/**
	 * Scribunto's own Lua sandbox already hides io and os.getenv from loaded
	 * chunks, so run a probe script directly through the wrapper instead.
	 */
	public function testSandboxIsolation() {
		$outsideFile = tempnam( sys_get_temp_dir(), 'scribunto-bwrap' );
		try {
			file_put_contents( $outsideFile, 'secret' );
			$result = Shell::command(
				'/bin/sh',
				self::WRAPPER,
				self::LUA,
				__DIR__ . '/bwrap_probe.lua',
				__DIR__,
				$outsideFile,
				(string)getmypid()
			)->execute();
		} finally {
			unlink( $outsideFile );
		}
		$this->assertSame( 0, $result->getExitCode(), $result->getStderr() );
		$this->assertSame(
			"readOwnDir=true\n" .
			"readOutside=false\n" .
			"writeOwnDir=false\n" .
			"writeTmp=false\n" .
			"hostProcess=false\n" .
			"envPath=nil\n" .
			"envHome=nil\n",
			$result->getStdout()
		);
		$this->assertFileDoesNotExist( __DIR__ . '/bwrap_probe.out' );
	}
}
