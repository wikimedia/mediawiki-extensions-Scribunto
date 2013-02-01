<?php

// To add additional test modules, add the module to getTestModules() and
// implement a data provider method and test method, using provideCommonTests()
// and testCommonTests() as a template.

require_once( __DIR__ . '/LuaDataProvider.php' );

abstract class Scribunto_LuaEngineTest extends MediaWikiTestCase {
	private $engine = null;
	private $dataProviders = array();
	private $luaTestName = null;
	private $extraModules = array();

	abstract function newEngine( $opts = array() );

	function setUp() {
		parent::setUp();
		try {
			$this->getEngine()->getInterpreter();
		} catch ( Scribunto_LuaInterpreterNotFoundError $e ) {
			$this->markTestSkipped( "interpreter not available" );
		}
	}

	function tearDown() {
		foreach ( $this->dataProviders as $k => $p ) {
			$p->destroy();
		}
		$this->dataProviders = array();
		if ( $this->engine ) {
			$this->engine->destroy();
			$this->engine = null;
		}
		parent::tearDown();
	}

	function getEngine() {
		if ( $this->engine ) {
			return $this->engine;
		}
		$parser = new Parser;
		$options = new ParserOptions;
		$options->setTemplateCallback( array( $this, 'templateCallback' ) );
		$parser->startExternalParse( Title::newMainPage(), $options, Parser::OT_HTML, true );
		$this->engine = $this->newEngine( array( 'parser' => $parser ) );
		return $this->engine;
	}

	function templateCallback( $title, $parser ) {
		if ( isset($this->extraModules[$title->getFullText()]) ) {
			return array(
				'text' => $this->extraModules[$title->getFullText()],
				'finalTitle' => $title,
				'deps' => array()
			);
		}

		$modules = $this->getTestModules();
		foreach ( $modules as $name => $fileName ) {
			$modTitle = Title::makeTitle( NS_MODULE, $name );
			if ( $modTitle->equals( $title ) ) {
				return array(
					'text' => file_get_contents( $fileName ),
					'finalTitle' => $title,
					'deps' => array()
				);
			}
		}
		return Parser::statelessFetchTemplate( $title, $parser );
	}

	function toString() {
		// When running tests written in Lua, return a nicer representation in
		// the failure message.
		if ( $this->luaTestName ) {
			return $this->luaTestName;
		}
		return parent::toString();
	}

	function getTestModules() {
		return array(
			'TestFramework' => __DIR__ . '/TestFramework.lua',
			'CommonTests' => __DIR__ . '/CommonTests.lua',
		);
	}

	function getTestProvider( $moduleName ) {
		if ( !isset( $this->dataProviders[$moduleName] ) ) {
			$this->dataProviders[$moduleName] = new LuaDataProvider( $this->getEngine(), $moduleName );
		}
		return $this->dataProviders[$moduleName];
	}

	function runTestProvider( $moduleName, $key, $testName, $expected ) {
		$this->luaTestName = "{$moduleName}[$key]: $testName";
		$dataProvider = $this->getTestProvider( $moduleName );
		$actual = $dataProvider->run( $key );
		$this->assertSame( $expected, $actual );
		$this->luaTestName = null;
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

	function provideCommonTests() {
		return $this->getTestProvider( 'CommonTests' );
	}

	/** @dataProvider provideCommonTests */
	function testCommonTests( $key, $testName, $expected ) {
		// Note this depends on every iteration of the data provider running with a clean parser
		$this->getEngine()->getParser()->getOptions()->setExpensiveParserFunctionLimit( 10 );
		$this->runTestProvider( 'CommonTests', $key, $testName, $expected );
	}
}
