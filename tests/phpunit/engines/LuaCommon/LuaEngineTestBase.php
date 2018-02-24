<?php

/**
 * This is the subclass for Lua library tests. It will automatically run all
 * tests against LuaSandbox and LuaStandalone.
 *
 * Most of the time, you'll only need to override the following:
 * - $moduleName: Name of the module being tested
 * - getTestModules(): Add a mapping from $moduleName to the file containing
 *   the code.
 */
// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Scribunto_LuaEngineTestBase extends MediaWikiLangTestCase {
	private static $engineConfigurations = [
		'LuaSandbox' => [
			'memoryLimit' => 50000000,
			'cpuLimit' => 30,
			'allowEnvFuncs' => true,
			'maxLangCacheSize' => 30,
		],
		'LuaStandalone' => [
			'errorFile' => null,
			'luaPath' => null,
			'memoryLimit' => 50000000,
			'cpuLimit' => 30,
			'allowEnvFuncs' => true,
			'maxLangCacheSize' => 30,
		],
	];

	private static $staticEngineName = null;
	private $engineName = null;
	private $engine = null;
	private $luaDataProvider = null;

	/**
	 * Name to display instead of the default
	 * @var string
	 */
	protected $luaTestName = null;

	/**
	 * Name of the module being tested
	 * @var string
	 */
	protected static $moduleName = null;

	/**
	 * Class to use for the data provider
	 * @var string
	 */
	protected static $dataProviderClass = 'Scribunto_LuaDataProvider';

	/**
	 * Tests to skip. Associative array mapping test name to skip reason.
	 * @var array
	 */
	protected $skipTests = [];

	public function __construct(
		$name = null, array $data = [], $dataName = '', $engineName = null
	) {
		if ( $engineName === null ) {
			$engineName = self::$staticEngineName;
		}
		$this->engineName = $engineName;
		parent::__construct( $name, $data, $dataName );
	}

	public static function suite( $className ) {
		return self::makeSuite( $className );
	}

	protected static function makeSuite( $className, $group = null ) {
		$suite = new PHPUnit_Framework_TestSuite;
		$suite->setName( $className );

		$class = new ReflectionClass( $className );

		foreach ( self::$engineConfigurations as $engineName => $opts ) {
			if ( $group !== null && $group !== $engineName ) {
				continue;
			}

			try {
				$parser = new Parser;
				$parser->startExternalParse( Title::newMainPage(), new ParserOptions, Parser::OT_HTML, true );
				$engineClass = "Scribunto_{$engineName}Engine";
				$engine = new $engineClass(
					self::$engineConfigurations[$engineName] + [ 'parser' => $parser ]
				);
				$parser->scribunto_engine = $engine;
				$engine->setTitle( $parser->getTitle() );
				$engine->getInterpreter();
			} catch ( Scribunto_LuaInterpreterNotFoundError $e ) {
				$suite->addTest(
					new Scribunto_LuaEngineTestSkip(
						$className, "interpreter for $engineName is not available"
					), [ 'Lua', $engineName ]
				);
				continue;
			}

			// Work around PHPUnit breakage: the only straightforward way to
			// get the data provider is to call
			// PHPUnit_Util_Test::getProvidedData, but that instantiates the
			// class without passing any parameters to the constructor. But we
			// *need* that engine name.
			self::$staticEngineName = $engineName;

			$engineSuite = new PHPUnit_Framework_TestSuite;
			$engineSuite->setName( "$engineName: $className" );

			foreach ( $class->getMethods() as $method ) {
				if ( PHPUnit_Framework_TestSuite::isTestMethod( $method ) && $method->isPublic() ) {
					$name = $method->getName();
					$groups = PHPUnit_Util_Test::getGroups( $className, $name );
					$groups[] = 'Lua';
					$groups[] = $engineName;
					$groups = array_unique( $groups );

					$data = PHPUnit_Util_Test::getProvidedData( $className, $name );
					if ( is_array( $data ) || $data instanceof Iterator ) {
						// with @dataProvider
						$dataSuite = new PHPUnit_Framework_TestSuite_DataProvider(
							$className . '::' . $name
						);
						foreach ( $data as $k => $v ) {
							$dataSuite->addTest(
								new $className( $name, $v, $k, $engineName ),
								$groups
							);
						}
						$engineSuite->addTest( $dataSuite );
					} elseif ( $data === false ) {
						// invalid @dataProvider
						$engineSuite->addTest( new PHPUnit_Framework_Warning(
							"The data provider specified for {$className}::$name is invalid."
						) );
					} else {
						// no @dataProvider
						$engineSuite->addTest(
							new $className( $name, [], '', $engineName ),
							$groups
						);
					}
				}
			}

			$suite->addTest( $engineSuite );
		}

		return $suite;
	}

	protected function tearDown() {
		if ( $this->luaDataProvider ) {
			$this->luaDataProvider->destroy();
			$this->luaDataProvider = null;
		}
		if ( $this->engine ) {
			$this->engine->destroy();
			$this->engine = null;
		}
		parent::tearDown();
	}

	/**
	 * Get the title used for unit tests
	 *
	 * @return Title
	 */
	protected function getTestTitle() {
		return Title::newMainPage();
	}

	/**
	 * @return ScribuntoEngineBase
	 */
	protected function getEngine() {
		if ( !$this->engine ) {
			$parser = new Parser;
			$options = new ParserOptions;
			$options->setTemplateCallback( [ $this, 'templateCallback' ] );
			$parser->startExternalParse( $this->getTestTitle(), $options, Parser::OT_HTML, true );
			$class = "Scribunto_{$this->engineName}Engine";
			$this->engine = new $class(
				self::$engineConfigurations[$this->engineName] + [ 'parser' => $parser ]
			);
			$parser->scribunto_engine = $this->engine;
			$this->engine->setTitle( $parser->getTitle() );
		}
		return $this->engine;
	}

	public function templateCallback( $title, $parser ) {
		if ( isset( $this->extraModules[$title->getFullText()] ) ) {
			return [
				'text' => $this->extraModules[$title->getFullText()],
				'finalTitle' => $title,
				'deps' => []
			];
		}

		$modules = $this->getTestModules();
		foreach ( $modules as $name => $fileName ) {
			$modTitle = Title::makeTitle( NS_MODULE, $name );
			if ( $modTitle->equals( $title ) ) {
				return [
					'text' => file_get_contents( $fileName ),
					'finalTitle' => $title,
					'deps' => []
				];
			}
		}
		return Parser::statelessFetchTemplate( $title, $parser );
	}

	public function toString() {
		// When running tests written in Lua, return a nicer representation in
		// the failure message.
		if ( $this->luaTestName ) {
			return $this->engineName . ': ' . $this->luaTestName;
		}
		return $this->engineName . ': ' . parent::toString();
	}

	protected function getTestModules() {
		return [
			'TestFramework' => __DIR__ . '/TestFramework.lua',
		];
	}

	public function provideLuaData() {
		if ( !$this->luaDataProvider ) {
			$class = static::$dataProviderClass;
			$this->luaDataProvider = new $class ( $this->getEngine(), static::$moduleName );
		}
		return $this->luaDataProvider;
	}

	/**
	 * @dataProvider provideLuaData
	 * @param string $key
	 * @param string $testName
	 * @param mixed $expected
	 */
	public function testLua( $key, $testName, $expected ) {
		$this->luaTestName = static::$moduleName."[$key]: $testName";
		if ( isset( $this->skipTests[$testName] ) ) {
			$this->markTestSkipped( $this->skipTests[$testName] );
		} else {
			try {
				$actual = $this->provideLuaData()->run( $key );
			} catch ( Scribunto_LuaError $ex ) {
				if ( substr( $ex->getLuaMessage(), 0, 6 ) === 'SKIP: ' ) {
					$this->markTestSkipped( substr( $ex->getLuaMessage(), 6 ) );
				} else {
					throw $ex;
				}
			}
			$this->assertSame( $expected, $actual );
		}
		$this->luaTestName = null;
	}
}

// @codingStandardsIgnoreLine Squiz.Classes.ValidClassName.NotCamelCaps
class Scribunto_LuaEngineTestSkip extends PHPUnit\Framework\TestCase {
	private $className = '';
	private $message = '';

	public function __construct( $className = '', $message = '' ) {
		$this->className = $className;
		$this->message = $message;
		parent::__construct( 'testDummy' );
	}

	public function testDummy() {
		if ( $this->className ) {
			$this->markTestSkipped( $this->message );
		} else {
			// Dummy
			$this->assertTrue( true );
		}
	}

	public function toString() {
		return $this->className;
	}
}
