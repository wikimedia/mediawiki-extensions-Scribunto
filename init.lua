package.path = package.path .. ";?;?.lua;.\\lualib\\?.lua;./lualib/?.lua;.\\modules\\?.lua;./modules/?.lua"
mw = require("mw")
-- All loaded functions are registered in the mw table in order to track them.
-- mwInit = require('mwInit') - does not lead to any changes in the mw table, apparently it should be deleted
bit = require("luabit/bit"); mw.bit = bit
hex = require("luabit/hex"); mw.hex = hex
mw.libraryUtil = libraryUtil

-- package = require('package')

--[[    
        TODO
            * alter package.searchers so that require('Module:yesno') == require('yesno')
            * Module:String, Module:Arguments, Module:Math
        HOW TO USE:
for now you need to put your module and other modules it uses 
in "\modules\.." folder and then alter their code (for example comment a line 
"yesno = require('Module:Yesno')" in your module) and instead write here:]]
yesno = require('yesno')

-- you can define some dull function instead of copying module code:
getArgs = function  (input)
    -- null operation - parameters are discarded
    return input
end

test = require('test')

test.run()
