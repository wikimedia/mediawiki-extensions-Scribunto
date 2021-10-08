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

	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
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
		return $engine->validate( $this->getText(), $title->getPrefixedDBkey() );
	}

	/** @inheritDoc */
	public function prepareSave( WikiPage $page, $flags, $parentRevId, User $user ) {
		return $this->validate( $page->getTitle() );
	}
}
