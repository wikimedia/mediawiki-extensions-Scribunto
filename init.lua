package.path = package.path .. ";?;?.lua;.\\lualib\\?.lua;./lualib/?.lua;.\\modules\\?.lua;./modules/?.lua"
local mw = require("mw")
--[[    TODO
            * alter package.searchers so that require('Module:yesno') == require('yesno')

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
local output = function(arg)
    io.write(tostring(arg) .. "\n")
end

output(yesno(1))
output(yesno(0))