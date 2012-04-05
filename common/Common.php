<?php

/**
 * Generic scripting functions.
 */
class Scripting {
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
		global $wgScriptingDefaultEngine, $wgScriptingEngineConf;
		if( !$wgScriptingDefaultEngine ) {
			throw new MWException( 'Scripting extension is enabled but $wgScriptingDefaultEngine is not set' );
		}

		if( !isset( $wgScriptingEngineConf[$wgScriptingDefaultEngine] ) ) {
			throw new MWException( 'Invalid scripting engine is specified in $wgScriptingDefaultEngine' );
		}
		$options = $extraOptions + $wgScriptingEngineConf[$wgScriptingDefaultEngine];
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
		if( !isset( $parser->scripting_engine ) || !$parser->scripting_engine ) {
			$parser->scripting_engine = self::newDefaultEngine( array( 'parser' => $parser ) );
		}
		return $parser->scripting_engine;
	}

	/**
	 * Remove the current engine instance from the parser
	 */
	public static function resetParserEngine( $parser ) {
		$parser->scripting_engine = null;
	}
}

/**
 * An exception class which represents an error in the script. This does not 
 * normally abort the request, instead it is caught and shown to the user.
 */
class ScriptingException extends MWException {
	var $messageName, $params;

	function __construct( $messageName, $params = array() ) {
		if ( isset( $params['args'] ) ) {
			$args = $params['args'];
		} else {
			$args = array();
		}
		if ( isset( $params['module'] ) && isset( $params['line'] ) ) {
			$codelocation = wfMsg( 'scripting-codelocation', $params['module'], $params['line'] );
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
