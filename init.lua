package.path = package.path .. ";?;?.lua;.\\lualib\\?.lua;./lualib/?.lua;.\\modules\\?.lua;./modules/?.lua"
mw = require("mw")
-- mwInit = require('mwInit')
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

-- in VSC actboy168.lua-debug io.write will appear in "DEBUG CONSOLE"
local output = function(...)
    local string = {}
    for _,arg in pairs{...} do
        table.insert(string,tostring(arg))
    end
    table.insert(string,"\n")
    io.write(table.concat(string," "))
end

local function mw_logObject (t)
    mw.logObject( t )
    output(mw.getLogBuffer())
    mw.clearLogBuffer()
end

-- some tests
output("yesno module:",yesno(0),yesno(1))
output(mw_logObject(mw))


