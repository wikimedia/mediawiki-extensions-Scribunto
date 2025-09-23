<?php

namespace MediaWiki\Extension\Scribunto\Tests;

use MediaWiki\Config\ConfigException;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Scribunto\EngineFactory;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Scribunto\EngineFactory
 */
class EngineFactoryTest extends MediaWikiIntegrationTestCase {

	private function newFactory( array $options ) {
		return new EngineFactory(
			new ServiceOptions(
				EngineFactory::CONSTRUCTOR_OPTIONS,
				new HashConfig( $options )
			),
		);
	}

	public function testNewDefaultEngine() {
		$factory = $this->getServiceContainer()->getService( 'Scribunto.EngineFactory' );
		$this->assertNotNull( $factory->newDefaultEngine() );
	}

	/** @dataProvider provideNewDefaultEngineException */
	public function testNewDefaultEngineException( array $options ) {
		$factory = $this->newFactory( $options );

		$this->expectException( ConfigException::class );
		$factory->newDefaultEngine();
	}

	public static function provideNewDefaultEngineException(): iterable {
		return [
			[
				[
					'ScribuntoDefaultEngine' => null,
					'ScribuntoEngineConf' => [],
				],
			],
			[
				[
					'ScribuntoDefaultEngine' => 'not-defined',
					'ScribuntoEngineConf' => [],
				],
			],
		];
	}
}
