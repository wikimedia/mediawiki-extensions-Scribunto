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
    io.write("mw")
    output("\nbit functions testing:")
    local a_int1, a_int2, a_bit = 18, 3, 1
    output( "bit: bnot" .. a_int1 .. " =",bit.bnot(18),
            "bit32: ",mw.bit32.bnot(a_int1))
    output( "bit: " .. a_int1 .. " band " .. a_int2 .. " =",bit.band(18,1),
            "bit32: ",mw.bit32.band(a_int1, a_int2))
    output( "bit: " .. a_int1 .. " bor  " .. a_int2 .. " =",bit.bor(18,1),
            "bit32: ",mw.bit32.bor(a_int1, a_int2))
    output( "bit: " .. a_int1 .. " bxor " .. a_int2 .. " =",bit.bxor(18,1),
            "bit32: ",mw.bit32.bxor(a_int1, a_int2))
    output( "bit: " .. a_int1 .. " bxor2 " .. a_int2 .. " =",bit.bxor2(18,1))
    output( "bit: " .. a_int1 .. " brshift " .. a_bit .. " =",bit.brshift(18,1),
            "bit32: ",mw.bit32.rshift(a_int1, a_bit))
    output( "bit: " .. a_int1 .. " bl_rshift " .. a_bit .. " =",bit.blogic_rshift(18,1), 
            "bit32: ",mw.bit32.arshift(a_int1, a_bit))
    output( "bit: " .. a_int1 .. " blshift " .. a_bit .. " =",bit.blshift(18,1),
            "bit32: ",mw.bit32.lshift(a_int1, a_bit))
    local bit1 = bit.tobits(a_int1)
    output("bit: tobits " .. a_int1 .. " =",unpack(bit1))
    output("bit: tonumb --//-- =",bit.tonumb(bit1))
    local n, v, field, width = 16, 1, 2, 3
    local x, disp = 16, 1
    output("bit32: lrotate" .. x .. ", " .. disp .. "=",mw.bit32.lrotate( x, disp ))
    output("bit32: rrotate" .. x .. ", " .. disp .. "=",mw.bit32.rrotate( x, disp ))
    output("bit32: extract" .. n .. "," .. field .. "," .. width .. "=",mw.bit32.extract( n, field, width ))
    output("bit32: replace" .. n .. "," .. v .. "," .. field .. "," .. width .. "=",mw.bit32.replace( n, v, field, width))
    output("\nmodules testing:")
    output("yesno module: 0, 1 =",yesno(0),yesno(1))

end

return p
