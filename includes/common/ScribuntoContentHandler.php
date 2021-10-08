<?php

use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\MediaWikiServices;

/**
 * Scribunto Content Handler
 *
 * @file
 * @ingroup Extensions
 * @ingroup Scribunto
 *
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

class ScribuntoContentHandler extends CodeContentHandler {

	/**
	 * @param string $modelId
	 * @param string[] $formats
	 */
	public function __construct(
		$modelId = CONTENT_MODEL_SCRIBUNTO, $formats = [ CONTENT_FORMAT_TEXT ]
	) {
		parent::__construct( $modelId, $formats );
	}

	/**
	 * @return string Class name
	 */
	protected function getContentClass() {
		return ScribuntoContent::class;
	}

	/**
	 * @param string $format
	 * @return bool
	 */
	public function isSupportedFormat( $format ) {
		// An error in an earlier version of Scribunto means we might see this.
		if ( $format === 'CONTENT_FORMAT_TEXT' ) {
			$format = CONTENT_FORMAT_TEXT;
		}
		return parent::isSupportedFormat( $format );
	}

	/**
	 * Only allow this content handler to be used in the Module namespace
	 * @param Title $title
	 * @return bool
	 */
	public function canBeUsedOn( Title $title ) {
		if ( $title->getNamespace() !== NS_MODULE ) {
			return false;
		}

		return parent::canBeUsedOn( $title );
	}

	/**
	 * @inheritDoc
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		'@phan-var ScribuntoContent $content';
		$page = $cpoParams->getPage();
		$title = Title::castFromPageReference( $page );
		$parserOptions = $cpoParams->getParserOptions();
		$revId = $cpoParams->getRevId();
		$generateHtml = $cpoParams->getGenerateHtml();
		$parser = MediaWikiServices::getInstance()->getParser();
		$text = $content->getText();

		// Get documentation, if any
		$output = new ParserOutput();
		// @phan-suppress-next-line PhanTypeMismatchArgument
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

				if ( $parserOptions->getTargetLanguage() === null ) {
					$parserOptions->setTargetLanguage( $doc->getPageLanguage() );
				}
				$output = $parser->parse( $docWikitext, $page, $parserOptions, true, true, $revId );
			}

			// Mark the doc page as a transclusion, so we get purged when it
			// changes.
			$output->addTemplate( $doc, $doc->getArticleID(), $doc->getLatestRevID() );
		}

		// Validate the script, and include an error message and tracking
		// category if it's invalid
		// @phan-suppress-next-line PhanTypeMismatchArgument
		$status = $content->validate( $title );
		if ( !$status->isOK() ) {
			$output->setText( $output->getRawText() .
				Html::rawElement( 'div', [ 'class' => 'errorbox' ],
					$status->getHTML( 'scribunto-error-short', 'scribunto-error-long' )
				)
			);
			$trackingCategories = MediaWikiServices::getInstance()->getTrackingCategories();
			$trackingCategories->addTrackingCategory( $output, 'scribunto-module-with-errors-category', $page );
		}

		if ( !$generateHtml ) {
			// We don't need the actual HTML
			$output->setText( '' );
			return;
		}

		$engine = Scribunto::newDefaultEngine();
		// @phan-suppress-next-line PhanTypeMismatchArgument
		$engine->setTitle( $title );
		if ( $this->highlight( $text, $output, $engine ) ) {
			return;
		}

		// No GeSHi, or GeSHi can't parse it, use plain <pre>
		$output->setText( $output->getRawText() .
			"<pre class='mw-code mw-script' dir='ltr'>\n" .
			htmlspecialchars( $text ) .
			"\n</pre>\n"
		);
	}

	/**
	 * Adds syntax highlighting to the output (or do not touch it and return false).
	 * @param string $text
	 * @param ParserOutput $output
	 * @param ScribuntoEngineBase $engine
	 * @return bool Success status
	 */
	protected function highlight( $text, ParserOutput $output, ScribuntoEngineBase $engine ) {
		global $wgScribuntoUseGeSHi;
		$language = $engine->getGeSHiLanguage();
		if ( $wgScribuntoUseGeSHi && class_exists( SyntaxHighlight::class ) && $language ) {
			$status = SyntaxHighlight::highlight( $text, $language, [ 'line' => true, 'linelinks' => 'L' ] );
			if ( $status->isGood() ) {
				// @todo replace addModuleStyles line with the appropriate call on
				// SyntaxHighlight once one is created
				$output->addModuleStyles( 'ext.pygments' );
				$output->addModules( 'ext.pygments.linenumbers' );
				$output->setText( $output->getRawText() . $status->getValue() );
				return true;
			}
		}
		return false;
	}
}
