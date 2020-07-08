# partial-MediaWiki-lua-environment
Contains a stripped-down lua part of MediaWiki code for developing and debugging Scribunto modules in a friendly environment.                Part of MediaWiki extension Scribunto (please see Gerrit https://www.mediawiki.org/wiki/Developer_access for contributing).

# content
```lua
mw {
  ["allToString"] = function(...)
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
  ["clearLogBuffer"] = function()
  ["clone"] = function(val_tbl)
  ["dumpObject"] = function(object)
  ["getLogBuffer"] = function()
--["hash"] = PHP-based functions, don't work in pure lua { 
--  ["hashValue"] = function(algo, value)
--  ["listAlgorithms"] = function() { "md2", "md4", "md5", "sha1", "sha224", "sha256", "sha384", "sha512/224", "sha512/256", "sha512", "sha3-224", "sha3-256", "sha3-384", "sha3-512", "ripemd128", "ripemd160", "ripemd256", "ripemd320", "whirlpool", "tiger128,3", "tiger160,3", "tiger192,3", "tiger128,4", "tiger160,4", "tiger192,4", "snefru", "snefru256", "gost", "gost-crypto", "adler32", "crc32", "crc32b", "fnv132", "fnv1a32", "fnv164", "fnv1a64", "joaat", "haval128,3", "haval160,3", "haval192,3", "haval224,3", "haval256,3", "haval128,4", "haval160,4", "haval192,4", "haval224,4", "haval256,4", "haval128,5", "haval160,5", "haval192,5", "haval224,5", "haval256,5",}
--  ["setupInterface"] = function()
  },
  ["hex"] = table#5 {
    ["to_dec"] = function(hex) -- convert a hex string(prefix with '0x' or '0X') to number
    ["to_hex"] = function(n) -- convert a number to a hex string
  },
--["html"] = table#6 {
--  ["create"] = function: #33,
--  ["setupInterface"] = function()
--},
  ["libraryUtil"] = table#7 {
    ["checkType"] = function( name, argIdx, arg, expectType, nilOk )
    ["checkTypeForIndex"] = function( index, value, expectType )
    ["checkTypeForNamedArg"] = function( name, argName, arg, expectType, nilOk )
    ["checkTypeMulti"] = function( name, argIdx, arg, expectTypes )
    ["makeCheckSelfFunction"] = function( libraryName, varName, selfObj, selfObjDesc )
  },
  ["loadData"] = function( module )
  ["log"] = function( ... )
  ["logObject"] = function( object, prefix )
  ["loglevel"] = 3,
  ["text"] = table#8 {
    ["decode"] = function: #43,
    ["encode"] = function: #44,
    ["gsplit"] = function: #45,
    ["listToText"] = function: #46,
    ["nowiki"] = function: #47,
    ["setupInterface"] = function: #48,
    ["split"] = function: #49,
    ["tag"] = function: #50,
    ["trim"] = function: #51,
    ["truncate"] = function: #52,
  },
  ["uri"] = table#9 {
    ["buildQueryString"] = function: #53,
    ["decode"] = function: #54,
    ["encode"] = function: #55,
    ["new"] = function: #56,
    ["parseQueryString"] = function: #57,
    ["setupInterface"] = function: #58,
    ["validate"] = function: #59,
  },
  ["ustring"] = table#10 {
    ["byte"] = function: #60,
    ["byteoffset"] = function: #61,
    ["char"] = function: #62,
    ["codepoint"] = function: #63,
    ["find"] = function: #64,
    ["format"] = function: #65,
    ["gcodepoint"] = function: #66,
    ["gmatch"] = function: #67,
    ["gsub"] = function: #68,
    ["isutf8"] = function: #69,
    ["len"] = function: #70,
    ["lower"] = function: #71,
    ["match"] = function: #72,
    ["maxPatternLength"] = inf,
    ["maxStringLength"] = inf,
    ["rep"] = function: #73,
    ["sub"] = function: #74,
    ["toNFC"] = function: #75,
    ["toNFD"] = function: #76,
    ["toNFKC"] = function: #77,
    ["toNFKD"] = function: #78,
    ["upper"] = function: #79,
  },
}
