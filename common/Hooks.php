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
	 * Register parser hooks.
	 * @param $parser Parser
	 */
	public static function setupParserHook( &$parser ) {
		$parser->setFunctionHook( 'invoke', 'ScribuntoHooks::callHook', SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'script', 'ScribuntoHooks::transcludeHook', SFH_NO_HASH | SFH_OBJECT_ARGS );
		return true;
	}

	/**
	 * Called when the interpreter is to be reset.
	 * 
	 * @static
	 * @param  $parser Parser
	 * @return bool
	 */
	public static function clearState( &$parser ) {
		Scribunto::resetParserEngine( $parser );
		return true;
	}

	/**
	 * Hook function for {{#invoke:module|func}}
	 *
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args array
	 * @return string
	 */
	public static function callHook( &$parser, $frame, $args ) {
		if( count( $args ) < 2 ) {
			throw new ScribuntoException( 'scribunto-common-nofunction' );
		}

		$module = $parser->mStripState->unstripBoth( array_shift( $args ) );
		$function = $frame->expand( array_shift( $args ) );
		return self::doRunHook( $parser, $frame, $module, $function, $args );
	}

	/**
	 * Hook function for {{script:module}}
	 *
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $args
	 * @return string
	 */
	public static function transcludeHook( &$parser, $frame, $args ) {
		$module = $parser->mStripState->unstripBoth( array_shift( $args ) );
		return self::doRunHook( $parser, $frame, $module, 'main', $args );
	}

	/**
	 * @param $parser Parser
	 * @param $frame PPFrame
	 * @param $moduleName
	 * @param $functionName
	 * @param $args
	 * @return string
	 * @throws ScribuntoException
	 */
	private static function doRunHook( $parser, $frame, $moduleName, $functionName, $args ) {
		wfProfileIn( __METHOD__ );
		
		try {
			$engine = Scribunto::getParserEngine( $parser );
			$title = Title::makeTitleSafe( NS_MODULE, $moduleName );
			if ( !$title ) {
				throw new ScribuntoException( 'scribunto-common-nosuchmodule' );
			}
			$module = $engine->fetchModuleFromParser( $title );
			if ( !$module ) {
				throw new ScribuntoException( 'scribunto-common-nosuchmodule' );
			}
			foreach( $args as &$arg ) {
				$arg = $frame->expand( $arg );
			}
			$result = $module->invoke( $functionName, $args, $frame );

			wfProfileOut( __METHOD__ );
			return trim( strval( $result ) );
		} catch( ScribuntoException $e ) {
			$trace = $e->getScriptTraceHtml( array( 'msgOptions' => array( 'content' ) ) );
			$html = Html::element( 'p', array(), $e->getMessage() );
			if ( $trace !== false ) {
				$html .= Html::element( 'p', array(), wfMsgForContent( 'scribunto-common-backtrace' ) ) . $trace;
			}
			$out = $parser->getOutput();
			if ( !isset( $out->scribunto_errors ) ) {
				$out->addOutputHook( 'ScribuntoError' );
				$out->scribunto_errors = array();
			}

			$out->scribunto_errors[] = $html;
			$id = 'mw-scribunto-error-' . ( count( $out->scribunto_errors ) - 1 );
			$parserError = wfMsgForContent( 'scribunto-parser-error' );
			wfProfileOut( __METHOD__ );

			// #iferror-compatible error element
			return "<strong class=\"error\"><span class=\"scribunto-error\" id=\"$id\">" . 
				$parserError. "</span></strong>";
		}
	}

	/**
	 * Overrides the standard view for modules. Enables syntax highlighting when
	 * possible.
	 *
	 * @param $text string
	 * @param $title Title
	 * @param $output OutputPage
	 * @return bool
	 */
	public static function handleScriptView( $text, $title, $output ) {
		global $wgScribuntoUseGeSHi;

		if( $title->getNamespace() == NS_MODULE ) {
			$engine = Scribunto::newDefaultEngine();
			$language = $engine->getGeSHiLanguage();
			
			if( $wgScribuntoUseGeSHi && $language ) {
				$geshi = SyntaxHighlight_GeSHi::prepare( $text, $language );
				$geshi->set_language( $language );
				if( $geshi instanceof GeSHi && !$geshi->error() ) {
					$code = $geshi->parse_code();
					if( $code ) {
						$output->addHeadItem( "source-{$language}", SyntaxHighlight_GeSHi::buildHeadItem( $geshi ) );
						$output->addHTML( "<div dir=\"ltr\">{$code}</div>" );
						return false;
					}
				}
			}

			// No GeSHi, or GeSHi can't parse it, use plain <pre>
			$output->addHTML( "<pre class=\"mw-code mw-script\" dir=\"ltr\">\n" );
			$output->addHTML( htmlspecialchars( $text ) );
			$output->addHTML( "\n</pre>\n" );
			return false;
		} else {
			return true;
		}
	}
	
	public static function getCodeLanguage( $title, &$lang ) {
		global $wgScribuntoUseCodeEditor;
		if( $wgScribuntoUseCodeEditor && $title->getNamespace() == NS_MODULE ) {
			$engine = Scribunto::newDefaultEngine();
			if( $engine->getCodeEditorLanguage() ) {
				$lang = $engine->getCodeEditorLanguage();
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Indicates that modules are not wikitext.
	 * @param $title Title
	 * @param $result
	 * @return bool
	 */
	public static function isWikitextPage( $title, &$result ) {
		if( $title->getNamespace() == NS_MODULE ) {
			$result = false;
			return false;
		}
		return true;
	}

	/**
	 * Adds report of number of evaluations by the single wikitext page.
	 * 
	 * @param $parser Parser
	 * @param $report
	 * @return bool
	 */
	public static function reportLimits( $parser, &$report ) {
		if ( Scribunto::isParserEnginePresent( $parser ) ) {
			$engine = Scribunto::getParserEngine( $parser );
			$report .= $engine->getLimitReport();
		}
		return true;
	}

	/**
	 * Adds the module namespaces.
	 */
	public static function addCanonicalNamespaces( &$list ) {
		$list[NS_MODULE] = 'Module';
		$list[NS_MODULE_TALK] = 'Module_talk';
		return true;
	}

	public static function validateScript( $editor, $text, &$error, $summary ) {
		global $wgUser, $wgOut, $wgScribuntoUseCodeEditor;
		$title = $editor->mTitle;

		if( $title->getNamespace() == NS_MODULE ) {
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

		return true;
	}

	public static function unitTestsList( &$files ) {
		$tests = array(
			'engines/LuaStandalone/LuaStandaloneInterpreterTest.php',
			'engines/LuaStandalone/LuaStandaloneEngineTest.php',
			'engines/LuaSandbox/LuaSandboxInterpreterTest.php',
			'engines/LuaSandbox/LuaSandboxEngineTest.php' );
		foreach ( $tests as $test ) {
			$files[] = dirname( __FILE__ ) .'/../tests/' . $test;
		}
		return true;
	}

	public static function parserOutputHook( $outputPage, $parserOutput ) {
		$outputPage->addModules( 'ext.scribunto' );
		$outputPage->addInlineScript( 'mw.loader.using("ext.scribunto", function() {' . 
			Xml::encodeJsCall( 'mw.scribunto.setErrors', array( $parserOutput->scribunto_errors ) )
			. '});' );
	}
}
