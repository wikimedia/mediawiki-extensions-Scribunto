<?php

class Scribunto_LuaLanguageModule {
	const MAX_LANG_CACHE_SIZE = 20;

	var $engine;
	var $langCache = array();

	function __construct( $engine ) {
		$this->engine = $engine;
	}

	function register() {
		// Pre-populate the language cache
		global $wgContLang;
		$this->langCache[$wgContLang->getCode()] = $wgContLang;

		$statics = array(
			'getContLangCode',
			'isValidCode',
			'isValidBuiltInCode',
			'fetchLanguageName',
		);
		$methods = array(
			'lcfirst',
			'ucfirst',
			'lc',
			'uc',
			'formatNum',
			'parseFormattedNumber',
			'convertPlural',
			'isRTL',
		);
		$lib = array();
		foreach ( $statics as $name ) {
			$lib[$name] = array( $this, $name );
		}
		foreach ( $methods as $name ) {
			$lib[$name] = function () use ( $name ) {
				$args = func_get_args();
				return $this->languageMethod( $name, $args );
			};
		}
		$this->engine->registerInterface( 'mw.language.lua', $lib );
	}

	function getContLangCode() {
		global $wgContLang;
		return array( $wgContLang->getCode() );
	}

	function isValidCode( $code ) {
		return array( Language::isValidCode( strval( $code ) ) );
	}

	function isValidBuiltInCode( $code ) {
		return array( (bool)Language::isValidBuiltInCode( strval( $code ) ) );
	}

	function fetchLanguageName( $code, $inLanguage ) {
		return array( Language::fetchLanguageName(
			strval( $code ), 
			$inLanguage === null ? null : strval( $inLanguage ) ) );
	}

	/**
	 * Language object method handler
	 */
	function languageMethod( $name, $args ) {
		$name = strval( $name );
		$code = array_shift( $args );
		if ( !isset( $this->langCache[$code] ) ) {
			if ( count( $this->langCache ) > self::MAX_LANG_CACHE_SIZE ) {
				throw new Scribunto_LuaError( 'too many language codes requested' );
			}
			$this->langCache[$code] = Language::factory( $code );
		}
		$lang = $this->langCache[$code];
		switch ( $name ) {
			// Zero arguments
			case 'isRTL':
				return array( $lang->$name() );

			// One argument passed straight through
			case 'lcfirst':
			case 'ucfirst':
			case 'lc':
			case 'uc':
			case 'parseFormattedNumber':
				return array( $lang->$name( strval( $args[0] ) ) );

			// Custom handling
			default:
				return $this->$name( $lang, $args );
		}
	}

	/**
	 * convertPlural handler
	 */
	function convertPlural( $lang, $args ) {
		if ( !is_array( $args[1] ) ) {
			throw new Scribunto_LuaError( "the second argument to mw.language:convertPlural() " . 
				"must be an array" );
		}
		$text = strval( $args[0] );
		$forms = array_map( 'strval', $args[1] );
		return array( $lang->convertPlural( $text, $forms ) );
	}

	/**
	 * formatNum handler
	 */
	function formatNum( $lang, $args ) {
		if ( !is_scalar( $args[0] ) ) {
			throw new Scribunto_LuaError( "the first argument to mw.language:formatNum() must " .
				"be a number" );
		}
		$num = $args[0];

		$noCommafy = false;
		if ( isset( $args[1] ) && is_array( $args[1] ) ) {
			$options = $args[1];
			$noCommafy = !empty( $options['noCommafy'] );
		}
		return array( $lang->formatNum( $num, $noCommafy ) );
	}
}
