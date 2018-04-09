<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Scribunto' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Scribunto'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['ScribuntoMagic'] = __DIR__ . '/Scribunto.magic.php';
	$wgExtensionMessagesFiles['ScribuntoNamespaces'] = __DIR__ . '/Scribunto.namespaces.php';
	/* wfWarn(
		'Deprecated PHP entry point used for Scribunto extension. Please use wfLoadExtension instead,' .
		' see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return true;
} else {
	die( 'This version of the Scribunto extension requires MediaWiki 1.27+' );
}

/**
 * The rest of this file is a PHP stub for providing documentation
 * about the various configuration settings for Scribunto, as well
 * as providing hints for IDEs. It is not executed by MediaWiki.
 */

define( 'NS_MODULE', 828 );
define( 'NS_MODULE_TALK', 829 );

/**
 * The name of the default script engine.
 */
$wgScribuntoDefaultEngine = 'luaautodetect';

/**
 * Configuration for each script engine
 */
$wgScribuntoEngineConf = [
	'luasandbox' => [
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
		// $wgLocalisationCacheConf['manualRecache'] = false
		// then memory usage is perhaps 10x smaller.
		'maxLangCacheSize' => 30,
	],
	'luastandalone' => [
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
	],
	'luaautodetect' => [
		'factory' => 'Scribunto_LuaEngine::newAutodetectEngine',
	],
];

/**
 * Set to true to enable the SyntaxHighlight_GeSHi extension
 */
$wgScribuntoUseGeSHi = true;

/**
 * Set to true to enable the CodeEditor extension
 */
$wgScribuntoUseCodeEditor = true;

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
