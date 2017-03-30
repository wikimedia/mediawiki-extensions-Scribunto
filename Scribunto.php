<?php
/**
 * Wikitext scripting infrastructure for MediaWiki.
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

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['parserhook']['Scribunto'] = array(
	'path'           => __FILE__,
	'name'           => 'Scribunto',
	'author'         => array( 'Victor Vasiliev', 'Tim Starling', 'Brad Jorsch' ),
	'descriptionmsg' => 'scribunto-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Scribunto',
	'license-name'   => 'GPL-2.0+ AND MIT',
);

define( 'CONTENT_MODEL_SCRIBUNTO', 'Scribunto' );

$wgMessagesDirs['Scribunto'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['ScribuntoMagic'] = __DIR__ . '/Scribunto.magic.php';
$wgExtensionMessagesFiles['ScribuntoNamespaces'] = __DIR__ . '/Scribunto.namespaces.php';

$wgAutoloadClasses['ScribuntoEngineBase'] = __DIR__ . '/common/Base.php';
$wgAutoloadClasses['ScribuntoModuleBase'] = __DIR__ . '/common/Base.php';
$wgAutoloadClasses['ScribuntoHooks'] = __DIR__ . '/common/Hooks.php';
$wgAutoloadClasses['ScribuntoException'] = __DIR__ . '/common/Common.php';
$wgAutoloadClasses['Scribunto'] = __DIR__ . '/common/Common.php';
$wgAutoloadClasses['ApiScribuntoConsole'] = __DIR__ . '/common/ApiScribuntoConsole.php';
$wgAutoloadClasses['ScribuntoContentHandler'] = __DIR__ . '/common/ScribuntoContentHandler.php';
$wgAutoloadClasses['ScribuntoContent'] = __DIR__ . '/common/ScribuntoContent.php';
$wgAutoloadClasses['Scribunto_LuaError'] = __DIR__ . '/engines/LuaCommon/LuaCommon.php';
$wgAutoloadClasses['Scribunto_LuaInterpreterNotFoundError'] =
	__DIR__ . '/engines/LuaCommon/LuaInterpreter.php';
$wgAutoloadClasses['Scribunto_LuaInterpreterBadVersionError'] =
	__DIR__ . '/engines/LuaCommon/LuaInterpreter.php';
$wgAutoloadClasses['Scribunto_LuaSandboxInterpreter'] = __DIR__ . '/engines/LuaSandbox/Engine.php';
$wgAutoloadClasses['Scribunto_LuaSandboxCallback'] = __DIR__ . '/engines/LuaSandbox/Engine.php';
$wgAutoloadClasses['Scribunto_LuaStandaloneInterpreterFunction'] =
	__DIR__ . '/engines/LuaStandalone/LuaStandaloneEngine.php';
$wgAutoloadClasses['Scribunto_LuaEngineTestSkip'] =
	__DIR__ . '/tests/engines/LuaCommon/LuaEngineTestBase.php';

$wgHooks['SoftwareInfo'][] = 'ScribuntoHooks::getSoftwareInfo';

$wgHooks['ParserFirstCallInit'][] = 'ScribuntoHooks::setupParserHook';
$wgHooks['ParserLimitReport']['scribunto'] = 'ScribuntoHooks::reportLimits';
$wgHooks['ParserLimitReportPrepare'][] = 'ScribuntoHooks::reportLimitData';
$wgHooks['ParserLimitReportFormat'][] = 'ScribuntoHooks::formatLimitData';
$wgHooks['ParserClearState'][] = 'ScribuntoHooks::clearState';
$wgHooks['ParserCloned'][] = 'ScribuntoHooks::parserCloned';

$wgHooks['CanonicalNamespaces'][] = 'ScribuntoHooks::addCanonicalNamespaces';
$wgHooks['CodeEditorGetPageLanguage'][] = 'ScribuntoHooks::getCodeLanguage';
$wgHooks['EditPage::showStandardInputs:options'][] = 'ScribuntoHooks::showStandardInputsOptions';
$wgHooks['EditPage::showReadOnlyForm:initial'][] = 'ScribuntoHooks::showReadOnlyFormInitial';
$wgHooks['EditPageBeforeEditButtons'][] = 'ScribuntoHooks::beforeEditButtons';
$wgHooks['EditFilterMergedContent'][] = 'ScribuntoHooks::validateScript';
$wgHooks['ArticleViewHeader'][] = 'ScribuntoHooks::showDocPageHeader';
$wgHooks['ContentHandlerDefaultModelFor'][] = 'ScribuntoHooks::contentHandlerDefaultModelFor';

$wgHooks['UnitTestsList'][] = 'ScribuntoHooks::unitTestsList';
$wgParserTestFiles[] = __DIR__ . '/tests/engines/LuaCommon/luaParserTests.txt';

$wgContentHandlers[CONTENT_MODEL_SCRIBUNTO] = 'ScribuntoContentHandler';

$sbtpl = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'Scribunto/modules',
);

$wgResourceModules['ext.scribunto.errors'] = $sbtpl + array(
	'scripts' => 'ext.scribunto.errors.js',
	'styles' => 'ext.scribunto.errors.css',
	'dependencies' => array( 'jquery.ui.dialog' ),
	'messages' => array(
		'scribunto-parser-dialog-title'
	),
);
$wgResourceModules['ext.scribunto.logs'] = $sbtpl + array(
	'styles' => 'ext.scribunto.logs.css',
	'position' => 'top',
);
$wgResourceModules['ext.scribunto.edit'] = $sbtpl + array(
	'scripts' => 'ext.scribunto.edit.js',
	'styles' => 'ext.scribunto.edit.css',
	'dependencies' => array( 'mediawiki.api', 'jquery.spinner' ),
	'messages' => array(
		'scribunto-console-title',
		'scribunto-console-intro',
		'scribunto-console-clear',
		'scribunto-console-cleared',
		'scribunto-console-cleared-session-lost',
	),
);
$wgAPIModules['scribunto-console'] = 'ApiScribuntoConsole';

/***** Individual engines and their configurations *****/

$wgAutoloadClasses['Scribunto_LuaEngine'] = __DIR__ . '/engines/LuaCommon/LuaCommon.php';
$wgAutoloadClasses['Scribunto_LuaModule'] = __DIR__ . '/engines/LuaCommon/LuaCommon.php';
$wgAutoloadClasses['Scribunto_LuaInterpreter'] = __DIR__ . '/engines/LuaCommon/LuaInterpreter.php';
$wgAutoloadClasses['Scribunto_LuaSandboxEngine'] = __DIR__ . '/engines/LuaSandbox/Engine.php';
$wgAutoloadClasses['Scribunto_LuaStandaloneEngine'] =
	__DIR__ . '/engines/LuaStandalone/LuaStandaloneEngine.php';
$wgAutoloadClasses['Scribunto_LuaStandaloneInterpreter'] =
	__DIR__ . '/engines/LuaStandalone/LuaStandaloneEngine.php';

/***** Individual libraries and their configurations *****/
$wgAutoloadClasses['Scribunto_LuaLibraryBase'] = __DIR__ . '/engines/LuaCommon/LibraryBase.php';
$wgAutoloadClasses['Scribunto_LuaEngineTestBase'] =
	__DIR__ . '/tests/engines/LuaCommon/LuaEngineTestBase.php';
$wgAutoloadClasses['Scribunto_LuaDataProvider'] =
	__DIR__ . '/tests/engines/LuaCommon/LuaDataProvider.php';
$wgAutoloadClasses['Scribunto_LuaSiteLibrary'] = __DIR__ . '/engines/LuaCommon/SiteLibrary.php';
$wgAutoloadClasses['Scribunto_LuaUriLibrary'] = __DIR__ . '/engines/LuaCommon/UriLibrary.php';
$wgAutoloadClasses['Scribunto_LuaUstringLibrary'] =
	__DIR__ . '/engines/LuaCommon/UstringLibrary.php';
$wgAutoloadClasses['Scribunto_LuaLanguageLibrary'] =
	__DIR__ . '/engines/LuaCommon/LanguageLibrary.php';
$wgAutoloadClasses['Scribunto_LuaMessageLibrary'] =
	__DIR__ . '/engines/LuaCommon/MessageLibrary.php';
$wgAutoloadClasses['Scribunto_LuaTitleLibrary'] = __DIR__ . '/engines/LuaCommon/TitleLibrary.php';
$wgAutoloadClasses['Scribunto_LuaTextLibrary'] = __DIR__ . '/engines/LuaCommon/TextLibrary.php';
$wgAutoloadClasses['Scribunto_LuaHtmlLibrary'] = __DIR__ . '/engines/LuaCommon/HtmlLibrary.php';
$wgAutoloadClasses['Scribunto_LuaHashLibrary'] = __DIR__ . '/engines/LuaCommon/HashLibrary.php';

/***** Configuration *****/

/**
 * The name of the default script engine.
 */
$wgScribuntoDefaultEngine = 'luastandalone';

/**
 * Configuration for each script engine
 */
$wgScribuntoEngineConf = array(
	'luasandbox' => array(
		'class' => 'Scribunto_LuaSandboxEngine',
		'memoryLimit' => 50 * 1024 * 1024,
		'cpuLimit' => 7,

		// The profiler sample period, or false to disable the profiler
		'profilerPeriod' => 0.02,

		// Set this to true to allow setfenv() and getfenv() in user code.
		// Note that these functions have been removed in Lua 5.2. Scribunto
		// does not yet support Lua 5.2, but we expect support will be
		// implemented in the future, and there is no guarantee that a
		// simulation of setfenv() and getfenv() will be provided.
		'allowEnvFuncs' => false,

		// The maximum number of languages about which data can be requested.
		// The cost is about 1.5MB of memory usage per language on default
		// installations (during recache), but if recaching is disabled with
		//     $wgLocalisationCacheConf['manualRecache'] = false
		// then memory usage is perhaps 10x smaller.
		'maxLangCacheSize' => 30,
	),
	'luastandalone' => array(
		'class' => 'Scribunto_LuaStandaloneEngine',

		// A filename to act as the destination for stderr from the Lua
		// binary. This may provide useful error information if Lua fails to
		// run. Set this to null to discard stderr output.
		'errorFile' => null,

		// The location of the Lua binary, or null to use the bundled binary.
		'luaPath' => null,
		'memoryLimit' => 50 * 1024 * 1024,
		'cpuLimit' => 7,
		'allowEnvFuncs' => false,
		'maxLangCacheSize' => 30,
	),
);

/**
 * Set to true to enable the SyntaxHighlight_GeSHi extension
 */
$wgScribuntoUseGeSHi = false;

/**
 * Set to true to enable the CodeEditor extension
 */
$wgScribuntoUseCodeEditor = false;

/**
 * Set to true to enable gathering and reporting of performance data
 * for slow function invocations.
 */
$wgScribuntoGatherFunctionStats = false;

/**
 * If $wgScribuntoGatherFunctionStats is true, this variable specifies
 * the percentile threshold for slow function invocations. Should be
 * a value between 0 and 1 (exclusive).
 */
$wgScribuntoSlowFunctionThreshold = 0.90;

define( 'NS_MODULE', 828 );
define( 'NS_MODULE_TALK', 829 );

// Set subpages by default
$wgNamespacesWithSubpages[NS_MODULE] = true;
$wgNamespacesWithSubpages[NS_MODULE_TALK] = true;

$wgTrackingCategories[] = 'scribunto-common-error-category';
$wgTrackingCategories[] = 'scribunto-module-with-errors-category';
