<?php

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserFactory;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use ObjectCacheFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for serving debug console requests on the edit page
 */
class ApiScribuntoConsole extends ApiBase {
	private const SC_MAX_SIZE = 500000;
	private const SC_SESSION_EXPIRY = 3600;
	private ObjectCacheFactory $objectCacheFactory;
	private ParserFactory $parserFactory;

	public function __construct(
		ApiMain $main,
		string $action,
		ObjectCacheFactory $objectCacheFactory,
		ParserFactory $parserFactory
	) {
		parent::__construct( $main, $action );
		$this->objectCacheFactory = $objectCacheFactory;
		$this->parserFactory = $parserFactory;
	}

	/**
	 * @suppress PhanTypePossiblyInvalidDimOffset
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$title = Title::newFromText( $params['title'] );
		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['title'] ) ] );
		}

		if ( $params['session'] ) {
			$sessionId = $params['session'];
		} else {
			$sessionId = mt_rand( 0, 0x7fffffff );
		}

		$cache = $this->objectCacheFactory->getInstance( CACHE_ANYTHING );
		$sessionKey = $cache->makeKey( 'scribunto-console', $this->getUser()->getId(), $sessionId );
		$session = null;
		$sessionIsNew = false;
		if ( $params['session'] ) {
			$session = $cache->get( $sessionKey );
		}
		if ( !isset( $session['version'] ) ) {
			$session = $this->newSession();
			$sessionIsNew = true;
		}

		// Create a variable holding the session which will be stored if there
		// are no errors. If there are errors, we don't want to store the current
		// question to the state builder array, since that will cause subsequent
		// requests to fail.
		$newSession = $session;

		if ( !empty( $params['clear'] ) ) {
			$newSession['size'] -= strlen( implode( '', $newSession['questions'] ) );
			$newSession['questions'] = [];
			$session['questions'] = [];
		}
		if ( strlen( $params['question'] ) ) {
			$newSession['size'] += strlen( $params['question'] );
			$newSession['questions'][] = $params['question'];
		}
		if ( $params['content'] ) {
			$newSession['size'] += strlen( $params['content'] ) - strlen( $newSession['content'] );
			$newSession['content'] = $params['content'];
		}

		if ( $newSession['size'] > self::SC_MAX_SIZE ) {
			$this->dieWithError( 'scribunto-console-too-large' );
		}
		$result = $this->runConsole( [
			'title' => $title,
			'content' => $newSession['content'],
			'prevQuestions' => $session['questions'],
			'question' => $params['question'],
		] );

		if ( $result['type'] === 'error' ) {
			// Restore the questions array
			$newSession['questions'] = $session['questions'];
		}
		$cache->set( $sessionKey, $newSession, self::SC_SESSION_EXPIRY );
		$result['session'] = $sessionId;
		$result['sessionSize'] = $newSession['size'];
		$result['sessionMaxSize'] = self::SC_MAX_SIZE;
		if ( $sessionIsNew ) {
			$result['sessionIsNew'] = '';
		}
		foreach ( $result as $key => $value ) {
			$this->getResult()->addValue( null, $key, $value );
		}
	}

	/**
	 * Execute the console
	 * @param array $params
	 *  - 'title': (Title) Module being processed
	 *  - 'content': (string) New module text
	 *  - 'prevQuestions': (string[]) Previous values for 'question' in this session.
	 *  - 'question': (string) Lua code to run.
	 * @return array Result data
	 */
	protected function runConsole( array $params ) {
		$parser = $this->parserFactory->getInstance();
		$options = new ParserOptions( $this->getUser() );
		$parser->startExternalParse( $params['title'], $options, Parser::OT_HTML, true );
		$engine = Scribunto::getParserEngine( $parser );
		try {
			$result = $engine->runConsole( $params );
		} catch ( ScribuntoException $e ) {
			$trace = $e->getScriptTraceHtml();
			$message = $e->getMessage();
			$html = Html::element( 'p', [], $message );
			if ( $trace !== false ) {
				$html .= Html::element( 'p',
					[],
					$this->msg( 'scribunto-common-backtrace' )->inContentLanguage()->text()
				) . $trace;
			}

			return [
				'type' => 'error',
				'html' => $html,
				'message' => $message,
				'messagename' => $e->getMessageName() ];
		}
		return [
			'type' => 'normal',
			'print' => strval( $result['print'] ),
			'return' => strval( $result['return'] )
		];
	}

	/**
	 * @return array
	 */
	protected function newSession() {
		return [
			'content' => '',
			'questions' => [],
			'size' => 0,
			'version' => 1,
		];
	}

	/** @inheritDoc */
	public function needsToken() {
		return 'csrf';
	}

	/** @inheritDoc */
	public function isInternal() {
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'title' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'content' => [
				ParamValidator::PARAM_TYPE => 'text'
			],
			'session' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'question' => [
				ParamValidator::PARAM_TYPE => 'text',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'clear' => [
				ParamValidator::PARAM_TYPE => 'boolean',
			],
		];
	}
}
