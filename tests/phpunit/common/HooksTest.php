<?php

class ScribuntoHooksTest extends MediaWikiLangTestCase {

	public function provideContentHandlerDefaultModelFor() {
		return [
			[ 'Module:Foo', CONTENT_MODEL_SCRIBUNTO, false ],
			[ 'Module:Foo/doc', null, true ],
			[ 'Main Page', null, true ],

		];
	}

	/**
	 * @covers ScribuntoHooks::contentHandlerDefaultModelFor
	 * @dataProvider provideContentHandlerDefaultModelFor
	 */
	public function testContentHandlerDefaultModelFor( $name, $expected, $retVal ) {
		$title = Title::newFromText( $name );
		$model = null;
		$ret = ScribuntoHooks::contentHandlerDefaultModelFor( $title, $model );
		$this->assertSame( $retVal, $ret );
		$this->assertSame( $expected, $model );
	}
}
