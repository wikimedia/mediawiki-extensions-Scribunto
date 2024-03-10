<?php

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Extension\CodeEditor\Hooks\CodeEditorGetPageLanguageHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * Hooks from CodeEditor extension,
 * which is optional to use with this extension.
 */
class CodeEditorHooks implements CodeEditorGetPageLanguageHook {

	/**
	 * @param Title $title
	 * @param string|null &$languageCode
	 * @param string $model
	 * @param string $format
	 * @return bool
	 */
	public function onCodeEditorGetPageLanguage( Title $title, ?string &$languageCode, string $model, string $format ) {
		$useCodeEditor = MediaWikiServices::getInstance()->getMainConfig()->get( 'ScribuntoUseCodeEditor' );
		if ( $useCodeEditor && $title->hasContentModel( CONTENT_MODEL_SCRIBUNTO ) ) {
			$engine = Scribunto::newDefaultEngine();
			if ( $engine->getCodeEditorLanguage() ) {
				$languageCode = $engine->getCodeEditorLanguage();
				return false;
			}
		}

		return true;
	}

}
