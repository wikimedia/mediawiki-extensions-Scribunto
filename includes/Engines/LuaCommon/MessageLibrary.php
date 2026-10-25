<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;

class MessageLibrary extends LibraryBase {
	/** @inheritDoc */
	public function register() {
		$lib = [
			'plain' => $this->messagePlain( ... ),
			'text' => $this->messageText( ... ),
			'check' => $this->messageCheck( ... ),
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
	 * @throws LuaError
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
	 * @param array $data
	 * @return string[]
	 */
	private function messagePlain( $data ) {
		$msg = $this->makeMessage( $data, true );
		return [ $msg->plain() ];
	}

	/**
	 * Handler for messageText
	 *
	 * Unlike Message::text(), this preprocesses the message using the current
	 * parser and frame, so that templates and parser functions used by the
	 * message are tracked in the page's link tables (T62758, T231299).
	 *
	 * @param array $data
	 * @return string[]
	 * @throws LuaError
	 */
	private function messageText( $data ) {
		$parser = $this->getParser();
		if ( !$parser ) {
			throw new LuaError( 'mw.message:text() called without a parser' );
		}

		// Like Message::text(), raw parameters are substituted after preprocessing
		$rawParams = [];
		foreach ( $data['params'] as $i => $param ) {
			if ( is_array( $param ) && isset( $param['raw'] ) ) {
				$marker = Parser::MARKER_PREFIX . "-scribunto-rawparam-$i-" . Parser::MARKER_SUFFIX;
				$rawParams[$marker] = (string)$param['raw'];
				$data['params'][$i] = [ 'raw' => $marker ];
			}
		}
		$text = $this->makeMessage( $data, true )->plain();

		// Expand in an argument-less child frame, so that {{{1}}} in the message
		// isn't replaced with arguments of the {{#invoke:}}
		$frame = $this->getEngine()->getCurrentFrame()?->newChild() ?? false;
		$text = $parser->replaceVariables( $text, $frame );

		return [ strtr( $text, $rawParams ) ];
	}

	/**
	 * Handler for messageCheck
	 * @param string $what
	 * @param array $data
	 * @return bool[]
	 * @throws LuaError
	 */
	private function messageCheck( $what, $data ) {
		if ( !in_array( $what, [ 'exists', 'isBlank', 'isDisabled' ] ) ) {
			throw new LuaError( "invalid what for 'messageCheck'" );
		}

		$msg = $this->makeMessage( $data, false );
		return [ $msg->$what() ];
	}
}
