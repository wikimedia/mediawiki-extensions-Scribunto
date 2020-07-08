# partial-MediaWiki-lua-environment
Contains a stripped-down lua part of MediaWiki code for developing and debugging Scribunto modules in a friendly environment.                Part of MediaWiki extension Scribunto (please see Gerrit https://www.mediawiki.org/wiki/Developer_access for contributing).

# content
For further information see https://www.mediawiki.org/wiki/Extension:Scribunto/Lua_reference_manual#Scribunto_libraries
```lua
mw {
  ["allToString"] = function(...)
  ["clearLogBuffer"] = function()
  ["clone"] = function(val_tbl)
  ["dumpObject"] = function(object)
  ["getLogBuffer"] = function()
  ["loadData"] = function( module )
  ["log"] = function( ... )
  ["logObject"] = function( object, prefix )
  ["loglevel"] = 3,
  ["bit"] = {
    ["band"] = function(m, n) -- bitwise and (m & n)
    ["blogic_rshift"] = function(n, bits) -- logic right shift(zero fill >>>)
    ["blshift"] = function(n, bits) -- left shift (n << bits)
    ["bnot"] = function(n) -- bitwise not (~n)
    ["bor"] = function(m, n) -- bitwise or (m | n)
    ["brshift"] = function(n, bits) -- right shift (n >> bits)
    ["bxor"] = function(m, n) -- bitwise xor (m ^ n)
    ["bxor2"] = function(m, n)
    ["tobits"] = function(n) -- convert n into a bit table(which is a 1/0 sequence) high bits first
    ["tonumb"] = function(bit_tbl) -- convert a bit table into a number
  },
  ["bit32"] = {
    ["arshift"] = function(x, disp)
    ["band"] = function(...)
    ["bnot"] = function(x)
    ["bor"] = function(...)
    ["btest"] = function(...)
    ["bxor"] = function(...)
    ["extract"] = function(n, field, width)
    ["lrotate"] = function(x, disp)
    ["lshift"] = function(x, disp)
    ["replace"] = function(n, v, field, width)
    ["rrotate"] = function(x, disp)
    ["rshift"] = function(x, disp)
  },
--["hash"] = PHP-based functions, don't work in pure lua { 
--  ["hashValue"] = function(algo, value)
--  ["listAlgorithms"] = function() { "md2", "md4", "md5", "sha1", "sha224", "sha256", "sha384", "sha512/224", "sha512/256", "sha512", "sha3-224", "sha3-256", "sha3-384", "sha3-512", "ripemd128", "ripemd160", "ripemd256", "ripemd320", "whirlpool", "tiger128,3", "tiger160,3", "tiger192,3", "tiger128,4", "tiger160,4", "tiger192,4", "snefru", "snefru256", "gost", "gost-crypto", "adler32", "crc32", "crc32b", "fnv132", "fnv1a32", "fnv164", "fnv1a64", "joaat", "haval128,3", "haval160,3", "haval192,3", "haval224,3", "haval256,3", "haval128,4", "haval160,4", "haval192,4", "haval224,4", "haval256,4", "haval128,5", "haval160,5", "haval192,5", "haval224,5", "haval256,5",}
--  ["setupInterface"] = function()
  },
  ["hex"] = table#5 {
    ["to_dec"] = function(hex) -- convert a hex string(prefix with '0x' or '0X') to number
    ["to_hex"] = function(n) -- convert a number to a hex string
  },
  ["html"] = {
    ["create"] = function( tagName, args )
    ["setupInterface"] = function()
  -- Methods: mw.html:node, mw.html:wikitext, mw.html:newline, mw.html:tag, mw.html:attr, mw.html:getAttr, mw.html:addClass, mw.html:css, mw.html:cssText, mw.html:done, mw.html:allDone
  },
  ["libraryUtil"] = table#7 {
    ["checkType"] = function( name, argIdx, arg, expectType, nilOk )
    ["checkTypeForIndex"] = function( index, value, expectType )
    ["checkTypeForNamedArg"] = function( name, argName, arg, expectType, nilOk )
    ["checkTypeMulti"] = function( name, argIdx, arg, expectTypes )
    ["makeCheckSelfFunction"] = function( libraryName, varName, selfObj, selfObjDesc )
  },
  ["text"] = { -- mostly ustring-dependent functions
    ["decode"] = function( s )
    ["encode"] = function( s, charset ) 
    ["gsplit"] = function( text, pattern, plain )
    ["listToText"] = function( list, separator, conjunction )
    ["nowiki"] = function( s )
    ["setupInterface"] = function( opts ) -- for example: opts = {["comma"] = ", " -- separator,["and"] = " or " -- conjunction,["ellipsis"] = "...", ["nowiki_protocols"] = false}
    ["split"] = function( text, pattern, plain )
    ["tag"] = function( name, attrs, content )
    ["trim"] = function( s, charset )
    ["truncate"] = function( text, length, ellipsis, adjustLength )
--[[ disabled functions:
  -- mw.text.unstrip( s )
  -- mw.text.unstripNoWiki( s )
  -- mw.text.killMarkers( s )
  jsonDecode - jsonEncode (?)
--]]
  },
  ["uri"] = table#9 {
    ["buildQueryString"] = function( table ) --Encodes a table as a URI query string
    ["decode"] = function( s, enctype )
    ["encode"] = function( s, enctype )
    ["new"] = function( s )
    ["parseQueryString"] = function( s, i, j ) -- Decodes the query string s to a table.
    ["validate"] = function( table ) -- Validates the passed table (or URI object).
--Methods: uri:parse( s ), mw.uri:clone(), uri:extend( parameters )
--[[ disabled functions:
  -- mw.uri.anchorEncode( s )
  -- mw.uri.canonicalUrl( page, query )
  -- mw.uri.fullUrl( page, query )
  -- mw.uri.localUrl( page, query )
  -- 
--]]
  },
  ["ustring"] = table#10 {
    ["byte"] = function( s, i, j )
    ["byteoffset"] = function( s, l, i )
    ["char"] = function( ... )
    ["codepoint"] = function( s, i, j )
    ["find"] = function( s, pattern, init, plain )
    ["format"] = function( format, ... )
    ["gcodepoint"] = function( s, i, j )
    ["gmatch"] = function( s, pattern )
    ["gsub"] = function( s, pattern, repl, n )
    ["isutf8"] = function( s )
    ["len"] = function( s )
    ["lower"] = function( s )
    ["match"] = function( s, pattern, init )
    ["maxPatternLength"] = inf,
    ["maxStringLength"] = inf,
    ["rep"] = function( s, n )
    ["sub"] = function( s, i, j )
    ["toNFC"] = function( s )
    ["toNFD"] = function( s )
    ["toNFKC"] = function( s )
    ["toNFKD"] = function( s )
    ["upper"] = function( s )
  },
--[[ disabled mw. functions:
  addWarning, getCurrentFrame, incrementExpensiveFunctionCount, isSubsting, 

  Frame object (frame.args can be treated as lua-table):
  frame:callParserFunction, frame:expandTemplate, frame:extensionTag, frame:getParent, frame:getTitle, frame:newChild, frame:preprocess, 
  frame:getArgument (see if it can be substed as dull function), frame:newParserValue, frame:newTemplateParserValue, frame:argumentPairs, 

  Language library  mw.language

  Message library   mw.message

  Site library      mw.site

  Title library     mw.title
--]]
}
