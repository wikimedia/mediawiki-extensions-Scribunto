<?php

abstract class Scribunto_LuaEngineTest extends MediaWikiTestCase {

	abstract function newEngine( $opts = array() );

	function setUp() {
		parent::setUp();
		try {
			$this->getEngine()->getInterpreter();
		} catch ( Scribunto_LuaInterpreterNotFoundError $e ) {
			$this->markTestSkipped( "interpreter not available" );
		}
	}

	function getEngine() {
		$parser = new Parser;
		$options = new ParserOptions;
		$options->setTemplateCallback( array( $this, 'templateCallback' ) );
		$parser->startExternalParse( Title::newMainPage(), $options, Parser::OT_HTML, true );
		return $this->newEngine( array( 'parser' => $parser ) );
	}

	function getFrame( $engine ) {
		return $engine->getParser()->getPreprocessor()->newFrame();
	}

	function getTestModules() {
		return array(
			'CommonTests' => dirname( __FILE__ ) . '/CommonTests.lua'
		);
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

	function getTestModuleName() {
		return 'CommonTests';
	}

	function getTestModule( $engine, $moduleName ) {
		return $engine->fetchModuleFromParser( 
			Title::makeTitle( NS_MODULE, $moduleName ) );
	}

	function testProvider() {
		$tests = $this->provideLua();
		$this->assertGreaterThan( 2, count( $tests ) );
	}

	function provideLua() {
		$engine = $this->getEngine();
		$allTests = array();
		foreach ( $this->getTestModules() as $moduleName => $fileName ) {
			$module = $this->getTestModule( $engine, $moduleName );
			$exports = $module->execute();
			$result = $engine->getInterpreter()->callFunction( $exports['getTests'] );
			$moduleTests = $result[0];
			foreach ( $moduleTests as $test ) {
				array_unshift( $test, $moduleName );
				$allTests[] = $test;
			}
		}
		return $allTests;
	}

	/** @dataProvider provideLua */
	function testLua( $moduleName, $testName, $expected ) {
		$engine = $this->getEngine();
		$module = $this->getTestModule( $engine, $moduleName );
		if ( is_array( $expected ) && isset( $expected['error'] ) ) {
			$caught = false;
			try {
				$ret = $module->invoke( $testName, $this->getFrame( $engine ) );
			} catch ( Scribunto_LuaError $e ) {
				$caught = true;
				$this->assertStringMatchesFormat( $expected['error'], $e->getLuaMessage() );
			}
			$this->assertTrue( $caught, 'expected an exception' );
		} else {
			$ret = $module->invoke( $testName, $this->getFrame( $engine ) );
			$this->assertSame( $expected, $ret );
		}
	}
}
