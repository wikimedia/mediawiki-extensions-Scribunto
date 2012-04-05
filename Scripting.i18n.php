<?php
/**
 * Internationalisation file for extension Scripting.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Victor Vasiliev
 */
$messages['en'] = array(
	'scripting-desc' => 'Framework for embedding scripting languages into MediaWiki pages',
	'scripting-codelocation' => 'in $1 at line $2',
	'scripting-luasandbox-error' => 'Lua error: $2',
	'scripting-common-toofewargs' => 'Lua error: Too few arguments to function $2',
	'scripting-common-nosuchmodule' => 'Script error: No such module',
	'scripting-luasandbox-noreturn' => 'Script error: The module did not return a value, it should return an export table.',
	'scripting-luasandbox-toomanyreturns' => 'Script error: The module returned multiple values, it should return an export table.',
	'scripting-luasandbox-notarrayreturn' => 'Script error: The module returned something other than a table, it should return an export table.',
	'scripting-common-nofunction' => 'Script error: You must specify a function to call.',
	'scripting-common-nosuchfunction' => 'Script error: The function you specified did not exist.',
);
