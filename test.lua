local p = {}

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

-- run some tests (without verification of results
-- need tables with input and output values for this)
p.run = function ()
    output("yesno module:",yesno(0),yesno(1))
    output(mw_logObject(mw))
end

return p
