<?php
/**
 * Scribunto Content Model
 *
 * @file
 * @ingroup Extensions
 * @ingroup Scribunto
 *
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

/**
 * Represents the content of a Scribunto script page
 */
class ScribuntoContent extends TextContent {

	function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_SCRIBUNTO );
	}

	/**
	 * Checks whether the script is valid
	 *
	 * @param Title $title
	 * @return Status
	 */
	public function validate( Title $title ) {
		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( $title );
		return $engine->validate( $this->getNativeData(), $title->getPrefixedDBkey() );
	}

	public function prepareSave( WikiPage $page, $flags, $parentRevId, User $user ) {
		return $this->validate( $page->getTitle() );
	}

	/**
	 * Parse the Content object and generate a ParserOutput from the result.
	 *
	 * @param $title Title The page title to use as a context for rendering
	 * @param $revId null|int The revision being rendered (optional)
	 * @param $options null|ParserOptions Any parser options
	 * @param $generateHtml boolean Whether to generate HTML (default: true).
	 * @param &$output ParserOutput representing the HTML form of the text.
	 * @return ParserOutput
	 */
	protected function fillParserOutput(
		Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output
	) {
		global $wgParser, $wgScribuntoUseGeSHi, $wgUseSiteCss;

		$text = $this->getNativeData();

		// Get documentation, if any
		$output = new ParserOutput();
		$doc = Scribunto::getDocPage( $title );
		if ( $doc ) {
			$msg = wfMessage(
				$doc->exists() ? 'scribunto-doc-page-show' : 'scribunto-doc-page-does-not-exist',
				$doc->getPrefixedText()
			)->inContentLanguage();

			if ( !$msg->isDisabled() ) {
				// We need the ParserOutput for categories and such, so we
				// can't use $msg->parse().
				$docViewLang = $doc->getPageViewLanguage();
				$dir = $docViewLang->getDir();

				// Code is forced to be ltr, but the documentation can be rtl.
				// Correct direction class is needed for correct formatting.
				// The possible classes are
				// mw-content-ltr or mw-content-rtl
				$dirClass = "mw-content-$dir";

				$docWikitext = Html::rawElement(
					'div',
					[
						'lang' => $docViewLang->getHtmlCode(),
						'dir' => $dir,
						'class' => $dirClass,
					],
					// Line breaks are needed so that wikitext would be
					// appropriately isolated for correct parsing. See Bug 60664.
					"\n" . $msg->plain() . "\n"
				);

				if ( !$options ) {
					// NOTE: use canonical options per default to produce cacheable output
					$options = ContentHandler::getForTitle( $doc )->makeParserOptions( 'canonical' );
				} else {
					if ( $options->getTargetLanguage() === null ) {
						$options->setTargetLanguage( $doc->getPageLanguage() );
					}
				}

				$output = $wgParser->parse( $docWikitext, $title, $options, true, true, $revId );
			}

			// Mark the doc page as a transclusion, so we get purged when it
			// changes.
			$output->addTemplate( $doc, $doc->getArticleID(), $doc->getLatestRevID() );
		}

		// Validate the script, and include an error message and tracking
		// category if it's invalid
		$status = $this->validate( $title );
		if ( !$status->isOK() ) {
			$output->setText( self::getPOText( $output ) .
				Html::rawElement( 'div', [ 'class' => 'errorbox' ],
					$status->getHTML( 'scribunto-error-short', 'scribunto-error-long' )
				)
			);
			$output->addTrackingCategory( 'scribunto-module-with-errors-category', $title );
		}

		if ( !$generateHtml ) {
			// We don't need the actual HTML
			$output->setText( '' );
			return $output;
		}

		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( $title );

		// Add HTML for the actual script
		$language = $engine->getGeSHiLanguage();
		if ( $wgScribuntoUseGeSHi && $language ) {
			$geshi = SyntaxHighlight_GeSHi::prepare( $text, $language );
			$geshi->set_language( $language );
			if ( $geshi instanceof GeSHi && !$geshi->error() ) {
				$code = $geshi->parse_code();
				if ( $code ) {
					// @todo Once we drop support for old versions of
					// Extension:SyntaxHighlight_GeSHi, drop the ugly test and
					// the BC case.
					global $wgAutoloadClasses;
					if ( isset( $wgAutoloadClasses['ResourceLoaderGeSHiModule'] ) ) {
						$output->addModuleStyles( "ext.geshi.language.$language" );
					} else {
						// Backwards compatibility
						$output->addHeadItem( SyntaxHighlight_GeSHi::buildHeadItem( $geshi ), "source-{$language}" );
					}
					if ( $wgUseSiteCss ) {
						$output->addModuleStyles( 'ext.geshi.local' );
					}
					$output->setText( self::getPOText( $output ) . $code );
					return $output;
				}
			}
		}

		// No GeSHi, or GeSHi can't parse it, use plain <pre>
		$output->setText( self::getPOText( $output ) .
			"<pre class='mw-code mw-script' dir='ltr'>\n" .
			htmlspecialchars( $text ) .
			"\n</pre>\n"
		);

		return $output;
	}

	/**
	 * Fetch the text from a ParserOutput
	 * @todo Once support for MW < 1.27 is dropped, inline this.
	 * @param ParserOutput $po
	 * @return string
	 */
	private static function getPOText( ParserOutput $po ) {
		return is_callable( [ $po, 'getRawText' ] )
			? $po->getRawText()
			: $po->getText();
	}
}
