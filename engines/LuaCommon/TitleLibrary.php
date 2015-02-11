<?php

class Scribunto_LuaTitleLibrary extends Scribunto_LuaLibraryBase {
	// Note these caches are naturally limited to
	// $wgExpensiveParserFunctionLimit + 1 actual Title objects because any
	// addition besides the one for the current page calls
	// incrementExpensiveFunctionCount()
	private $titleCache = array();
	private $idCache = array( 0 => null );

	function register() {
		$lib = array(
			'newTitle' => array( $this, 'newTitle' ),
			'makeTitle' => array( $this, 'makeTitle' ),
			'getExpensiveData' => array( $this, 'getExpensiveData' ),
			'getUrl' => array( $this, 'getUrl' ),
			'getContent' => array( $this, 'getContent' ),
			'getFileInfo' => array( $this, 'getFileInfo' ),
			'protectionLevels' => array( $this, 'protectionLevels' ),
			'cascadingProtection' => array( $this, 'cascadingProtection' ),
		);
		return $this->getEngine()->registerInterface( 'mw.title.lua', $lib, array(
			'thisTitle' => $this->getInexpensiveTitleData( $this->getTitle() ),
			'NS_MEDIA' => NS_MEDIA,
		) );
	}

	private function checkNamespace( $name, $argIdx, &$arg, $default = null ) {
		global $wgContLang;

		if ( $arg === null && $default !== null ) {
			$arg = $default;
		} elseif ( is_numeric( $arg ) ) {
			$arg = (int)$arg;
			if ( !MWNamespace::exists( $arg ) ) {
				throw new Scribunto_LuaError(
					"bad argument #$argIdx to '$name' (unrecognized namespace number '$arg')"
				);
			}
		} elseif ( is_string( $arg ) ) {
			$ns = $wgContLang->getNsIndex( $arg );
			if ( $ns === false ) {
				throw new Scribunto_LuaError(
					"bad argument #$argIdx to '$name' (unrecognized namespace name '$arg')"
				);
			}
			$arg = $ns;
		} else {
			$this->checkType( $name, $argIdx, $arg, 'namespace number or name' );
		}
	}

	/**
	 * Extract inexpensive information from a Title object for return to Lua
	 *
	 * @param $title Title Title to return
	 * @return array Lua data
	 */
	private function getInexpensiveTitleData( Title $title ) {
		$ns = $title->getNamespace();
		$ret = array(
			'isLocal' => (bool)$title->isLocal(),
			'interwiki' => $title->getInterwiki(),
			'namespace' => $ns,
			'nsText' => $title->getNsText(),
			'text' => $title->getText(),
			'fragment' => $title->getFragment(),
			'thePartialUrl' => $title->getPartialURL(),
		);
		if ( $ns === NS_SPECIAL ) {
			// Core doesn't currently record special page links, but it may in the future.
			if ( $this->getParser() && !$title->equals( $this->getTitle() ) ) {
				$this->getParser()->getOutput()->addLink( $title );
			}
			$ret['exists'] = (bool)SpecialPageFactory::exists( $title->getDBkey() );
		}
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			$ret['file'] = false;
		}
		return $ret;
	}

	/**
	 * Extract expensive information from a Title object for return to Lua
	 *
	 * This records a link to this title in the current ParserOutput and caches the
	 * title for repeated lookups. It may call incrementExpensiveFunctionCount() if
	 * the title is not already cached.
	 *
	 * @param string $text Title text
	 * @return array Lua data
	 */
	public function getExpensiveData( $text ) {
		$this->checkType( 'getExpensiveData', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( null );
		}
		$dbKey = $title->getPrefixedDBkey();
		if ( isset( $this->titleCache[$dbKey] ) ) {
			// It was already cached, so we already did the expensive work and added a link
			$title = $this->titleCache[$dbKey];
		} else {
			if ( !$title->equals( $this->getTitle() ) ) {
				$this->incrementExpensiveFunctionCount();

				// Record a link
				if ( $this->getParser() ) {
					$this->getParser()->getOutput()->addLink( $title );
				}
			}

			// Cache it
			$this->titleCache[$dbKey] = $title;
			if ( $title->getArticleID() > 0 ) {
				$this->idCache[$title->getArticleID()] = $title;
			}
		}

		$ret = array(
			'isRedirect' => (bool)$title->isRedirect(),
			'id' => $title->getArticleID(),
			'contentModel' => $title->getContentModel(),
		);
		if ( $title->getNamespace() === NS_SPECIAL ) {
			$ret['exists'] = (bool)SpecialPageFactory::exists( $title->getDBkey() );
		} else {
			// bug 70495: don't just check whether the ID != 0
			$ret['exists'] = $title->exists();
		}
		return array( $ret );
	}

	/**
	 * Handler for title.new
	 *
	 * Calls Title::newFromID or Title::newFromTitle as appropriate for the
	 * arguments.
	 *
	 * @param $text_or_id string|int Title or page_id to fetch
	 * @param $defaultNamespace string|int Namespace name or number to use if $text_or_id doesn't override
	 * @return array Lua data
	 */
	function newTitle( $text_or_id, $defaultNamespace = null ) {
		$type = $this->getLuaType( $text_or_id );
		if ( $type === 'number' ) {
			if ( array_key_exists( $text_or_id, $this->idCache ) ) {
				$title = $this->idCache[$text_or_id];
			} else {
				$this->incrementExpensiveFunctionCount();
				$title = Title::newFromID( $text_or_id );
				$this->idCache[$text_or_id] = $title;

				// Record a link
				if ( $this->getParser() && $title && !$title->equals( $this->getTitle() ) ) {
					$this->getParser()->getOutput()->addLink( $title );
				}
			}
			if ( $title ) {
				$this->titleCache[$title->getPrefixedDBkey()] = $title;
			} else {
				return array( null );
			}
		} elseif ( $type === 'string' ) {
			$this->checkNamespace( 'title.new', 2, $defaultNamespace, NS_MAIN );

			// Note this just fills in the given fields, it doesn't fetch from
			// the page table.
			$title = Title::newFromText( $text_or_id, $defaultNamespace );
			if ( !$title ) {
				return array( null );
			}
		} else {
			// This will always fail
			$this->checkType( 'title.new', 1, $text_or_id, 'number or string' );
		}

		return array( $this->getInexpensiveTitleData( $title ) );
	}

	/**
	 * Handler for title.makeTitle
	 *
	 * Calls Title::makeTitleSafe.
	 *
	 * @param $ns string|int Namespace
	 * @param $text string Title text
	 * @param $fragment string URI fragment
	 * @param $interwiki string Interwiki code
	 * @return array Lua data
	 */
	function makeTitle( $ns, $text, $fragment = null, $interwiki = null ) {
		$this->checkNamespace( 'makeTitle', 1, $ns );
		$this->checkType( 'makeTitle', 2, $text, 'string' );
		$this->checkTypeOptional( 'makeTitle', 3, $fragment, 'string', '' );
		$this->checkTypeOptional( 'makeTitle', 4, $interwiki, 'string', '' );

		// Note this just fills in the given fields, it doesn't fetch from the
		// page table.
		$title = Title::makeTitleSafe( $ns, $text, $fragment, $interwiki );
		if ( !$title ) {
			return array( null );
		}

		return array( $this->getInexpensiveTitleData( $title ) );
	}

	// May call the following Title methods:
	// getFullUrl, getLocalUrl, getCanonicalUrl
	function getUrl( $text, $which, $query = null, $proto = null ) {
		static $protoMap = array(
			'http' => PROTO_HTTP,
			'https' => PROTO_HTTPS,
			'relative' => PROTO_RELATIVE,
			'canonical' => PROTO_CANONICAL,
		);

		$this->checkType( 'getUrl', 1, $text, 'string' );
		$this->checkType( 'getUrl', 2, $which, 'string' );
		if ( !in_array( $which, array( 'fullUrl', 'localUrl', 'canonicalUrl' ), true ) ) {
			$this->checkType( 'getUrl', 2, $which, "'fullUrl', 'localUrl', or 'canonicalUrl'" );
		}
		$func = "get" . ucfirst( $which );

		$args = array( $query, false );
		if ( !is_string( $query ) && !is_array( $query ) ) {
			$this->checkTypeOptional( $which, 1, $query, 'table or string', '' );
		}
		if ( $which === 'fullUrl' ) {
			$this->checkTypeOptional( $which, 2, $proto, 'string', 'relative' );
			if ( !isset( $protoMap[$proto] ) ) {
				$this->checkType( $which, 2, $proto, "'http', 'https', 'relative', or 'canonical'" );
			}
			$args[] = $protoMap[$proto];
		}

		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( null );
		}
		return array( call_user_func_array( array( $title, $func ), $args ) );
	}

	function getContent( $text ) {
		$this->checkType( 'getContent', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( null );
		}

		// Record in templatelinks, so edits cause the page to be refreshed
		$this->getParser()->getOutput()->addTemplate(
			$title, $title->getArticleID(), $title->getLatestRevID()
		);
		if ( $title->equals( $this->getTitle() ) ) {
			$this->getParser()->getOutput()->setFlag( 'vary-revision' );
		}

		if ( is_callable( array( $this->getParser(), 'fetchCurrentRevisionOfTitle' ) ) ) {
			$rev = $this->getParser()->fetchCurrentRevisionOfTitle( $title );
		} else {
			$rev = Revision::newFromTitle( $title );
		}
		if ( !$rev ) {
			return array( null );
		}
		$content = $rev->getContent();
		if ( !$content ) {
			return array( null );
		}
		return array( $content->serialize() );
	}

	function getFileInfo( $text ) {
		$this->checkType( 'getFileInfo', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( false );
		}
		$ns = $title->getNamespace();
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			return array( false );
		}

		$this->incrementExpensiveFunctionCount();
		$file = wfFindFile( $title );
		if ( !$file ) {
			return array( array( 'exists' => false ) );
		}
		$this->getParser()->getOutput()->addImage(
			$file->getName(), $file->getTimestamp(), $file->getSha1()
		);
		if ( !$file->exists() ) {
			return array( array( 'exists' => false ) );
		}
		$pageCount = $file->pageCount();
		if ( $pageCount === false ) {
			$pages = null;
		} else {
			$pages = array();
			for ( $i = 1; $i <= $pageCount; ++$i ) {
				$pages[$i] = array(
					'width' => $file->getWidth( $i ),
					'height' => $file->getHeight( $i )
				);
			}
		}
		return array( array(
			'exists' => true,
			'width' => $file->getWidth(),
			'height' => $file->getHeight(),
			'mimeType' => $file->getMimeType(),
			'size' => $file->getSize(),
			'pages' => $pages
		) );
	}

	private static function makeArrayOneBased( $arr ) {
		if ( empty( $arr ) ) {
			return $arr;
		}
		return array_combine( range( 1, count( $arr ) ), array_values( $arr ) );
	}

	public function protectionLevels( $text ) {
		$this->checkType( 'protectionLevels', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( null );
		}

		if ( !$title->areRestrictionsLoaded() ) {
			$this->incrementExpensiveFunctionCount();
		}
		return array( array_map( 'Scribunto_LuaTitleLibrary::makeArrayOneBased', $title->getAllRestrictions() ) );
	}

	public function cascadingProtection( $text ) {
		$this->checkType( 'cascadingProtection', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return array( null );
		}

		if ( !$title->areCascadeProtectionSourcesLoaded() ) {
			$this->incrementExpensiveFunctionCount();
		}
		list( $sources, $restrictions ) = $title->getCascadeProtectionSources();
		return array( array(
			'sources' => Scribunto_LuaTitleLibrary::makeArrayOneBased( array_map(
				function ( $t ) {
					return $t->getPrefixedText();
				},
				$sources ) ),
			'restrictions' => array_map( 'Scribunto_LuaTitleLibrary::makeArrayOneBased', $restrictions )
		) );
	}
}
