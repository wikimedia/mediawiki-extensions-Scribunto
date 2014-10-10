<?php
/**
 * Scribunto Content Handler
 *
 * @file
 * @ingroup Extensions
 * @ingroup Scribunto
 *
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

class ScribuntoContentHandler extends TextContentHandler {

	/**
	 * @param string $modelId
	 * @param string[] $formats
	 */
	public function __construct( $modelId = 'Scribunto', array $formats = array( CONTENT_FORMAT_TEXT ) ) {
		parent::__construct( $modelId, $formats );
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
	 * Unserializes a ScribuntoContent object.
	 *
	 * @param  $text    string       Serialized form of the content
	 * @param  $format  null|string  The format used for serialization
	 * @return Content  the ScribuntoContent object wrapping $text
	 */
	public function unserializeContent( $text, $format = null ) {
		$this->checkFormat( $format );
		return new ScribuntoContent( $text );
	}

	/**
	 * Creates an empty ScribuntoContent object.
	 *
	 * @return  Content
	 */
	public function makeEmptyContent() {
		return new ScribuntoContent( '' );
	}

	/**
	 * Scripts themselves should be in English.
	 *
	 * @param Title $title
	 * @param Content $content
	 * @return Language wfGetLangObj( 'en' )
	 */
	public function getPageLanguage( Title $title, Content $content = null ) {
		return wfGetLangObj( 'en' );
	}

	/**
	 * Scripts themselves should be in English.
	 *
	 * @param Title $title
	 * @param Content $content
	 * @return Language wfGetLangObj( 'en' )
	 */
	public function getPageViewLanguage( Title $title, Content $content = null ) {
		return wfGetLangObj( 'en' );
	}
}
