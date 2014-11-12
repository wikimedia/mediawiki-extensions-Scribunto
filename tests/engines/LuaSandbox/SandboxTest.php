<?php

class Scribunto_LuaSandboxTests extends Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'SandboxTests';

	public static function suite( $className ) {
		return self::makeSuite( $className, 'LuaSandbox' );
	}

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'SandboxTests' => __DIR__ . '/SandboxTests.lua',
		);
	}

	public function testArgumentParsingTime() {
		$engine = $this->getEngine();
		if ( !is_callable( array( $engine->getInterpreter()->sandbox, 'pauseUsageTimer' ) ) ) {
			$this->markTestSkipped( "LuaSandbox::pauseUsageTimer is not available" );
		}

		$parser = $engine->getParser();
		$pp = $parser->getPreprocessor();
		$frame = $pp->newFrame();

		$parser->setHook( 'scribuntodelay', function () {
			$t = microtime( 1 ) + 0.5;
			while ( microtime( 1 ) < $t ) {
				# Waste CPU cycles
			}
			return "ok";
		} );
		$this->extraModules['Module:TestArgumentParsingTime'] = '
			return {
				f = function ( frame )
					return frame.args[1]
				end,
				f2 = function ( frame )
					return frame:preprocess( "{{#invoke:TestArgumentParsingTime|f|}}" )
				end,
				f3 = function ( frame )
					return frame:preprocess( "{{#invoke:TestArgumentParsingTime|f|<scribuntodelay/>}}" )
				end,
			}
		';

		$u0 = $engine->getInterpreter()->getCPUUsage();
		$frame->expand(
			$pp->preprocessToObj(
				'{{#invoke:TestArgumentParsingTime|f|<scribuntodelay/>}}'
			)
		);
		$this->assertLessThan( 0.25, $engine->getInterpreter()->getCPUUsage() - $u0,
			'Argument access time was not counted'
		);

		$u0 = $engine->getInterpreter()->getCPUUsage();
		$frame->expand(
			$pp->preprocessToObj(
				'{{#invoke:TestArgumentParsingTime|f2|<scribuntodelay/>}}'
			)
		);
		$this->assertLessThan( 0.25, $engine->getInterpreter()->getCPUUsage() - $u0,
			'Unused arguments not counted in preprocess'
		);

		$u0 = $engine->getInterpreter()->getCPUUsage();
		$frame->expand(
			$pp->preprocessToObj(
				'{{#invoke:TestArgumentParsingTime|f3}}'
			)
		);
		$this->assertGreaterThan( 0.25, $engine->getInterpreter()->getCPUUsage() - $u0,
			'Recursive argument access time was counted'
		);
	}
}
