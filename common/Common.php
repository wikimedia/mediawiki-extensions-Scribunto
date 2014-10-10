<?php

/**
 * Static function collection for general extension support.
 */
class Scribunto {
	const LOCAL = 'local';

	/**
	 * Create a new engine object with specified parameters.
	 *
	 * @param array $options
	 * @return ScribuntoEngineBase
	 */
	public static function newEngine( $options ) {
		$class = $options['class'];
		return new $class( $options );
	}

	/**
	 * Create a new engine object with default parameters
	 *
	 * @param $extraOptions array Extra options to pass to the constructor, in addition to the configured options
	 * @throws MWException
	 * @return ScribuntoEngineBase
	 */
	public static function newDefaultEngine( $extraOptions = array() ) {
		global $wgScribuntoDefaultEngine, $wgScribuntoEngineConf;
		if( !$wgScribuntoDefaultEngine ) {
			throw new MWException( 'Scribunto extension is enabled but $wgScribuntoDefaultEngine is not set' );
		}

		if( !isset( $wgScribuntoEngineConf[$wgScribuntoDefaultEngine] ) ) {
			throw new MWException( 'Invalid scripting engine is specified in $wgScribuntoDefaultEngine' );
		}
		$options = $extraOptions + $wgScribuntoEngineConf[$wgScribuntoDefaultEngine];
		return self::newEngine( $options );
	}

	/**
	 * Get an engine instance for the given parser, and cache it in the parser
	 * so that subsequent calls to this function for the same parser will return
	 * the same engine.
	 *
	 * @param Parser $parser
	 * @return ScribuntoEngineBase
	 */
	public static function getParserEngine( Parser $parser ) {
		if( empty( $parser->scribunto_engine ) ) {
			$parser->scribunto_engine = self::newDefaultEngine( array( 'parser' => $parser ) );
			$parser->scribunto_engine->setTitle( $parser->getTitle() );
		}
		return $parser->scribunto_engine;
	}

	/**
	 * Check if an engine instance is present in the given parser
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function isParserEnginePresent( Parser $parser ) {
		return !empty( $parser->scribunto_engine );
	}

	/**
	 * Remove the current engine instance from the parser
	 */
	public static function resetParserEngine( Parser $parser ) {
		if ( !empty( $parser->scribunto_engine ) ) {
			$parser->scribunto_engine->destroy();
			$parser->scribunto_engine = null;
		}
	}

	/**
	 * Test whether the page should be considered a documentation page
	 *
	 * @param Title $title
	 * @param Title &$forModule Module for which this is a doc page
	 * @return boolean
	 */
	public static function isDocPage( Title $title, Title &$forModule = null ) {
		$docPage = wfMessage( 'scribunto-doc-page-name' )->inContentLanguage();
		if ( $docPage->isDisabled() ) {
			return false;
		}

		// Canonicalize the input pseudo-title. The unreplaced "$1" shouldn't
		// cause a problem.
		$docTitle = Title::newFromText( $docPage->plain() );
		if ( !$docTitle ) {
			return false;
		}
		$docPage = $docTitle->getPrefixedText();

		// Make it into a regex, and match it against the input title
		$docPage = str_replace( '\\$1', '(.+)', preg_quote( $docPage, '/' ) );
		if ( preg_match( "/^$docPage$/", $title->getPrefixedText(), $m ) ) {
			$forModule = Title::makeTitleSafe( NS_MODULE, $m[1] );
			return $forModule !== null;
		} else {
			return false;
		}
	}

	/**
	 * Return the Title for the documentation page
	 *
	 * @param Title $title
	 * @return Title|null
	 */
	public static function getDocPage( Title $title ) {
		$docPage = wfMessage( 'scribunto-doc-page-name', $title->getText() )->inContentLanguage();
		if ( $docPage->isDisabled() ) {
			return null;
		}

		return Title::newFromText( $docPage->plain() );
	}
}

/**
 * An exception class which represents an error in the script. This does not
 * normally abort the request, instead it is caught and shown to the user.
 */
class ScribuntoException extends MWException {
	/**
	 * @var string
	 */
	public $messageName;

	/**
	 * @var array
	 */
	public $messageArgs;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * @param string $messageName
	 * @param array $params
	 */
	function __construct( $messageName, $params = array() ) {
		if ( isset( $params['args'] ) ) {
			$this->messageArgs = $params['args'];
		} else {
			$this->messageArgs = array();
		}
		if ( isset( $params['module'] ) && isset( $params['line'] ) ) {
			$codeLocation = false;
			if ( isset( $params['title'] ) ) {
				$moduleTitle = Title::newFromText( $params['module'] );
				if ( $moduleTitle && $moduleTitle->equals( $params['title'] ) ) {
					$codeLocation = wfMessage( 'scribunto-line', $params['line'] )->inContentLanguage()->text();
				}
			}
			if ( $codeLocation === false ) {
				$codeLocation = wfMessage(
					'scribunto-module-line',
					$params['module'],
					$params['line']
				)->inContentLanguage()->text();
			}
		} else {
			$codeLocation = '[UNKNOWN]';
		}
		array_unshift( $this->messageArgs, $codeLocation );
		$msg = wfMessage( $messageName )->params( $this->messageArgs )->inContentLanguage()->text();
		parent::__construct( $msg );

		$this->messageName = $messageName;
		$this->params = $params;
	}

	/**
	 * @return string
	 */
	public function getMessageName() {
		return $this->messageName;
	}

	public function toStatus() {
		$args = array_merge( array( $this->messageName ), $this->messageArgs );
		$status = call_user_func_array( array( 'Status', 'newFatal' ), $args );
		$status->scribunto_error = $this;
		return $status;
	}

	/**
	 * Get the backtrace as HTML, or false if there is none available.
	 */
	public function getScriptTraceHtml( $options = array() ) {
		return false;
	}
}
