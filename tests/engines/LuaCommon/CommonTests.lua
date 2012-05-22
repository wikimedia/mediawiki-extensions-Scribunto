-- Parts are based on lua-TestMore (Copyright © 2009-2012 François Perrad, MIT
-- license)

local test = {}

local function is_deeply (got, expected, name)
    if type(got) ~= 'table' then
        return false
    elseif type(expected) ~= 'table' then
        error("expected value isn't a table : " .. tostring(expected))
    end
    local msg1
    local msg2

    local function deep_eq (t1, t2, key_path)
        if t1 == t2 then
            return true
        end
        for k, v2 in pairs(t2) do
            local v1 = t1[k]
            if type(v1) == 'table' and type(v2) == 'table' then
                local r = deep_eq(v1, v2, key_path .. "." .. tostring(k))
                if not r then
                    return false
                end
            else
                if v1 ~= v2 then
                    key_path = key_path .. "." .. tostring(k)
                    msg1 = "     got" .. key_path .. ": " .. tostring(v1)
                    msg2 = "expected" .. key_path .. ": " .. tostring(v2)
                    return false
                end
            end
        end
        for k in pairs(t1) do
            local v2 = t2[k]
            if v2 == nil then
                key_path = key_path .. "." .. tostring(k)
                msg1 = "     got" .. key_path .. ": " .. tostring(t1[k])
                msg2 = "expected" .. key_path .. ": " .. tostring(v2)
                return false
            end
        end
        return true
    end -- deep_eq

	return deep_eq(got, expected, '')
end

function test.getTests( engine )
	return {
		{ 'clone1', 'ok' },
		{ 'clone2', 'ok' },
		{ 'clone3', 'ok' },
		{ 'clone4', 'ok' },
		{ 'setfenv1', { error = '%s cannot set the global %s' } },
		{ 'setfenv2', { error = '%s cannot set an environment %s' } },
		{ 'setfenv3', { error = '%s cannot set the requested environment%s' } },
		{ 'setfenv4', 'ok' },
		{ 'setfenv5', 'ok' },
		{ 'setfenv6', { error = '%s cannot be called on a protected function' } },
		{ 'setfenv7', { error = '%s can only be called with a function%s' } },
		{ 'getfenv1', 'ok' },
		{ 'getfenv2', { error = '%s cannot get the global environment' } },
		{ 'getfenv3', { error = '%Sno function environment for tail call %s' } },
	}
end

function test.clone1()
	local x = 1
	local y = mw.clone( x )
	assert( x == y )
	return 'ok'
end

function test.clone2()
	local x = { 'a' }
	local y = mw.clone( x )
	assert( x ~= y )
	assert( is_deeply( y, x ) )
	y[2] = 'b'
	assert( not is_deeply( y, x ) )
	return 'ok'
end

function test.clone3()
	local mt = { __add = function() end }
	local x = {}
	setmetatable( x, mt )
	local y = mw.clone( x )
	assert( getmetatable( x ) ~= getmetatable( y ) )
	assert( is_deeply( getmetatable( y ), getmetatable( x ) ) )
	return 'ok'
end

function test.clone4()
	local x = {}
	x.x = x
	local y = mw.clone( x )
	assert( x ~= y )
	assert( y == y.x )
	return 'ok'
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

function test.setfenv8()
	setfenv( 2, {} )
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

	bar()
end

return test
