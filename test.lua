local p = {}

-- in VSC actboy168.lua-debug io.write will appear in "DEBUG CONSOLE"
local output = function(...)
    io.write(mw.allToString("\n",...))
end

local function mw_logObject (t)
    mw.logObject( t )
    output(mw.getLogBuffer())
    mw.clearLogBuffer()
end

-- run some tests (without verification of results
-- need tables with input and output values for this)
p.run = function ()
    -- output(mw_logObject(mw))
    -- mw.bit
    output("bit: bnot 18 =",bit.bnot(18))
    output("bit: 18 band 1 =",bit.band(18,1))
    output("bit: 18 bor 1 =",bit.bor(18,1))
    output("bit: 18 bxor 1 =",bit.bxor(18,1))
    output("bit: 18 bxor2 1 =",bit.bxor2(18,1))
    output("bit: 18 brshift 1 bit =",bit.brshift(18,1))
    output("bit: 18 blshift 1 bit =",bit.blshift(18,1))
    output("bit: 18 blogic_rshift 1 bit =",bit.blogic_rshift(18,1))
    local bit1 = bit.tobits(18)
    output("bit: tobits 1 =",unpack(bit1))
    output("bit: tonumb 1 =",bit.tonumb(bit1))
    -- modules
    output("yesno module: 0, 1 =",yesno(0),yesno(1))

end

return p
