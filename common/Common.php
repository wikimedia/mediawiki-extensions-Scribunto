<?php

/**
 * Static function collection for general extension support.
 */
class Scribunto {
	const LOCAL = 'local';

	/**
	 * Create a new engine object with specified parameters.
	 */
	public static function newEngine( $options ) {
		$class = $options['class'];
		return new $class( $options );
	}

	/**
	 * Create a new engine object with default parameters
	 * @param $extraOptions Extra options to pass to the constructor, in addition to the configured options
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
	 */
	public static function getParserEngine( $parser ) {
		if( empty( $parser->scribunto_engine ) ) {
			$parser->scribunto_engine = self::newDefaultEngine( array( 'parser' => $parser ) );
		}
		return $parser->scribunto_engine;
	}

	/**
	 * Check if an engine instance is present in the given parser
	 */
	public static function isParserEnginePresent( $parser ) {
		return !empty( $parser->scribunto_engine );
	}

	/**
	 * Remove the current engine instance from the parser
	 */
	public static function resetParserEngine( $parser ) {
		$parser->scribunto_engine = null;
	}
}

/**
 * An exception class which represents an error in the script. This does not 
 * normally abort the request, instead it is caught and shown to the user.
 */
class ScribuntoException extends MWException {
	var $messageName, $params;

	function __construct( $messageName, $params = array() ) {
		if ( isset( $params['args'] ) ) {
			$args = $params['args'];
		} else {
			$args = array();
		}
		if ( isset( $params['module'] ) && isset( $params['line'] ) ) {
			$codelocation = wfMsg( 'scribunto-codelocation', $params['module'], $params['line'] );
		} else {
			$codelocation = '[UNKNOWN]'; // should never happen
		}
		array_unshift( $args, $codelocation );
		$msg = wfMsgExt( $messageName, array(), $args );
		parent::__construct( $msg );

		$this->messageName = $messageName;
		$this->params = $params;
	}

	public function getMessageName() {
		return $this->messageName;
	}
}
