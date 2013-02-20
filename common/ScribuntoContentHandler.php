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

	public function __construct( $modelId = 'Scribunto', $formats = array( 'CONTENT_FORMAT_TEXT' ) ) {
		parent::__construct( $modelId, $formats );
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
}
