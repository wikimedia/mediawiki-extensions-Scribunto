<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaError;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\UstringLibrary;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\UstringLibrary
 */
class UstringLibraryPhpTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @dataProvider providePatternToRegexErrors
	 */
	public function testPatternToRegexErrors( $pattern, $error ) {
		/** @var UstringLibrary $library */
		$library = TestingAccessWrapper::newFromObject(
			new UstringLibrary(
				$this->createNoOpMock( LuaEngine::class )
			)
		);
		$this->expectException( LuaError::class );
		$this->expectExceptionMessageMatches(
			'/' . preg_quote( $error, '/' ) . '/' );
		$library->patternToRegex( $pattern, 'false', 'patternToRegex' );
	}

	public static function providePatternToRegexErrors() {
		return [
			[
				'(',
				'Unmatched open-paren'
			],
			[
				str_repeat( '(', 101 ) . 'a' . str_repeat( ')', 101 ),
				'Parentheses are too deeply nested'
			],
			[
				')',
				'Unmatched close-paren'
			],
			[
				'foo%',
				'malformed pattern (ends with',
			],
			[
				'foo%b',
				'malformed pattern (missing arguments',
			],
			[
				'%f',
				"missing '['"
			],
			[
				'%1',
				'invalid capture index %1'
			],
			[
				']',
				'Unmatched close-bracket'
			],
			[
				'(foo',
				'Unclosed capture',
			],
		];
	}
}
