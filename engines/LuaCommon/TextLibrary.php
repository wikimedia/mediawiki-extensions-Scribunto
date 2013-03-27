<?php

class Scribunto_LuaTextLibrary extends Scribunto_LuaLibraryBase {
	function register() {
		$lib = array(
			'unstrip' => array( $this, 'textUnstrip' ),
			'getEntityTable' => array( $this, 'getEntityTable' ),
		);
		$this->getEngine()->registerInterface( 'mw.text.lua', $lib, array(
			'comma' => wfMessage( 'comma-separator' )->inContentLanguage()->text(),
			'and' => wfMessage( 'and' )->inContentLanguage()->text() .
				wfMessage( 'word-separator' )->inContentLanguage()->text(),
			'ellipsis' => wfMessage( 'ellipsis' )->inContentLanguage()->text(),
		) );
	}

	function textUnstrip( $s ) {
		$this->checkType( 'unstrip', 1, $s, 'string' );
		return array( $this->getParser()->mStripState->unstripBoth( $s ) );
	}

	function getEntityTable() {
		$flags = ENT_QUOTES;
		// PHP 5.3 compat
		if ( defined( "ENT_HTML5" ) ) {
			$flags |= constant( "ENT_HTML5" );
		}
		$table = array_flip( get_html_translation_table( HTML_ENTITIES, $flags, "UTF-8" ) );
		return array( $table );
	}
}
