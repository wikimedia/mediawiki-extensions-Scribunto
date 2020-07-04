# partial-MediaWiki-lua-environment
Contains a stripped-down lua part of MediaWiki code for developing and debugging Scribunto modules in a friendly environment.                Part of MediaWiki extension Scribunto (please see Gerrit https://www.mediawiki.org/wiki/Developer_access for contributing).

# content
mw {
    ["allToString"] = function: 00000029219832D0#1,
    ["bit32"] = table#2 {
      ["arshift"] = function: 000000292195B400#2,
      ["band"] = function: 000000292195AC00#3,
      ["bnot"] = function: 000000292195B380#4,
      ["bor"] = function: 000000292195AC40#5,
      ["btest"] = function: 000000292195AD80#6,
      ["bxor"] = function: 000000292195B280#7,
      ["extract"] = function: 000000292195B0C0#8,
      ["lrotate"] = function: 0000002921975860#9,
      ["lshift"] = function: 000000292195B1C0#10,
      ["replace"] = function: 000000292195B100#11,
      ["rrotate"] = function: 0000002921975320#12,
      ["rshift"] = function: 000000292195B240#13,
    },
    ["clearLogBuffer"] = function: 000000292198A7D0#14,
    ["clone"] = function: 0000002921983840#15,
    ["dumpObject"] = function: 0000002921983150#16,
    ["getLogBuffer"] = function: 0000002921989D50#17,
    ["hash"] = table#3 {
      ["hashValue"] = function: 00000029219740E0#18,
      ["listAlgorithms"] = function: 0000002921973F20#19,
      ["setupInterface"] = function: 00000029219746A0#20,
    },
    ["html"] = table#4 {
      ["create"] = function: 00000029219753A0#21,
      ["setupInterface"] = function: 000000292194DA40#22,
    },
    ["loadData"] = function: 000000292198A750#23,
    ["log"] = function: 000000292198A090#24,
    ["logObject"] = function: 0000002921989ED0#25,
    ["loglevel"] = 3,
    ["makeProtectedEnvFuncs"] = function: 0000002921980750#26,
    ["text"] = table#5 {
      ["decode"] = function: 0000002921959D40#27,
      ["encode"] = function: 0000002921959D80#28,
      ["gsplit"] = function: 0000002921961D00#29,
      ["listToText"] = function: 000000292195AF80#30,
      ["nowiki"] = function: 000000292195C200#31,
      ["setupInterface"] = function: 0000002921959D00#32,
      ["split"] = function: 000000292195B500#33,
      ["tag"] = function: 000000292195C340#34,
      ["trim"] = function: 0000002921965A50#35,
      ["truncate"] = function: 000000292195AC80#36,
    },
    ["uri"] = table#6 {
      ["buildQueryString"] = function: 000000292198A210#37,
      ["decode"] = function: 000000292195AE00#38,
      ["encode"] = function: 0000002921984E10#39,
      ["new"] = function: 000000292195B540#40,
      ["parseQueryString"] = function: 00000029219854A0#41,
      ["setupInterface"] = function: 000000292195BA00#42,
      ["validate"] = function: 0000002921933B40#43,
    },
    ["ustring"] = table#7 {
      ["byte"] = function: 000000291F976860#44,
      ["byteoffset"] = function: 000000292194CD20#45,
      ["char"] = function: 0000002921933740#46,
      ["codepoint"] = function: 00000029219218F0#47,
      ["find"] = function: 00000029219580E0#48,
      ["format"] = function: 000000291F978290#49,
      ["gcodepoint"] = function: 0000002921921990#50,
      ["gmatch"] = function: 0000002921957CF0#51,
      ["gsub"] = function: 00000029219583F0#52,
      ["isutf8"] = function: 0000002921933640#53,
      ["len"] = function: 0000002921933780#54,
      ["lower"] = function: 0000002921932600#55,
      ["match"] = function: 00000029219585B0#56,
      ["maxPatternLength"] = inf,
      ["maxStringLength"] = inf,
      ["rep"] = function: 000000291F9787D0#57,
      ["sub"] = function: 00000029219219E0#58,
      ["toNFC"] = function: 000000292194C840#59,
      ["toNFD"] = function: 000000292194CCC0#60,
      ["toNFKC"] = function: 000000292194CD80#61,
      ["toNFKD"] = function: 000000292194C060#62,
      ["upper"] = function: 0000002921932E00#63,
    },
}

libraryUtil {
    ["checkType"] = function: 0000002921965960#53,
    ["checkTypeForIndex"] = function: 0000002921965990#54,
    ["checkTypeForNamedArg"] = function: 0000002921965A20#55,
    ["checkTypeMulti"] = function: 0000002921965930#56,
    ["makeCheckSelfFunction"] = function: 0000002921965840#57,
}

bit {
  ["band"] = function: 000000292195C390#1,
  ["blogic_rshift"] = function: 0000002921960090#2,
  ["blshift"] = function: 000000292195C6B0#3,
  ["bnot"] = function: 0000002921960910#4,
  ["bor"] = function: 000000292195C2F0#5,
  ["brshift"] = function: 000000292195C660#6,
  ["bxor"] = function: 000000292195C3E0#7,
  ["bxor2"] = function: 000000292195CA20#8,
  ["tobits"] = function: 000000292195FF10#9,
  ["tonumb"] = function: 0000002921980990#10,
}

hex {
  ["to_dec"] = function: 0000002921980ED0#1,
  ["to_hex"] = function: 0000002921960790#2,
}

