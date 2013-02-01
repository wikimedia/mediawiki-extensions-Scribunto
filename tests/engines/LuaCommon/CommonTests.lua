local testframework = require 'Module:TestFramework'

local test = {}

function test.clone1()
	local x = 1
	local y = mw.clone( x )
	return ( x == y )
end

function test.clone2()
	local x = { 'a' }
	local y = mw.clone( x )
	assert( x ~= y )
	return testframework.deepEquals( x, y )
end

function test.clone2b()
	local x = { 'a' }
	local y = mw.clone( x )
	assert( x ~= y )
	y[2] = 'b'
	return testframework.deepEquals( x, y )
end

function test.clone3()
	local mt = { __add = function() end }
	local x = {}
	setmetatable( x, mt )
	local y = mw.clone( x )
	assert( getmetatable( x ) ~= getmetatable( y ) )
	return testframework.deepEquals( getmetatable( x ), getmetatable( y ) )
end

function test.clone4()
	local x = {}
	x.x = x
	local y = mw.clone( x )
	assert( x ~= y )
	return y == y.x
end

function test.setfenv1()
	setfenv( 0, {} )
end

function test.setfenv2()
	setfenv( 1000, {} )
end

function test.setfenv3()
	local function jailbreak()
		setfenv( 2, {} )
	end
	local new_setfenv, new_getfenv = mw.makeProtectedEnvFuncs( { [_G] = true }, {} )
	setfenv( jailbreak, {setfenv = new_setfenv} )
	jailbreak()
end

function test.setfenv4()
	-- Set an unprotected environment at a higher stack level than a protected
	-- environment. It's assumed that any higher-level environment will protect
	-- itself with its own setfenv wrapper, so this succeeds.
	local function level3()
		local function level2()
			local env = {setfenv = setfenv}
			local function level1()
				setfenv( 3, {} )
			end

			setfenv( level1, env )()
		end
		local protected = {mw = mw}
		protected.setfenv, protected.getfenv = mw.makeProtectedEnvFuncs(
			{[protected] = true}, {} )
		setfenv( level2, protected )()
	end
	local unprotected = {setfenv = setfenv, getfenv = getfenv, mw = mw}
	setfenv( level3, unprotected )()
	assert( getfenv( level3 ) ~= unprotected )
	return 'ok'
end

function test.setfenv5()
	local function allowed()
		(function() setfenv( 2, {} ) end )()
	end
	local new_setfenv, new_getfenv = mw.makeProtectedEnvFuncs( { [_G] = true }, {} )
	setfenv( allowed, {setfenv = new_setfenv} )()
	return 'ok'
end

function test.setfenv6()
	local function target() end
	local function jailbreak()
		setfenv( target, {} )
	end
	local new_setfenv, new_getfenv = mw.makeProtectedEnvFuncs( {}, { [target] = true } )
	setfenv( jailbreak, {setfenv = new_setfenv} )()
end

function test.setfenv7()
	setfenv( {}, {} )
end

function test.getfenv1()
	assert( getfenv( 1 ) == _G )
	return 'ok'
end

function test.getfenv2()
	getfenv( 0 )
end

function test.getfenv3()
	local function foo()
		return getfenv( 2 )
	end

	local function bar()
		return foo()
	end

	-- The "at level #" bit varies between environments, so
	-- catch the error and strip that part out
	local ok, err = pcall( bar )
	if not ok then
		err = string.gsub( err, '^%S+:%d+: ', '' )
		err = string.gsub( err, ' at level %d$', '' )
		error( err )
	end
end

function test.executeExpensiveCalls( n )
	for i = 1, n do
		mw.incrementExpensiveFunctionCount()
	end
	return 'Did not error out'
end

function test.stringMetatableHidden1()
	return getmetatable( "" )
end

function test.stringMetatableHidden2()
	string.foo = 42
	return ("").foo
end

local pairs_test_table = {}
setmetatable( pairs_test_table, {
	__pairs = function () return 1, 2, 3, 'ignore' end,
	__ipairs = function () return 4, 5, 6, 'ignore' end,
} )

function test.noLeaksViaPackageLoaded()
	assert( package.loaded.debug == debug, "package.loaded.debug ~= debug" )
	assert( package.loaded.string == string, "package.loaded.string ~= string" )
	assert( package.loaded.math == math, "package.loaded.math ~= math" )
	assert( package.loaded.io == io, "package.loaded.io ~= io" )
	assert( package.loaded.os == os, "package.loaded.os ~= os" )
	assert( package.loaded.table == table, "package.loaded.table ~= table" )
	assert( package.loaded._G == _G , "package.loaded._G ~= _G " )
	assert( package.loaded.coroutine == coroutine, "package.loaded.coroutine ~= coroutine" )
	assert( package.loaded.package == package, "package.loaded.package ~= package" )
	return 'ok'
end

return testframework.getTestProvider( {
	{ name = 'clone', func = test.clone1,
	  expect = { true },
	},
	{ name = 'clone table', func = test.clone2,
	  expect = { true },
	},
	{ name = 'clone table then modify', func = test.clone2b,
	  expect = { false, { 2 }, nil, 'b' },
	},
	{ name = 'clone table with metatable', func = test.clone3,
	  expect = { true },
	},
	{ name = 'clone recursive table', func = test.clone4,
	  expect = { true },
	},

	{ name = 'setfenv global', func = test.setfenv1,
	  expect = "'setfenv' cannot set the global environment, it is protected",
	},
	{ name = 'setfenv invalid level', func = test.setfenv2,
	  expect = "'setfenv' cannot set an environment at a level greater than 10",
	},
	{ name = 'setfenv invalid environment', func = test.setfenv3,
	  expect = "'setfenv' cannot set the requested environment, it is protected",
	},
	{ name = 'setfenv on unprotected past protected', func = test.setfenv4,
	  expect = { 'ok' },
	},
	{ name = 'setfenv from inside protected', func = test.setfenv5,
	  expect = { 'ok' },
	},
	{ name = 'setfenv protected function', func = test.setfenv6,
	  expect = "'setfenv' cannot be called on a protected function",
	},
	{ name = 'setfenv on a non-function', func = test.setfenv7,
	  expect = "'setfenv' can only be called with a function or integer as the first argument",
	},

	{ name = 'getfenv(1)', func = test.getfenv1,
	  expect = { 'ok' },
	},
	{ name = 'getfenv(0)', func = test.getfenv2,
	  expect = "'getfenv' cannot get the global environment",
	},
	{ name = 'getfenv with tail call', func = test.getfenv3,
	  expect = "no function environment for tail call",
	},

	{ name = 'Not quite too many expensive function calls',
	  func = test.executeExpensiveCalls, args = { 10 },
	  expect = { 'Did not error out' }
	},

	{ name = 'Too many expensive function calls',
	  func = test.executeExpensiveCalls, args = { 11 },
	  expect = 'too many expensive function calls'
	},

	{ name = 'string metatable is hidden', func = test.stringMetatableHidden1,
	  expect = { nil }
	},

	{ name = 'string is not string metatable', func = test.stringMetatableHidden2,
	  expect = { nil }
	},

	{ name = 'pairs with __pairs',
	  func = pairs, args = { pairs_test_table },
	  expect = { 1, 2, 3 },
	},

	{ name = 'ipairs with __ipairs',
	  func = ipairs, args = { pairs_test_table },
	  expect = { 4, 5, 6 },
	},

	{ name = 'package.loaded does not leak references to out-of-environment objects',
	  func = test.noLeaksViaPackageLoaded,
	  expect = { 'ok' },
	},
} )
