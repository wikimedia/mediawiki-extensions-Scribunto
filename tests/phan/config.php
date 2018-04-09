<?php

$cfg = require __DIR__ . '/../../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'./vendor/mediawiki/lua-sandbox/stubs',
		'./../../extensions/SyntaxHighlight_GeSHi',
	]
);
$cfg['file_list'][] = './Scribunto.php';

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'./vendor/mediawiki/lua-sandbox/stubs',
		'./../../extensions/SyntaxHighlight_GeSHi',
	]
);
$cfg['suppress_issue_types'] = array_merge(
	$cfg['suppress_issue_types'],
	[
		// \Parser->scribunto_engine
		'PhanUndeclaredProperty',
		// \Scribunto_LuaInterpreter::callFunction()
		'PhanParamTooMany',
		// our version of phan doesn't support inheriting doc yet
		'PhanParamSignatureMismatch',
	]
);

return $cfg;
