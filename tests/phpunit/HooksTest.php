<?php

namespace MediaWiki\Extension\Scribunto\Tests;

use MediaWiki\Context\RequestContext;
use MediaWiki\EditPage\EditPage;
use MediaWiki\Extension\Scribunto\Hooks;
use MediaWiki\Output\OutputPage;
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
		$this->newHooks()->onContentHandlerDefaultModelFor( $title, $model );
		$this->assertSame( $expected, $model );
	}

	public function testEditPageConsoleIntroIsParsed() {
		$this->setUserLang( 'en' );
		$title = $this->createMock( Title::class );
		$title->method( 'hasContentModel' )
			->with( CONTENT_MODEL_SCRIBUNTO )
			->willReturn( true );
		$editor = $this->createMock( EditPage::class );
		$editor->method( 'getTitle' )->willReturn( $title );
		$output = new OutputPage( RequestContext::getMain() );
		$tab = 1;

		$this->newHooks()->onEditPage__showStandardInputs_options( $editor, $output, $tab );

		$this->assertContains( 'ext.scribunto.edit', $output->getModules() );
		$intro = $output->getJsConfigVars()['ScribuntoConsoleIntro'];
		$this->assertStringStartsWith( '<ul><li>', $intro );
		$this->assertStringNotContainsString( '* ', $intro );
	}

	private function newHooks(): Hooks {
		$services = $this->getServiceContainer();
		return new Hooks(
			$services->getMainConfig(),
			$services->getContentHandlerFactory(),
			$services->getObjectCacheFactory(),
			$services->getStatsFactory(),
			$services->getService( 'Scribunto.EngineFactory' ),
		);
	}
}
