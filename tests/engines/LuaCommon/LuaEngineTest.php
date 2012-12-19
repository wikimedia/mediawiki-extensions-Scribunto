<?php

// To add additional test modules, add the module to getTestModules() and
// implement a data provider method and test method, using provideCommonTests()
// and testCommonTests() as a template.

require_once( __DIR__ . '/LuaDataProvider.php' );

abstract class Scribunto_LuaEngineTest extends MediaWikiTestCase {
	private $engine = null;
	private $dataProviders = array();
	private $luaTestName = null;

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

	function provideCommonTests() {
		return $this->getTestProvider( 'CommonTests' );
	}

	/** @dataProvider provideCommonTests */
	function testCommonTests( $key, $testName, $expected ) {
		$this->runTestProvider( 'CommonTests', $key, $testName, $expected );
	}
}
