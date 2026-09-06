<?php

namespace MediaWiki\Extension\Scribunto\Tests;

use MediaWiki\Extension\Scribunto\Hooks;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Scribunto\Hooks
 */
class HooksTest extends MediaWikiIntegrationTestCase {

	public static function provideContentHandlerDefaultModelFor() {
		return [
			[ NS_MODULE, 'Foo', CONTENT_MODEL_SCRIBUNTO ],
			[ NS_MODULE, 'Foo/doc', null ],
			[ NS_MODULE, 'Foo/styles.css', 'sanitized-css', 'sanitized-css' ],
			[ NS_MODULE, 'Foo.json', CONTENT_MODEL_JSON ],
			[ NS_MODULE, 'Foo/subpage.json', CONTENT_MODEL_JSON ],
			[ NS_MAIN, 'Main Page', null ],
		];
	}

	/**
	 * @dataProvider provideContentHandlerDefaultModelFor
	 */
	public function testContentHandlerDefaultModelFor( $ns, $name, $expected,
		$before = null
	) {
		$title = Title::makeTitle( $ns, $name );
		$model = $before;
		$services = $this->getServiceContainer();
		( new Hooks(
			$services->getMainConfig(),
			$services->getContentHandlerFactory(),
			$services->getObjectCacheFactory(),
			$services->getStatsFactory(),
			$services->getService( 'Scribunto.EngineFactory' ),
		) )->onContentHandlerDefaultModelFor( $title, $model );
		$this->assertSame( $expected, $model );
	}
}
