<?php
/**
 * Wikitext scripting infrastructure for MediaWiki: hooks.
 * Copyright (C) 2009-2012 Victor Vasiliev <vasilvv@gmail.com>
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Hooks for the Scribunto extension.
 */
class ScribuntoHooks {
	/**
	 * Get software information for Special:Version
	 *
	 * @param array &$software
	 * @return bool
	 */
	public static function getSoftwareInfo( array &$software ) {
		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( Title::makeTitle( NS_SPECIAL, 'Version' ) );
		$engine->getSoftwareInfo( $software );
		return true;
	}

	/**
	 * Register parser hooks.
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function setupParserHook( Parser &$parser ) {
		$parser->setFunctionHook( 'invoke', 'ScribuntoHooks::invokeHook', Parser::SFH_OBJECT_ARGS );
		return true;
	}

	/**
	 * Called when the interpreter is to be reset.
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function clearState( Parser &$parser ) {
		Scribunto::resetParserEngine( $parser );
		return true;
	}

	/**
	 * Called when the parser is cloned
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function parserCloned( Parser $parser ) {
		$parser->scribunto_engine = null;
		return true;
	}

	/**
	 * Hook function for {{#invoke:module|func}}
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 * @throws MWException
	 * @throws ScribuntoException
	 * @return string
	 */
	public static function invokeHook( Parser &$parser, PPFrame $frame, array $args ) {
		if ( !@constant( get_class( $frame ) . '::SUPPORTS_INDEX_OFFSET' ) ) {
			throw new MWException(
				'Scribunto needs MediaWiki 1.20 or later (Preprocessor::SUPPORTS_INDEX_OFFSET)' );
		}

		try {
			if ( count( $args ) < 2 ) {
				throw new ScribuntoException( 'scribunto-common-nofunction' );
			}
			$moduleName = trim( $frame->expand( $args[0] ) );
			$engine = Scribunto::getParserEngine( $parser );
			$title = Title::makeTitleSafe( NS_MODULE, $moduleName );
			if ( !$title || Scribunto::isDocPage( $title ) ) {
				throw new ScribuntoException( 'scribunto-common-nosuchmodule', array( 'args' => array( $moduleName ) ) );
			}
			$module = $engine->fetchModuleFromParser( $title );
			if ( !$module ) {
				throw new ScribuntoException( 'scribunto-common-nosuchmodule', array( 'args' => array( $moduleName ) ) );
			}
			$functionName = trim( $frame->expand( $args[1] ) );

			$bits = $args[1]->splitArg();
			unset( $args[0] );
			unset( $args[1] );

			// If $bits['index'] is empty, then the function name was parsed as a
			// key=value pair (because of an equals sign in it), and since it didn't
			// have an index, we don't need the index offset.
			$childFrame = $frame->newChild( $args, $title, $bits['index'] === '' ? 0 : 1 );
			$result = $module->invoke( $functionName, $childFrame );
			return UtfNormal::cleanUp( strval( $result ) );
		} catch( ScribuntoException $e ) {
			$trace = $e->getScriptTraceHtml( array( 'msgOptions' => array( 'content' ) ) );
			$html = Html::element( 'p', array(), $e->getMessage() );
			if ( $trace !== false ) {
				$html .= Html::element( 'p',
					array(),
					wfMessage( 'scribunto-common-backtrace' )->inContentLanguage()->text()
				) . $trace;
			} else {
				$html .= Html::element( 'p',
					array(),
					wfMessage( 'scribunto-common-no-details' )->inContentLanguage()->text()
				);
			}
			$out = $parser->getOutput();
			$errors = $out->getExtensionData( 'ScribuntoErrors' );
			if ( $errors === null ) {
				// On first hook use, set up error array and output
				$errors = array();
				$parser->addTrackingCategory( 'scribunto-common-error-category' );
				$out->addModules( 'ext.scribunto.errors' );
			}
			$errors[] = $html;
			$out->setExtensionData( 'ScribuntoErrors', $errors );
			$out->addJsConfigVars( 'ScribuntoErrors', $errors );
			$id = 'mw-scribunto-error-' . ( count( $errors ) - 1 );
			$parserError = htmlspecialchars( $e->getMessage() );

			// #iferror-compatible error element
			return "<strong class=\"error\"><span class=\"scribunto-error\" id=\"$id\">" .
				$parserError. "</span></strong>";
		}
	}

	/**
	 * @param Title $title
	 * @param string &$languageCode
	 * @return bool
	 */
	public static function getCodeLanguage( Title $title, &$languageCode ) {
		global $wgScribuntoUseCodeEditor;
		if( $wgScribuntoUseCodeEditor && $title->getNamespace() == NS_MODULE &&
			!Scribunto::isDocPage( $title )
		) {
			$engine = Scribunto::newDefaultEngine();
			if( $engine->getCodeEditorLanguage() ) {
				$languageCode = $engine->getCodeEditorLanguage();
				return false;
			}
		}

		return true;
	}

	/**
	 * Set the Scribunto content handler for modules
	 *
	 * @param Title $title
	 * @param string &$model
	 * @return bool
	 */
	public static function contentHandlerDefaultModelFor( Title $title, &$model ) {
		if( $title->getNamespace() == NS_MODULE && !Scribunto::isDocPage( $title ) ) {
			$model = 'Scribunto';
			return false;
		}
		return true;
	}

	/**
	 * Adds report of number of evaluations by the single wikitext page.
	 *
	 * @deprecated
	 * @param Parser $parser
	 * @param string $report
	 * @return bool
	 */
	public static function reportLimits( Parser $parser, &$report ) {
		if ( Scribunto::isParserEnginePresent( $parser ) ) {
			$engine = Scribunto::getParserEngine( $parser );
			$report .= $engine->getLimitReport();
		}
		return true;
	}

	/**
	 * Adds report of number of evaluations by the single wikitext page.
	 *
	 * @param Parser $parser
	 * @param ParserOutput $output
	 * @return bool
	 */
	public static function reportLimitData( Parser $parser, ParserOutput $output ) {
		// Unhook the deprecated hook, since the new one exists.
		global $wgHooks;
		unset( $wgHooks['ParserLimitReport']['scribunto'] );

		if ( Scribunto::isParserEnginePresent( $parser ) ) {
			$engine = Scribunto::getParserEngine( $parser );
			$engine->reportLimitData( $output );
		}
		return true;
	}

	/**
	 * Formats the limit report data
	 *
	 * @param string $key
	 * @param string &$value
	 * @param string &$report
	 * @param bool $isHTML
	 * @param bool $localize
	 * @return bool
	 */
	public static function formatLimitData( $key, &$value, &$report, $isHTML, $localize ) {
		$engine = Scribunto::newDefaultEngine();
		return $engine->formatLimitData( $key, $value, $report, $isHTML, $localize );
	}

	/**
	 * Adds the module namespaces.
	 *
	 * @param string[] $list
	 * @return bool
	 */
	public static function addCanonicalNamespaces( array &$list ) {
		$list[NS_MODULE] = 'Module';
		$list[NS_MODULE_TALK] = 'Module_talk';
		return true;
	}

	/**
	 * EditPageBeforeEditChecks hook
	 *
	 * @param EditPage $editor
	 * @param array $checkboxes Checkbox array
	 * @param int $tabindex Current tabindex
	 * @return bool
	 */
	public static function beforeEditChecks( EditPage &$editor, &$checkboxes, &$tabindex ) {
		if ( $editor->getTitle()->getNamespace() !== NS_MODULE ) {
			return true;
		}

		if ( Scribunto::isDocPage( $editor->getTitle() ) ) {
			return true;
		}

		global $wgOut;
		$wgOut->addModules( 'ext.scribunto.edit' );
		$editor->editFormTextAfterTools = '<div id="mw-scribunto-console"></div>';
		return true;
	}

	/**
	 * EditPage::showReadOnlyForm:initial hook
	 *
	 * @param EditPage $editor
	 * @param OutputPage $output
	 */
	public static function showReadOnlyFormInitial( EditPage $editor, OutputPage $output ) {
		if ( $editor->getTitle()->getNamespace() !== NS_MODULE ) {
			return true;
		}

		if ( Scribunto::isDocPage( $editor->getTitle() ) ) {
			return true;
		}

		$output->addModules( 'ext.scribunto.edit' );
		return true;
	}

	/**
	 * EditPageBeforeEditButtons hook
	 *
	 * @param EditPage $editor
	 * @param array $buttons Button array
	 * @param int $tabindex Current tabindex
	 * @return bool
	 */
	public static function beforeEditButtons( EditPage &$editor, array &$buttons, &$tabindex ) {
		if ( $editor->getTitle()->getNamespace() !== NS_MODULE ) {
			return true;
		}

		if ( Scribunto::isDocPage( $editor->getTitle() ) ) {
			return true;
		}

		unset( $buttons['preview'] );
		return true;
	}

	/**
	 * @param EditPage $editor
	 * @param string $text
	 * @param string $error
	 * @param string $summary
	 * @return bool
	 */
	public static function validateScript( EditPage $editor, $text, &$error, $summary ) {
		global $wgOut;
		$title = $editor->getTitle();

		if( $title->getNamespace() != NS_MODULE ) {
			return true;
		}

		if ( Scribunto::isDocPage( $title ) ) {
			return true;
		}

		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( $title );
		$status = $engine->validate( $text, $title->getPrefixedDBkey() );
		if( $status->isOK() ) {
			return true;
		}

		$errmsg = $status->getWikiText( 'scribunto-error-short', 'scribunto-error-long' );
		$error = <<<WIKI
<div class="errorbox">
{$errmsg}
</div>
<br clear="all" />
WIKI;
		if ( isset( $status->scribunto_error->params['module'] ) ) {
			$module = $status->scribunto_error->params['module'];
			$line = $status->scribunto_error->params['line'];
			if ( $module === $title->getPrefixedDBkey() && preg_match( '/^\d+$/', $line ) ) {
				$wgOut->addInlineScript( 'window.location.hash = ' . Xml::encodeJsVar( "#mw-ce-l$line" ) );
			}
		}

		return true;
	}

	/**
	 * @param array $files
	 * @return bool
	 */
	public static function unitTestsList( array &$files ) {
		$tests = array(
			'engines/LuaStandalone/LuaStandaloneInterpreterTest.php',
			'engines/LuaStandalone/StandaloneTest.php',
			'engines/LuaSandbox/LuaSandboxInterpreterTest.php',
			'engines/LuaSandbox/SandboxTest.php',
			'engines/LuaCommon/LuaEnvironmentComparisonTest.php',
			'engines/LuaCommon/CommonTest.php',
			'engines/LuaCommon/SiteLibraryTest.php',
			'engines/LuaCommon/UriLibraryTest.php',
			'engines/LuaCommon/UstringLibraryTest.php',
			'engines/LuaCommon/MessageLibraryTest.php',
			'engines/LuaCommon/TitleLibraryTest.php',
			'engines/LuaCommon/TextLibraryTest.php',
			'engines/LuaCommon/HtmlLibraryTest.php',
			'engines/LuaCommon/LanguageLibraryTest.php',
			'engines/LuaCommon/UstringLibraryPureLuaTest.php',
			'engines/LuaCommon/LibraryUtilTest.php',
		);
		foreach ( $tests as $test ) {
			$files[] = __DIR__ . '/../tests/' . $test;
		}
		return true;
	}

	/**
	 * @param Article &$article
	 * @param bool &$outputDone
	 * @param bool &$pcache
	 * @return bool
	 */
	public static function showDocPageHeader( Article &$article, &$outputDone, &$pcache ) {
		global $wgOut;

		$title = $article->getTitle();
		if ( Scribunto::isDocPage( $title, $forModule ) ) {
			$wgOut->addHTML(
				wfMessage( 'scribunto-doc-page-header', $forModule->getPrefixedText() )->parseAsBlock()
			);
		}
		return true;
	}
}
