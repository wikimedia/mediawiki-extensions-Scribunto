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
	'scribunto-line' => 'at line $1',
	'scribunto-module-line' => 'in $1 at line $2',
	'scribunto-parser-error' => 'Script error',
	'scribunto-parser-dialog-title' => 'Script error',
	'scribunto-error-short' => 'Script error: $1',
	'scribunto-error-long' => 'Script errors:

$1',
	'scribunto-common-toofewargs' => 'Lua error: Too few arguments to function $2',
	'scribunto-common-nosuchmodule' => 'Script error: No such module',
	'scribunto-common-nofunction' => 'Script error: You must specify a function to call.',
	'scribunto-common-nosuchfunction' => 'Script error: The function you specified did not exist.',
	'scribunto-common-timeout' => 'The time allocated for running scripts has expired.',
	'scribunto-common-oom' => 'The amount of memory allowed for running scripts has been exceeded.',
	'scribunto-common-backtrace' => 'Backtrace:',
	'scribunto-lua-in-function' => 'in function "$1"',
	'scribunto-lua-in-main' => 'in main chunk',
	'scribunto-lua-in-function-at' => 'in anonymous function at $1:$2',
	'scribunto-lua-backtrace-line' => '$1: $2',
	'scribunto-lua-error-location' => 'Lua error $1: $2',
	'scribunto-lua-error' => 'Lua error: $2',
	'scribunto-lua-noreturn' => 'Script error: The module did not return a value, it should return an export table.',
	'scribunto-lua-notarrayreturn' => 'Script error: The module returned something other than a table, it should return an export table.',
	'scribunto-luastandalone-proc-error' => 'Lua error: cannot create process',
	'scribunto-luastandalone-decode-error' => 'Lua error: internal error: unable to decode message',
	'scribunto-luastandalone-write-error' => 'Lua error: internal error: error writing to pipe',
	'scribunto-luastandalone-read-error' => 'Lua error: internal error: error reading from pipe',
	'scribunto-luastandalone-gone' => 'Lua error: internal error: the interpreter has already exited',
	'scribunto-luastandalone-signal' => 'Lua error: internal error: the interpreter has terminated with signal "$2"',
	'scribunto-luastandalone-exited' => 'Lua error: internal error: the interpreter exited with status $2',
);
