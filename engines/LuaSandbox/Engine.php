<?php

class Scribunto_LuaSandboxEngine extends Scribunto_LuaEngine {
	public $options, $loaded = false;
	protected $lineCache = array();

	public function getPerformanceCharacteristics() {
		return array(
			'phpCallsRequireSerialization' => false,
		);
	}

	public function getSoftwareInfo( array &$software ) {
		if ( is_callable( 'LuaSandbox::getVersionInfo' ) ) {
			$versions = LuaSandbox::getVersionInfo();
		} else {
			$sandbox = new LuaSandbox;
			list( $luaver ) = $sandbox->loadString( 'return _VERSION' )->call();
			$versions = array(
				'LuaSandbox' => phpversion( "LuaSandbox" ),
				'Lua' => $luaver,
			);
		}
		$software['[https://www.mediawiki.org/wiki/Extension:Scribunto#LuaSandbox LuaSandbox]'] = $versions['LuaSandbox'];
		$software['[http://www.lua.org/ Lua]'] = str_replace( 'Lua ', '', $versions['Lua'] );
		if ( isset( $versions['LuaJIT'] ) ) {
			$software['[http://luajit.org/ LuaJIT]'] = str_replace( 'LuaJIT ', '', $versions['LuaJIT'] );
		}
	}

	private function getLimitReportData() {
		$ret = array();
		$this->load();

		$t = $this->interpreter->getCPUUsage();
		$ret['scribunto-limitreport-timeusage'] = array(
			sprintf( "%.3f", $t ),
			sprintf( "%.3f", $this->options['cpuLimit'] )
		);
		$ret['scribunto-limitreport-memusage'] = array(
			$this->interpreter->getPeakMemoryUsage(),
			$this->options['memoryLimit'],
		);

		$logs = $this->getLogBuffer();
		if ( $logs !== '' ) {
			$ret['scribunto-limitreport-logs'] = $logs;
		}

		if ( $t < 1.0 ) {
			return $ret;
		}

		$percentProfile = $this->interpreter->getProfilerFunctionReport(
			Scribunto_LuaSandboxInterpreter::PERCENT
		);
		if ( !count( $percentProfile ) ) {
			return $ret;
		}
		$timeProfile = $this->interpreter->getProfilerFunctionReport(
			Scribunto_LuaSandboxInterpreter::SECONDS
		);

		$lines = array();
		$cumulativePercent = 0;
		$num = $otherTime = $otherPercent = 0;
		foreach ( $percentProfile as $name => $percent ) {
			$time = $timeProfile[$name] * 1000;
			$num++;
			if ( $cumulativePercent <= 99 && $num <= 10 ) {
				// Map some regularly appearing internal names
				if ( preg_match( '/^<mw.lua:(\d+)>$/', $name, $m ) ) {
					$line = $this->getMwLuaLine( $m[1] );
					if ( preg_match( '/^\s*(local\s+)?function ([a-zA-Z0-9_.]*)/', $line, $m ) ) {
						$name = $m[2] . ' ' . $name;
					}
				}
				$lines[] = array( $name, sprintf( '%.0f', $time ), sprintf( '%.1f', $percent ) );
			} else {
				$otherTime += $time;
				$otherPercent += $percent;
			}
			$cumulativePercent += $percent;
		}
		if ( $otherTime ) {
			$lines[] = array( '[others]', sprintf( '%.0f', $otherTime ), sprintf( '%.1f', $otherPercent ) );
		}
		$ret['scribunto-limitreport-profile'] = $lines;
		return $ret;
	}

	public function getLimitReport() {
		$data = $this->getLimitReportData();
		$lang = Language::factory( 'en' );

		$t = $this->interpreter->getCPUUsage();
		$s = 'Lua time usage: ' . sprintf( "%.3f", $data['scribunto-limitreport-timeusage'] ) . "s\n" .
			'Lua memory usage: ' . $lang->formatSize( $data['scribunto-limitreport-memusage'] ) . "\n";
		if ( isset( $data['scribunto-limitreport-profile'] ) ) {
			$s .= "Lua Profile:\n";
			$format = "    %-59s %8.0f ms %8.1f%%\n";
			foreach ( $data['scribunto-limitreport-profile'] as $line ) {
				$s .= sprintf( $format, $line[0], $line[1], $line[2] );
			}
		}
		return $s;
	}

	public function reportLimitData( ParserOutput $output ) {
		$data = $this->getLimitReportData();
		foreach ( $data as $k => $v ) {
			$output->setLimitReportData( $k, $v );
		}
		if ( isset( $data['scribunto-limitreport-logs'] ) ) {
			$output->addModules( 'ext.scribunto.logs' );
		}
	}

	public function formatLimitData( $key, &$value, &$report, $isHTML, $localize ) {
		global $wgLang;
		$lang = $localize ? $wgLang : Language::factory( 'en' );
		switch ( $key ) {
			case 'scribunto-limitreport-logs':
				if ( $isHTML ) {
					$report .= $this->formatHtmlLogs( $value, $localize );
				}
				return false;

			case 'scribunto-limitreport-memusage':
				$value = array_map( array( $lang, 'formatSize' ), $value );
				break;
		}

		if ( $key !== 'scribunto-limitreport-profile' ) {
			return true;
		}

		$keyMsg = wfMessage( 'scribunto-limitreport-profile' );
		$msMsg = wfMessage( 'scribunto-limitreport-profile-ms' );
		$percentMsg = wfMessage( 'scribunto-limitreport-profile-percent' );
		if ( !$localize ) {
			$keyMsg->inLanguage( 'en' )->useDatabase( false );
			$msMsg->inLanguage( 'en' )->useDatabase( false );
			$percentMsg->inLanguage( 'en' )->useDatabase( false );
		}

		// To avoid having to do actual work in Message::fetchMessage for each
		// line in the loops below, call ->exists() here to populate ->message.
		$msMsg->exists();
		$percentMsg->exists();

		if ( $isHTML ) {
			$report .= Html::openElement( 'tr' ) .
				Html::rawElement( 'th', array( 'colspan' => 2 ), $keyMsg->parse() ) .
				Html::closeElement( 'tr' ) .
				Html::openElement( 'tr' ) .
				Html::openElement( 'td', array( 'colspan' => 2 ) ) .
				Html::openElement( 'table' );
			foreach ( $value as $line ) {
				$name = $line[0];
				$location = '';
				if ( preg_match( '/^(.*?) *<([^<>]+):(\d+)>$/', $name, $m ) ) {
					$name = $m[1];
					$title = Title::newFromText( $m[2] );
					if ( $title && $title->getNamespace() === NS_MODULE ) {
						$location = '&lt;' . Linker::link( $title ) . ":{$m[3]}&gt;";
					} else {
						$location = htmlspecialchars( "<{$m[2]}:{$m[3]}>" );
					}
				}
				$ms = clone $msMsg;
				$ms->params( $line[1] );
				$pct = clone $percentMsg;
				$pct->params( $line[2] );
				$report .= Html::openElement( 'tr' ) .
					Html::element( 'td', null, $name ) .
					Html::rawElement( 'td', null, $location ) .
					Html::rawElement( 'td', array( 'align' => 'right' ), $ms->parse() ) .
					Html::rawElement( 'td', array( 'align' => 'right' ), $pct->parse() ) .
					Html::closeElement( 'tr' );
			}
			$report .= Html::closeElement( 'table' ) .
				Html::closeElement( 'td' ) .
				Html::closeElement( 'tr' );
		} else {
			$report .= $keyMsg->text() . ":\n";
			foreach ( $value as $line ) {
				$ms = clone $msMsg;
				$ms->params( $line[1] );
				$pct = clone $percentMsg;
				$pct->params( $line[2] );
				$report .= sprintf( "    %-59s %11s %11s\n", $line[0], $ms->text(), $pct->text() );
			}
		}

		return false;
	}

	protected function getMwLuaLine( $lineNum ) {
		if ( !isset( $this->lineCache['mw.lua'] ) ) {
			$this->lineCache['mw.lua'] = file( $this->getLuaLibDir() . '/mw.lua' );
		}
		return $this->lineCache['mw.lua'][$lineNum - 1];
	}

	function newInterpreter() {
		return new Scribunto_LuaSandboxInterpreter( $this, $this->options );
	}
}

class Scribunto_LuaSandboxInterpreter extends Scribunto_LuaInterpreter {
	/**
	 * @var Scribunto_LuaEngine
	 */
	public $engine;

	/**
	 * @var LuaSandbox
	 */
	public $sandbox;

	/**
	 * @var bool
	 */
	public $profilerEnabled;

	const SAMPLES = 0;
	const SECONDS = 1;
	const PERCENT = 2;

	function __construct( $engine, array $options ) {
		if ( !extension_loaded( 'luasandbox' ) ) {
			throw new Scribunto_LuaInterpreterNotFoundError(
				'The luasandbox extension is not present, this engine cannot be used.' );
		}
		$this->engine = $engine;
		$this->sandbox = new LuaSandbox;
		$this->sandbox->setMemoryLimit( $options['memoryLimit'] );
		$this->sandbox->setCPULimit( $options['cpuLimit'] );
		if ( is_callable( array( $this->sandbox, 'enableProfiler' ) ) )
		{
			if ( !isset( $options['profilerPeriod'] ) ) {
				$options['profilerPeriod'] = 0.02;
			}
			if ( $options['profilerPeriod'] ) {
				$this->profilerEnabled = true;
				$this->sandbox->enableProfiler( $options['profilerPeriod'] );
			}
		}
	}

	protected function convertSandboxError( LuaSandboxError $e ) {
		$opts = array();
		if ( isset( $e->luaTrace ) ) {
			$opts['trace'] = $e->luaTrace;
		}
		$message = $e->getMessage();
		if ( preg_match( '/^(.*?):(\d+): (.*)$/', $message, $m ) ) {
			$opts['module'] = $m[1];
			$opts['line'] = $m[2];
			$message = $m[3];
		}
		return $this->engine->newLuaError( $message, $opts );
	}

	/**
	 * @param string $text
	 * @param string $chunkName
	 * @throws Scribunto_LuaError
	 */
	public function loadString( $text, $chunkName ) {
		try {
			return $this->sandbox->loadString( $text, $chunkName );
		} catch ( LuaSandboxError $e ) {
			throw $this->convertSandboxError( $e );
		}
	}

	public function registerLibrary( $name, array $functions ) {
		$realLibrary = array();
		foreach ( $functions as $funcName => $callback ) {
			$realLibrary[$funcName] = array(
				new Scribunto_LuaSandboxCallback( $callback ),
				$funcName );
		}
		$this->sandbox->registerLibrary( $name, $realLibrary );

		# TODO: replace this with
		#$this->sandbox->registerVirtualLibrary(
		#	$name, array( $this, 'callback' ), $functions );
	}

	public function callFunction( $func /*, ... */ ) {
		$args = func_get_args();
		$func = array_shift( $args );
		try {
			$ret = call_user_func_array( array( $func, 'call' ), $args );
			if ( $ret === false ) {
				// Per the documentation on LuaSandboxFunction::call, a return value
				// of false means that something went wrong and it's PHP's fault,
				// so throw a "real" exception.
				throw new MWException(
					__METHOD__ . ': LuaSandboxFunction::call returned false' );
			}
			return $ret;
		} catch ( LuaSandboxTimeoutError $e ) {
			throw $this->engine->newException( 'scribunto-common-timeout' );
		} catch ( LuaSandboxError $e ) {
			throw $this->convertSandboxError( $e );
		}
	}

	public function wrapPhpFunction( $callable ) {
		if ( is_callable( array( $this->sandbox, 'wrapPhpFunction' ) ) ) {
			return $this->sandbox->wrapPhpFunction( $callable );
		}

		// We have to hack around the lack of the wrapper function by loading a
		// dummy library with $callable, then extracting the function, and then
		// for good measure nilling out the library table.
		list( $name ) = $this->sandbox->loadString( '
			for i = 0, math.huge do
				if not _G["*LuaSandbox* temp" .. i] then return "*LuaSandbox* temp" .. i end
			end
			' )->call();
		$this->sandbox->registerLibrary( $name, array( 'func' => $callable ) );
		list( $func ) = $this->sandbox->loadString(
			"local ret = _G['$name'].func _G['$name'] = nil return ret"
		)->call();
		return $func;
	}

	public function isLuaFunction( $object ) {
		return $object instanceof LuaSandboxFunction;
	}

	public function getPeakMemoryUsage() {
		return $this->sandbox->getPeakMemoryUsage();
	}

	public function getCPUUsage() {
		return $this->sandbox->getCPUUsage();
	}

	public function getProfilerFunctionReport( $units ) {
		if ( $this->profilerEnabled ) {
			static $unitsMap;
			if ( !$unitsMap ) {
				$unitsMap = array(
					self::SAMPLES => LuaSandbox::SAMPLES,
					self::SECONDS => LuaSandbox::SECONDS,
					self::PERCENT => LuaSandbox::PERCENT );
			}
			return $this->sandbox->getProfilerFunctionReport( $unitsMap[$units] );
		} else {
			return array();
		}
	}

	public function pauseUsageTimer() {
		if ( is_callable( array( $this->sandbox, 'pauseUsageTimer' ) ) ) {
			$this->sandbox->pauseUsageTimer();
		}
	}

	public function unpauseUsageTimer() {
		if ( is_callable( array( $this->sandbox, 'unpauseUsageTimer' ) ) ) {
			$this->sandbox->unpauseUsageTimer();
		}
	}
}

class Scribunto_LuaSandboxCallback {
	function __construct( $callback ) {
		$this->callback = $callback;
	}

	/**
	 * We use __call with a variable function name so that LuaSandbox will be
	 * able to return a meaningful function name in profiling data.
	 */
	function __call( $funcName, $args ) {
		try {
			return call_user_func_array( $this->callback, $args );
		} catch ( Scribunto_LuaError $e ) {
			throw new LuaSandboxRuntimeError( $e->getLuaMessage() );
		}
	}
}
