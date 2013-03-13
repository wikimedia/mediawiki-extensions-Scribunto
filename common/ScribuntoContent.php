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
		parent::__construct( $text, 'Scribunto' );
	}

	/**
	 * Parse the Content object and generate a ParserOutput from the result.
	 *
	 * @param $title Title The page title to use as a context for rendering
	 * @param $revId null|int The revision being rendered (optional)
	 * @param $options null|ParserOptions Any parser options
	 * @param $generateHtml boolean Whether to generate HTML (default: true).
	 * @return ParserOutput
	 */
	public function getParserOutput( Title $title, $revId = null, ParserOptions $options = null, $generateHtml = true ) {
		global $wgParser, $wgScribuntoUseGeSHi;

		$text = $this->getNativeData();
		$output = null;

		if ( !$options ) {
			//NOTE: use canonical options per default to produce cacheable output
			$options = $this->getContentHandler()->makeParserOptions( 'canonical' );
		}

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
				$output = $wgParser->parse( $msg->plain(), $title, $options, true, true, $revId );
			}

			// Mark the doc page as a transclusion, so we get purged when it
			// changes.
			$output->addTemplate( $doc, $doc->getArticleID(), $doc->getLatestRevID() );
		}

		if ( !$generateHtml ) {
			// We don't need the actual HTML
			$output->setText( '' );
			return $output;
		}

		// Add HTML for the actual script
		$engine = Scribunto::newDefaultEngine();
		$language = $engine->getGeSHiLanguage();
		if( $wgScribuntoUseGeSHi && $language ) {
			$geshi = SyntaxHighlight_GeSHi::prepare( $text, $language );
			$geshi->set_language( $language );
			if( $geshi instanceof GeSHi && !$geshi->error() ) {
				$code = $geshi->parse_code();
				if( $code ) {
					$output->addHeadItem( SyntaxHighlight_GeSHi::buildHeadItem( $geshi ), "source-{$language}" );
					$output->setText( $output->getText() . "<div dir=\"ltr\">{$code}</div>" );
					return $output;
				}
			}
		}

		// No GeSHi, or GeSHi can't parse it, use plain <pre>
		$output->setText( $output->getText() .
			"<pre class=\"mw-code mw-script\" dir=\"ltr\">\n" .
			htmlspecialchars( $text ) .
			"\n</pre>\n"
		);

		return $output;
	}

	/**
	 * Returns a Content object with pre-save transformations applied (or this
	 * object if no transformations apply).
	 *
	 * @param $title Title
	 * @param $user User
	 * @param $parserOptions null|ParserOptions
	 * @return Content
	 */
	public function preSaveTransform( Title $title, User $user, ParserOptions $parserOptions ) {
		return $this;
	}
}
