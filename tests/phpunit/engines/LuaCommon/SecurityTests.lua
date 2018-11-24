local testframework = require 'Module:TestFramework'

local function f(p1, p2, p3, p4, p5, p6, p7, p8, p9, p10,
            p11, p12, p13, p14, p15, p16, p17, p18, p19, p20,
            p21, p22, p23, p24, p25, p26, p27, p28, p29, p30,
            p31, p32, p33, p34, p35, p36, p37, p38, p39, p40,
            p41, p42, p43, p44, p45, p46, p48, p49, p50, ...)
   local a1, a2, a3, a4, a5, a6, a7, a8, a9, a10, a11, a12, a13, a14
   f(...)
end

-- Tests
local tests = {
	{ name = 'CVE-2014-5461', func = f,
	  args = { 17 },
	  expect = "stack overflow"
	}
}

return testframework.getTestProvider( tests )