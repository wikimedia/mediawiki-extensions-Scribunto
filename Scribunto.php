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

if( !defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['parserhook']['Scribunto'] = array(
	'path'           => __FILE__,
	'name'           => 'Scribunto',
	'author'         => array( 'Victor Vasiliev', 'Tim Starling' ),
	'descriptionmsg' => 'scribunto-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Scribunto',
);

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Scribunto'] = $dir . 'Scribunto.i18n.php';
$wgExtensionMessagesFiles['ScribuntoMagic'] = $dir . 'Scribunto.magic.php';
$wgExtensionMessagesFiles['ScribuntoNamespaces'] = $dir . 'Scribunto.namespaces.php';

$wgAutoloadClasses['ScribuntoEngineBase'] = $dir.'common/Base.php';
$wgAutoloadClasses['ScribuntoModuleBase'] = $dir.'common/Base.php';
$wgAutoloadClasses['ScribuntoHooks'] = $dir.'common/Hooks.php';
$wgAutoloadClasses['ScribuntoException'] = $dir.'common/Common.php';
$wgAutoloadClasses['Scribunto'] = $dir.'common/Common.php';
$wgAutoloadClasses['ApiScribuntoConsole'] = $dir.'common/ApiScribuntoConsole.php';

$wgHooks['ParserFirstCallInit'][] = 'ScribuntoHooks::setupParserHook';
$wgHooks['ParserLimitReport'][] = 'ScribuntoHooks::reportLimits';
$wgHooks['ParserClearState'][] = 'ScribuntoHooks::clearState';

$wgHooks['CanonicalNamespaces'][] = 'ScribuntoHooks::addCanonicalNamespaces';
$wgHooks['ArticleViewCustom'][] = 'ScribuntoHooks::handleScriptView';
$wgHooks['TitleIsWikitextPage'][] = 'ScribuntoHooks::isWikitextPage';
$wgHooks['CodeEditorGetPageLanguage'][] = 'ScribuntoHooks::getCodeLanguage';
$wgHooks['EditPageBeforeEditChecks'][] = 'ScribuntoHooks::beforeEditChecks';
$wgHooks['EditPageBeforeEditButtons'][] = 'ScribuntoHooks::beforeEditButtons';
$wgHooks['EditFilterMerged'][] = 'ScribuntoHooks::validateScript';

$wgHooks['UnitTestsList'][] = 'ScribuntoHooks::unitTestsList';
$wgParserTestFiles[] = $dir . 'tests/engines/LuaCommon/luaParserTests.txt';

$wgParserOutputHooks['ScribuntoError'] = 'ScribuntoHooks::parserOutputHook';

$sbtpl = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'Scribunto/modules',
);

$wgResourceModules['ext.scribunto'] = $sbtpl + array(
	'scripts' => 'ext.scribunto.js',
	'dependencies' => array( 'jquery.ui.dialog' ),
	'messages' => array(
		'scribunto-parser-dialog-title'
	),
);
$wgResourceModules['ext.scribunto.edit'] = $sbtpl + array(
	'scripts' => 'ext.scribunto.edit.js',
	'styles' => 'ext.scribunto.edit.css',
	'dependencies' => array( 'ext.scribunto', 'mediawiki.api' ),
	'messages' => array(
		'scribunto-console-title',
		'scribunto-console-intro',
		'scribunto-console-clear',
		'scribunto-console-cleared',
	),
);
$wgAPIModules['scribunto-console'] = 'ApiScribuntoConsole';

/***** Individual engines and their configurations *****/

$wgAutoloadClasses['Scribunto_LuaEngine'] = $dir.'engines/LuaCommon/LuaCommon.php';
$wgAutoloadClasses['Scribunto_LuaModule'] = $dir.'engines/LuaCommon/LuaCommon.php';
$wgAutoloadClasses['Scribunto_LuaInterpreter'] = $dir.'engines/LuaCommon/LuaInterpreter.php';
$wgAutoloadClasses['Scribunto_LuaSandboxEngine'] = $dir.'engines/LuaSandbox/Engine.php';
$wgAutoloadClasses['Scribunto_LuaStandaloneEngine'] = $dir.'engines/LuaStandalone/LuaStandaloneEngine.php';
$wgAutoloadClasses['Scribunto_LuaStandaloneInterpreter'] = $dir.'engines/LuaStandalone/LuaStandaloneEngine.php';


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

		// Set this to true to allow setfenv() and getfenv() in user code. 
		// Note that these functions have been removed in Lua 5.2. Scribunto 
		// does not yet support Lua 5.2, but we expect support will be 
		// implemented in the future, and there is no guarantee that a 
		// simulation of setfenv() and getfenv() will be provided.
		'allowEnvFuncs' => false,
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
	),
);

/**
 * Script namespace numbers.
 */
$wgScribuntoNamespaceNumbers = array(
	'Module' => 20,
	'Module_talk' => 21,
);

/**
 * Set to true to enable the SyntaxHighlight_GeSHi extension
 */
$wgScribuntoUseGeSHi = false;

/**
 * Set to true to enable the CodeEditor extension
 */
$wgScribuntoUseCodeEditor = false;

function efDefineScribuntoNamespace() {
	global $wgScribuntoNamespaceNumbers;
	define( 'NS_MODULE', $wgScribuntoNamespaceNumbers['Module'] );
	define( 'NS_MODULE_TALK', $wgScribuntoNamespaceNumbers['Module_talk'] );
}

$wgExtensionFunctions[] = 'efDefineScribuntoNamespace';
