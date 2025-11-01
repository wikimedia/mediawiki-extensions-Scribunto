<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use LogicException;
use MediaWiki\Content\Content;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;

class TitleLibrary extends LibraryBase {
	// Note these caches are naturally limited to
	// 25 * $wgExpensiveParserFunctionLimit + 1 actual Title objects because any
	// addition besides the one for the current page calls
	// incrementExpensiveFunctionCount()
	/** @var Title[] */
	private $titleCache = [];
	/** @var (Title|null)[] */
	private $idCache = [ 0 => null ];

	/** @var TitleAttributeResolver[] */
	private array $attributeResolvers = [];

	/** @inheritDoc */
	public function register() {
		$lib = [
			'newTitle' => [ $this, 'newTitle' ],
			'newBatchLookupExistence' => [ $this, 'newBatchLookupExistence' ],
			'makeTitle' => [ $this, 'makeTitle' ],
			'getExpensiveData' => [ $this, 'getExpensiveData' ],
			'getUrl' => [ $this, 'getUrl' ],
			'getContent' => [ $this, 'getContent' ],
			'getCategories' => [ $this, 'getCategories' ],
			'getFileInfo' => [ $this, 'getFileInfo' ],
			'getFileMetadata' => [ $this, 'getFileMetadata' ],
			'protectionLevels' => [ $this, 'protectionLevels' ],
			'cascadingProtection' => [ $this, 'cascadingProtection' ],
			'redirectTarget' => [ $this, 'redirectTarget' ],
			'recordVaryFlag' => [ $this, 'recordVaryFlag' ],
			'getPageLangCode' => [ $this, 'getPageLangCode' ],
			'getAttributeValue' => [ $this, 'getAttributeValue' ],
		];
		$title = $this->getTitle();

		$extensionRegistry = ExtensionRegistry::getInstance();
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$extraTitleAttributes = $extensionRegistry->getAttribute( 'ScribuntoLuaExtraTitleAttributes' );
		foreach ( $extraTitleAttributes as $key => $value ) {
			$resolver = $objectFactory->createObject( $value, [ 'assertClass' => TitleAttributeResolver::class ] );
			$resolver->setEngine( $this->getEngine() );
			$this->attributeResolvers[$key] = $resolver;
		}

		return $this->getEngine()->registerInterface( 'mw.title.lua', $lib, [
			'thisTitle' => $title ? $this->getInexpensiveTitleData( $title ) : null,
			'NS_MEDIA' => NS_MEDIA,
		] );
	}

	/**
	 * Check a namespace parameter
	 * @param string $name Function name (for errors)
	 * @param int $argIdx Argument index (for errors)
	 * @param mixed &$arg Argument
	 * @param int|null $default Default value, if $arg is null
	 */
	private function checkNamespace( $name, $argIdx, &$arg, $default = null ) {
		if ( $arg === null && $default !== null ) {
			$arg = $default;
		} elseif ( is_numeric( $arg ) ) {
			$arg = (int)$arg;
			if ( !MediaWikiServices::getInstance()->getNamespaceInfo()->exists( $arg ) ) {
				throw new LuaError(
					"bad argument #$argIdx to '$name' (unrecognized namespace number '$arg')"
				);
			}
		} elseif ( is_string( $arg ) ) {
			$ns = MediaWikiServices::getInstance()->getContentLanguage()->getNsIndex( $arg );
			if ( $ns === false ) {
				throw new LuaError(
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
	 * @param Title $title Title to return
	 * @return array Lua data
	 */
	private function getInexpensiveTitleData( Title $title ) {
		$ns = $title->getNamespace();
		$ret = [
			'isCurrentTitle' => (bool)$title->equals( $this->getTitle() ),
			'isLocal' => (bool)$title->isLocal(),
			'interwiki' => $title->getInterwiki(),
			'namespace' => $ns,
			'nsText' => $title->getNsText(),
			'text' => $title->getText(),
			'fragment' => $title->getFragment(),
			'thePartialUrl' => $title->getPartialURL(),
		];
		if ( $ns === NS_SPECIAL ) {
			$ret['exists'] = MediaWikiServices::getInstance()
				->getSpecialPageFactory()->exists( $title->getDBkey() );
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
	 * @internal
	 * @param string $text Title text
	 * @return array Lua data
	 */
	public function getExpensiveData( $text ) {
		$this->checkType( 'getExpensiveData', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
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
					$this->getParser()->getOutput()->addExistenceDependency( $title );
				}
			}

			// Cache it
			$this->titleCache[$dbKey] = $title;
			if ( $title->getArticleID() > 0 ) {
				$this->idCache[$title->getArticleID()] = $title;
			}
		}

		return [ $this->getExpensiveDataForTitle( $title ) ];
	}

	/**
	 * Fetch various expensive properties of a Title
	 *
	 * @param Title $title
	 * @return array The expensive data for the title
	 */
	private function getExpensiveDataForTitle( Title $title ) {
		$ret = [
			'isRedirect' => (bool)$title->isRedirect(),
			'id' => $title->getArticleID(),
			'contentModel' => $title->getContentModel(),
		];
		if ( $title->getNamespace() === NS_SPECIAL ) {
			$ret['exists'] = MediaWikiServices::getInstance()
				->getSpecialPageFactory()->exists( $title->getDBkey() );
		} else {
			// bug 70495: don't just check whether the ID != 0
			$ret['exists'] = $title->exists();
		}
		return $ret;
	}

	/**
	 * Handler for mw.title.newBatch(list):lookupExistence():getTitles()
	 *
	 * This allows batching of lookups of expensive title properties.
	 *
	 * @internal
	 * @param array $list Table of strings to turn into titles (1-indexed)
	 * @param mixed $defaultNamespace
	 * @return array
	 */
	public function newBatchLookupExistence( $list, $defaultNamespace = null ) {
		$this->checkType( 'mw.title.newBatch', 1, $list, 'table' );
		$this->checkNamespace( 'mw.title.newBatch', 2, $defaultNamespace, NS_MAIN );
		$returnValue = [];
		// array of prefixedDbKey -> what indexes to put it in returned table
		$returnMapping = [];
		$expensiveCount = 0;
		$lb = MediaWikiServices::getInstance()->getLinkBatchFactory()->newLinkBatch();
		$lb->setCaller( __METHOD__ );
		for ( $i = 1; $i < count( $list ) + 1; $i++ ) {
			if ( !isset( $list[$i] ) ) {
				throw new LuaError( 'First argument to mw.title.newBatch must be contiguous table' );
			}
			// For now we only support string titles. Perhaps a future version
			// will allow batching numeric title id lookups.
			$this->checkType( 'mw.title.newBatch title list', $i, $list[$i], 'string' );
			$curTitle = Title::newFromText( $list[$i], $defaultNamespace );
			if ( !$curTitle ) {
				// Consistency with mw.title.new()
				$returnValue[$i] = null;
				continue;
			}
			if ( !$curTitle->canExist() ) {
				// For titles that don't represent a normal page,
				// treat it just like mw.title.new().
				// Importantly, this means that NS_MEDIA won't be preloaded.
				// .exists on an NS_MEDIA title is actually .file.exists
				// which will still trigger an expensive parser function
				// increment if the user accesses it.
				$returnValue[$i] = $this->getInexpensiveTitleData( $curTitle );
				continue;
			}
			$key = $curTitle->getPrefixedDBkey();
			if ( isset( $this->titleCache[$key] ) ) {
				$returnValue[$i] = $this->getInexpensiveTitleData( $curTitle ) +
					$this->getExpensiveDataForTitle( $curTitle );
				continue;
			}
			// We actually have to do the lookup

			// We always increment at least once if we have to lookup
			// a title. Increment an additional time for every 25 titles.
			if ( $expensiveCount % 25 === 0 ) {
				$this->incrementExpensiveFunctionCount();
			}
			$expensiveCount++;

			// Keep track of what index we should return this title in.
			$returnMapping[$key][] = $i;

			$lb->addObj( $curTitle );
		}

		$lb->execute();

		foreach ( $returnMapping as $key => $placesToInsert ) {
			$curTitle = Title::newFromText( $key );
			// Cache the title so subsequent lookups don't trigger expensive
			// parser function count to be incremented.
			$this->titleCache[$curTitle->getPrefixedDBKey()] = $curTitle;
			if ( $curTitle->getArticleId() ) {
				$this->idCache[$curTitle->getArticleID()] = $curTitle;
			}

			foreach ( $placesToInsert as $place ) {
				$returnValue[$place] = $this->getInexpensiveTitleData( $curTitle ) +
					$this->getExpensiveDataForTitle( $curTitle );
			}

			// Record a link since this prefills existence.
			// Important we do this after the linkBatch executes.
			if ( $this->getParser() && !$curTitle->equals( $this->getTitle() ) ) {
				$this->getParser()->getOutput()->addExistenceDependency( $curTitle );
			}
		}
		return [ $returnValue ];
	}

	/**
	 * Handler for title.new
	 *
	 * Calls Title::newFromID or Title::newFromTitle as appropriate for the
	 * arguments.
	 *
	 * @internal
	 * @param string|int $text_or_id Title or page_id to fetch
	 * @param string|int|null $defaultNamespace Namespace name or number to use if
	 *  $text_or_id doesn't override
	 * @return array Lua data
	 */
	public function newTitle( $text_or_id, $defaultNamespace = null ) {
		$type = $this->getLuaType( $text_or_id );
		if ( $type === 'number' ) {
			if ( array_key_exists( $text_or_id, $this->idCache ) ) {
				$title = $this->idCache[$text_or_id];
			} else {
				$this->incrementExpensiveFunctionCount();
				$title = Title::newFromID( $text_or_id );
				$this->idCache[$text_or_id] = $title;

				// Record a link
				if ( $title && $this->getParser() && !$title->equals( $this->getTitle() ) ) {
					$this->getParser()->getOutput()->addExistenceDependency( $title );
				}
			}
			if ( $title ) {
				$this->titleCache[$title->getPrefixedDBkey()] = $title;
			} else {
				return [ null ];
			}
		} elseif ( $type === 'string' ) {
			$this->checkNamespace( 'title.new', 2, $defaultNamespace, NS_MAIN );

			// Note this just fills in the given fields, it doesn't fetch from
			// the page table.
			$title = Title::newFromText( $text_or_id, $defaultNamespace );
			if ( !$title ) {
				return [ null ];
			}
		} else {
			$this->checkType( 'title.new', 1, $text_or_id, 'number or string' );
			throw new LogicException( 'checkType above should have failed' );
		}

		return [ $this->getInexpensiveTitleData( $title ) ];
	}

	/**
	 * Handler for title.makeTitle
	 *
	 * Calls Title::makeTitleSafe.
	 *
	 * @internal
	 * @param string|int $ns Namespace
	 * @param string $text Title text
	 * @param string|null $fragment URI fragment
	 * @param string|null $interwiki Interwiki code
	 * @return array Lua data
	 */
	public function makeTitle( $ns, $text, $fragment = null, $interwiki = null ) {
		$this->checkNamespace( 'makeTitle', 1, $ns );
		$this->checkType( 'makeTitle', 2, $text, 'string' );
		$this->checkTypeOptional( 'makeTitle', 3, $fragment, 'string', '' );
		$this->checkTypeOptional( 'makeTitle', 4, $interwiki, 'string', '' );

		// Note this just fills in the given fields, it doesn't fetch from the
		// page table.
		$title = Title::makeTitleSafe( $ns, $text, $fragment, $interwiki );
		if ( !$title ) {
			return [ null ];
		}

		return [ $this->getInexpensiveTitleData( $title ) ];
	}

	/**
	 * Get a URL referring to this title
	 * @internal
	 * @param string $text Title text.
	 * @param string $which 'fullUrl', 'localUrl', or 'canonicalUrl'
	 * @param string|array|null $query Query string or query string data.
	 * @param string|null $proto 'http', 'https', 'relative', or 'canonical'
	 * @return array
	 */
	public function getUrl( $text, $which, $query = null, $proto = null ) {
		static $protoMap = [
			'http' => PROTO_HTTP,
			'https' => PROTO_HTTPS,
			'relative' => PROTO_RELATIVE,
			'canonical' => PROTO_CANONICAL,
		];

		$this->checkType( 'getUrl', 1, $text, 'string' );
		$this->checkType( 'getUrl', 2, $which, 'string' );
		if ( !in_array( $which, [ 'fullUrl', 'localUrl', 'canonicalUrl' ], true ) ) {
			$this->checkType( 'getUrl', 2, $which, "'fullUrl', 'localUrl', or 'canonicalUrl'" );
		}

		// May call the following Title methods:
		// getFullUrl, getLocalUrl, getCanonicalUrl
		$func = "get" . ucfirst( $which );

		$args = [ $query, false ];
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
			return [ null ];
		}
		return [ $title->$func( ...$args ) ];
	}

	/**
	 * Utility to get a Content object from a title
	 *
	 * The title is counted as a transclusion.
	 *
	 * @param string $text Title text
	 * @return Content|null The Content object of the title, null if missing
	 */
	private function getContentInternal( $text ) {
		$title = Title::newFromText( $text );
		if ( !$title || !$title->canExist() ) {
			return null;
		}

		if ( MediaWikiServices::getInstance()->getNamespaceInfo()->isNonincludable( $title->getNamespace() ) ) {
			return null;
		}

		$rev = $this->getParser()->fetchCurrentRevisionRecordOfTitle( $title );

		if ( $title->equals( $this->getTitle() ) ) {
			$parserOutput = $this->getParser()->getOutput();
			$parserOutput->setOutputFlag( ParserOutputFlags::VARY_REVISION_SHA1 );
			$parserOutput->setRevisionUsedSha1Base36( $rev ? $rev->getSha1() : '' );
			wfDebug( __METHOD__ . ": set vary-revision-sha1 for '$title'" );
		} else {
			// Record in templatelinks, so edits cause the page to be refreshed
			$this->getParser()->getOutput()->addTemplate(
				$title, $title->getArticleID(), $title->getLatestRevID()
			);
		}

		if ( !$rev ) {
			return null;
		}

		try {
			$content = $rev->getContent( SlotRecord::MAIN );
		} catch ( RevisionAccessException $ex ) {
			$logger = LoggerFactory::getInstance( 'Scribunto' );
			$logger->warning(
				__METHOD__ . ': Unable to transclude revision content',
				[ 'exception' => $ex ]
			);
			$content = null;
		}
		return $content;
	}

	/**
	 * Handler for getContent
	 * @internal
	 * @param string $text
	 * @return string[]|null[]
	 */
	public function getContent( $text ) {
		$this->checkType( 'getContent', 1, $text, 'string' );
		$content = $this->getContentInternal( $text );
		return [ $content ? $content->serialize() : null ];
	}

	/**
	 * @internal
	 * @param string $text
	 * @return string[][]
	 */
	public function getCategories( $text ) {
		$this->checkType( 'getCategories', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ [] ];
		}
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$this->incrementExpensiveFunctionCount();

		$parserOutput = $this->getParser()->getOutput();
		if ( $title->equals( $this->getTitle() ) ) {
			$parserOutput->setOutputFlag( ParserOutputFlags::VARY_REVISION );
		} else {
			// Record in templatelinks, so edits cause the page to be refreshed
			$parserOutput->addTemplate( $title, $title->getArticleID(), $title->getLatestRevID() );
		}

		$categoryTitles = $page->getCategories();
		$categoryNames = [];
		foreach ( $categoryTitles as $title ) {
			$categoryNames[] = $title->getText();
		}
		return [ self::makeArrayOneBased( $categoryNames ) ];
	}

	/**
	 * Handler for getFileInfo
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function getFileInfo( $text ) {
		$this->checkType( 'getFileInfo', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ false ];
		}
		$ns = $title->getNamespace();
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			return [ false ];
		}

		$this->incrementExpensiveFunctionCount();
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		if ( !$file ) {
			return [ [ 'exists' => false ] ];
		}
		$this->getParser()->getOutput()->addImage(
			$file->getName(), $file->getTimestamp(), $file->getSha1()
		);
		if ( !$file->exists() ) {
			return [ [ 'exists' => false ] ];
		}
		$pageCount = $file->pageCount();
		if ( $pageCount === false ) {
			$pages = null;
		} else {
			$pages = [];
			for ( $i = 1; $i <= $pageCount; ++$i ) {
				$pages[$i] = [
					'width' => $file->getWidth( $i ),
					'height' => $file->getHeight( $i )
				];
			}
		}
		return [ [
			'exists' => true,
			'width' => $file->getWidth(),
			'height' => $file->getHeight(),
			'mimeType' => $file->getMimeType(),
			'length' => $file->getLength(),
			'size' => $file->getSize(),
			'pages' => $pages
		] ];
	}

	/**
	 * Get Exif-style metadata for a file
	 *
	 * This uses $file->getCommonMetaArray not $file->getMetadataArray().
	 * getMetadataArray() is defined entirely by the handler, while getCommonMetaArray
	 * should use the same format for all handlers.
	 *
	 * Fetching metadata requires an additional DB request beyond just fetching the
	 * file so increment the expensive function counter again.
	 *
	 * @param string $text File name to lookup
	 * @return array
	 */
	public function getFileMetadata( $text ) {
		// Redo these checks just in case, but we should never be able
		// to get here if any of them are false except for race conditions.
		$this->checkType( 'getFileMetadata', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ [] ];
		}
		$ns = $title->getNamespace();
		if ( $ns !== NS_FILE && $ns !== NS_MEDIA ) {
			return [ [] ];
		}

		$this->incrementExpensiveFunctionCount();
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		if ( !$file ) {
			return [ [] ];
		}
		return [ $this->normalizeMetadata( $file->getCommonMetaArray() ) ];
	}

	/**
	 * Normalize metadata array (Change to 1-based indexing)
	 *
	 * MediaWiki distinguishes between handler does not support metadata and
	 * a particular image just doesn't have metadata, but we are going to return
	 * an empty array in both cases.
	 *
	 * The general format of the metadata array is:
	 * [
	 *   'field name' => 'simple value',
	 *   'field2' => [ 'item1', 'item2', '_type' => 'ul' ],
	 *   'lang field' => [ 'en' => 'English text', 'de' => 'German text', '_type' => 'x-default' ]
	 * ]
	 * In the UI metadata field names are translated with i18n messages, but we just have keys.
	 *
	 * @param bool|array $arr Associative array or false if media type doesn't support
	 * @return array One based array.
	 */
	private function normalizeMetadata( $arr ) {
		if ( $arr === false ) {
			return [];
		}
		// The metadata array contains string keys
		// The values can be either a string or an array.
		// If the value is an array it can contain both numeric
		// and string keys.
		foreach ( $arr as &$entry ) {
			if ( is_array( $entry ) ) {
				// Note: We cannot use makeArrayOneBased as it
				// modifies string keys.
				array_unshift( $entry, null );
				unset( $entry[0] );
			}
		}
		return $arr;
	}

	/**
	 * Handler for getAttributeValue
	 * @internal
	 * @param string $text
	 * @param string $attribute
	 * @return array
	 */
	public function getAttributeValue( $text, $attribute ) {
		$this->checkType( 'getAttributeValue', 1, $text, 'string' );
		$this->checkType( 'getAttributeValue', 2, $attribute, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}
		if ( isset( $this->attributeResolvers[$attribute] ) ) {
			return [ $this->attributeResolvers[$attribute]->resolve( $title ) ];
		}
		return [ null ];
	}

	/**
	 * Renumber an array for return to Lua
	 * @param array $arr
	 * @return array
	 */
	private static function makeArrayOneBased( $arr ) {
		if ( !$arr ) {
			return $arr;
		}
		return array_combine( range( 1, count( $arr ) ), array_values( $arr ) );
	}

	/**
	 * Handler for protectionLevels
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function protectionLevels( $text ) {
		$this->checkType( 'protectionLevels', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();

		if ( !$restrictionStore->areRestrictionsLoaded( $title ) ) {
			$this->incrementExpensiveFunctionCount();
		}
		return [ array_map(
			[ self::class, 'makeArrayOneBased' ],
			$restrictionStore->getAllRestrictions( $title )
		) ];
	}

	/**
	 * Handler for cascadingProtection
	 * @internal
	 * @param string $text
	 * @return array
	 */
	public function cascadingProtection( $text ) {
		$this->checkType( 'cascadingProtection', 1, $text, 'string' );
		$title = Title::newFromText( $text );
		if ( !$title ) {
			return [ null ];
		}

		$restrictionStore = MediaWikiServices::getInstance()->getRestrictionStore();
		$titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();

		if ( !$restrictionStore->areCascadeProtectionSourcesLoaded( $title ) ) {
			$this->incrementExpensiveFunctionCount();
		}

		[ $sources, $restrictions ] = $restrictionStore->getCascadeProtectionSources( $title );

		return [ [
			'sources' => self::makeArrayOneBased( array_map(
				static function ( $t ) use ( $titleFormatter ) {
					return $titleFormatter->getPrefixedText( $t );
				},
				$sources ) ),
			'restrictions' => array_map(
				[ self::class, 'makeArrayOneBased' ],
				$restrictions
			)
		] ];
	}

	/**
	 * Handler for redirectTarget
	 * @internal
	 * @param string $text
	 * @return string[]|null[]
	 */
	public function redirectTarget( $text ) {
		$this->checkType( 'redirectTarget', 1, $text, 'string' );
		$content = $this->getContentInternal( $text );
		$redirTitle = $content ? $content->getRedirectTarget() : null;
		return [ $redirTitle ? $this->getInexpensiveTitleData( $redirTitle ) : null ];
	}

	/**
	 * Record a ParserOutput flag when the current title is accessed
	 * @internal
	 * @param string $text
	 * @param string $flag
	 * @return array
	 */
	public function recordVaryFlag( $text, $flag ) {
		$this->checkType( 'recordVaryFlag', 1, $text, 'string' );
		$this->checkType( 'recordVaryFlag', 2, $flag, 'string' );
		$title = Title::newFromText( $text );
		if ( $title && $title->equals( $this->getTitle() ) ) {
			// XXX note that we don't check this against the values defined
			// in ParserOutputFlags
			$this->getParser()->getOutput()->setOutputFlag( $flag );
		}
		return [];
	}

	/**
	 * Handler for getPageLangCode
	 * @internal
	 * @param string $text Title text.
	 * @return array<?string>
	 */
	public function getPageLangCode( $text ) {
		$title = Title::newFromText( $text );
		if ( $title ) {
			// If the page language is coming from the page record, we've
			// probably accounted for the cost of reading the title from
			// the DB already. However, a PageContentLanguage hook handler
			// might get invoked here, and who knows how much that costs.
			// Be safe and increment here, even though this could over-count.
			$this->incrementExpensiveFunctionCount();
			return [ $title->getPageLanguage()->getCode() ];
		}
		return [ null ];
	}
}
