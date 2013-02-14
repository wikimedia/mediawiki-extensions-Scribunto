<?php

class Scribunto_LuaCommonTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'CommonTests';

	private static $allowedGlobals = array(
		// Functions
		'assert',
		'error',
		'getfenv',
		'getmetatable',
		'ipairs',
		'next',
		'pairs',
		'pcall',
		'rawequal',
		'rawget',
		'rawset',
		'require',
		'select',
		'setfenv',
		'setmetatable',
		'tonumber',
		'tostring',
		'type',
		'unpack',
		'xpcall',

		// Packages
		'_G',
		'debug',
		'math',
		'mw',
		'os',
		'package',
		'string',
		'table',

		// Misc
		'_VERSION',
	);

	function setUp() {
		parent::setUp();

		// Note this depends on every iteration of the data provider running with a clean parser
		$this->getEngine()->getParser()->getOptions()->setExpensiveParserFunctionLimit( 10 );

		// Some of the tests need this
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( 'mw.makeProtectedEnvFuncsForTest = mw.makeProtectedEnvFuncs', 'fortest' )
		);
	}

	function getTestModules() {
		return parent::getTestModules() + array(
			'CommonTests' => __DIR__ . '/CommonTests.lua',
		);
	}

	function testNoLeakedGlobals() {
		$interpreter = $this->getEngine()->getInterpreter();

		list( $actualGlobals ) = $interpreter->callFunction(
			$interpreter->loadString(
				'local t = {} for k in pairs( _G ) do t[#t+1] = k end return t',
				'getglobals'
			)
		);

		$leakedGlobals = array_diff( $actualGlobals, self::$allowedGlobals );
		$this->assertEquals( 0, count( $leakedGlobals ),
			'The following globals are leaked: ' . join( ' ', $leakedGlobals )
		);
	}

	function testModuleStringExtend() {
		$engine = $this->getEngine();
		$interpreter = $engine->getInterpreter();

		$interpreter->callFunction(
			$interpreter->loadString( 'string.testModuleStringExtend = "ok"', 'extendstring' )
		);
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return ("").testModuleStringExtend', 'teststring1' )
		);
		$this->assertSame( array( 'ok' ), $ret, 'string can be extended' );

		$this->extraModules['Module:testModuleStringExtend'] = '
			return {
				test = function() return ("").testModuleStringExtend end
			}
			';
		$module = $engine->fetchModuleFromParser(
			Title::makeTitle( NS_MODULE, 'testModuleStringExtend' )
		);
		$ext = $module->execute();
		$ret = $interpreter->callFunction( $ext['test'] );
		$this->assertSame( array( 'ok' ), $ret, 'string extension can be used from module' );

		$this->extraModules['Module:testModuleStringExtend2'] = '
			return {
				test = function()
					string.testModuleStringExtend = "fail"
					return ("").testModuleStringExtend
				end
			}
			';
		$module = $engine->fetchModuleFromParser(
			Title::makeTitle( NS_MODULE, 'testModuleStringExtend2' )
		);
		$ext = $module->execute();
		$ret = $interpreter->callFunction( $ext['test'] );
		$this->assertSame( array( 'ok' ), $ret, 'string extension cannot be modified from module' );
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return string.testModuleStringExtend', 'teststring2' )
		);
		$this->assertSame( array( 'ok' ), $ret, 'string extension cannot be modified from module' );

		$ret = $engine->runConsole( array(
			'prevQuestions' => array(),
			'question' => '=("").testModuleStringExtend',
			'content' => 'return {}',
			'title' => Title::makeTitle( NS_MODULE, 'dummy' ),
		) );
		$this->assertSame( 'ok', $ret['return'], 'string extension can be used from console' );

		$ret = $engine->runConsole( array(
			'prevQuestions' => array( 'string.fail = "fail"' ),
			'question' => '=("").fail',
			'content' => 'return {}',
			'title' => Title::makeTitle( NS_MODULE, 'dummy' ),
		) );
		$this->assertSame( 'nil', $ret['return'], 'string cannot be extended from console' );

		$ret = $engine->runConsole( array(
			'prevQuestions' => array( 'string.testModuleStringExtend = "fail"' ),
			'question' => '=("").testModuleStringExtend',
			'content' => 'return {}',
			'title' => Title::makeTitle( NS_MODULE, 'dummy' ),
		) );
		$this->assertSame( 'ok', $ret['return'], 'string extension cannot be modified from console' );
		$ret = $interpreter->callFunction(
			$interpreter->loadString( 'return string.testModuleStringExtend', 'teststring3' )
		);
		$this->assertSame( array( 'ok' ), $ret, 'string extension cannot be modified from console' );

		$interpreter->callFunction(
			$interpreter->loadString( 'string.testModuleStringExtend = nil', 'unextendstring' )
		);
	}
}
