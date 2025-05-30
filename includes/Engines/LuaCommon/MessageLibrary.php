<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;

class MessageLibrary extends LibraryBase {
	/** @inheritDoc */
	public function register() {
		$lib = [
			'plain' => [ $this, 'messagePlain' ],
			'check' => [ $this, 'messageCheck' ],
		];

		// Get the correct default language from the parser
		if ( $this->getParser() ) {
			$lang = $this->getParser()->getTargetLanguage();
		} else {
			$lang = MediaWikiServices::getInstance()->getContentLanguage();
		}

		return $this->getEngine()->registerInterface( 'mw.message.lua', $lib, [
			'lang' => $lang->getCode(),
		] );
	}

	/**
	 * Create a Message
	 * @param array $data
	 *  - 'rawMessage': (string, optional) If set, create a RawMessage using this as `$text`
	 *  - 'keys': (string|string[]) Message keys. Required unless 'rawMessage' is set.
	 *  - 'lang': (Language|StubUserLang|string) Language for the Message.
	 *  - 'useDB': (bool) "Use database" flag.
	 *  - 'params': (array) Parameters for the Message. May be omitted if $setParams is false.
	 * @param bool $setParams Whether to use $data['params']
	 * @return Message
	 */
	private function makeMessage( $data, $setParams ) {
		if ( isset( $data['rawMessage'] ) ) {
			$msg = new RawMessage( $data['rawMessage'] );
		} else {
			$msg = Message::newFallbackSequence( $data['keys'] );
		}
		if ( is_string( $data['lang'] ) &&
			!MediaWikiServices::getInstance()->getLanguageNameUtils()->isValidCode( $data['lang'] )
		) {
			throw new LuaError( "language code '{$data['lang']}' is invalid" );
		} else {
			$msg->inLanguage( $data['lang'] );
		}
		$msg->useDatabase( $data['useDB'] );
		if ( $setParams ) {
			foreach ( $data['params'] as $param ) {
				// Only rawParam and numParam are supposed by the Lua message API
				if ( is_array( $param ) && isset( $param['raw'] ) ) {
					$msg->rawParams( $param );
				} elseif ( is_array( $param ) && isset( $param['num'] ) ) {
					$msg->numParams( $param );
				} else {
					$msg->params( $param );
				}
			}
		}
		return $msg;
	}

	/**
	 * Handler for messagePlain
	 * @internal
	 * @param array $data
	 * @return string[]
	 */
	public function messagePlain( $data ) {
		$msg = $this->makeMessage( $data, true );
		return [ $msg->plain() ];
	}

	/**
	 * Handler for messageCheck
	 * @internal
	 * @param string $what
	 * @param array $data
	 * @return bool[]
	 */
	public function messageCheck( $what, $data ) {
		if ( !in_array( $what, [ 'exists', 'isBlank', 'isDisabled' ] ) ) {
			throw new LuaError( "invalid what for 'messageCheck'" );
		}

		$msg = $this->makeMessage( $data, false );
		return [ $msg->$what() ];
	}
}
