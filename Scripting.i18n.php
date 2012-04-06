<?php
/**
 * Internationalisation file for extension Scribunto.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Victor Vasiliev
 */
$messages['en'] = array(
	'scribunto-desc' => 'Framework for embedding scripting languages into MediaWiki pages',
	'scribunto-codelocation' => 'in $1 at line $2',
	'scribunto-luasandbox-error' => 'Lua error: $2',
	'scribunto-common-toofewargs' => 'Lua error: Too few arguments to function $2',
	'scribunto-common-nosuchmodule' => 'Script error: No such module',
	'scribunto-luasandbox-noreturn' => 'Script error: The module did not return a value, it should return an export table.',
	'scribunto-luasandbox-toomanyreturns' => 'Script error: The module returned multiple values, it should return an export table.',
	'scribunto-luasandbox-notarrayreturn' => 'Script error: The module returned something other than a table, it should return an export table.',
	'scribunto-common-nofunction' => 'Script error: You must specify a function to call.',
	'scribunto-common-nosuchfunction' => 'Script error: The function you specified did not exist.',
);
