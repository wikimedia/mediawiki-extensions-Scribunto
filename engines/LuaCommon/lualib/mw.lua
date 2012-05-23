mw = mw or {}

local packageCache
local packageModuleFunc
local php
local setupDone
local allowEnvFuncs = false

--- Put an isolation-friendly package module into the specified environment 
-- table. The package module will have an empty cache, because caching of 
-- module functions from other cloned environments would break module isolation.
-- 
-- @param env The cloned environment
local function makePackageModule( env )
	-- Create the package globals in the given environment
	setfenv( packageModuleFunc, env )()

	-- Make a loader function
	local function loadPackage( modName )
		local init
		if packageCache[modName] == 'missing' then
			return nil
		elseif packageCache[modName] == nil then
			init = php.loadPackage( modName )
			if init == nil then
				packageCache[modName] = 'missing'
				return nil
			end
			packageCache[modName] = init
		else
			init = packageCache[modName]
		end

		setfenv( init, env )
		return init
	end

	table.insert( env.package.loaders, loadPackage )
end

--- Set up the base environment. The PHP host calls this function after any 
-- necessary host-side initialisation has been done.
function mw.setup( options )
	if setupDone then
		return
	end
	setupDone = true

	-- Don't allow getmetatable() on a non-table, since if you can get the metatable,
	-- you can set values in it, breaking isolation
	local old_getmetatable = getmetatable
	function getmetatable(obj)
		if type(obj) == 'table' then
			return old_getmetatable(obj)
		else
			return nil
		end
	end

	if options.allowEnvFuncs then
		allowEnvFuncs = true
	end

	-- Make mw_php private
	--
	-- mw_php.loadPackage() returns function values with their environment
	-- set to the base environment, which would violate module isolation if they
	-- were run from a cloned environment. We can only allow access to 
	-- mw_php.loadPackage via our environment-setting wrapper.
	--
	php = mw_php
	mw_php = nil

	packageModuleFunc = php.loadPackage( 'package' )
	packageCache = {}
end

--- Do a "deep copy" of a table or other value.
function mw.clone( val )
	local tableRefs = {}
	local function recursiveClone( val )
		if type( val ) == 'table' then
			-- Encode circular references correctly
			if tableRefs[val] ~= nil then
				return tableRefs[val]
			end

			local retVal
			retVal = {}
			tableRefs[val] = retVal

			-- Copy metatable
			if getmetatable( val ) then
				setmetatable( retVal, recursiveClone( getmetatable( val ) ) )
			end

			for key, elt in pairs( val ) do
				retVal[key] = recursiveClone( elt )
			end
			return retVal
		else
			return val
		end
	end
	return recursiveClone( val )
end

--- Set up a cloned environment for execution of a module chunk, then execute
-- the module in that environment. This is called by the host to implement 
-- {{#invoke}}.
--
-- @param chunk The module chunk
function mw.executeModule( chunk )
	local env = mw.clone( _G )
	makePackageModule( env )

	if allowEnvFuncs then
		env.setfenv, env.getfenv = mw.makeProtectedEnvFuncs( {[_G] = true}, {} )
	else
		env.setfenv = nil
		env.getfenv = nil
	end

	setfenv( chunk, env )
	return chunk()
end

--- Make isolation-safe setfenv and getfenv functions
--
-- @param protectedEnvironments A table where the keys are protected environment
--    tables. These environments cannot be accessed with getfenv(), and
--    functions with these environments cannot be modified or accessed using 
--    integer indexes to setfenv(). However, functions with these environments 
--    can have their environment set with setfenv() with a function value 
--    argument.
--
-- @param protectedFunctions A table where the keys are protected functions, 
--    which cannot have their environments set by setfenv() with a function 
--    value argument.
--
-- @return setfenv
-- @return getfenv
function mw.makeProtectedEnvFuncs( protectedEnvironments, protectedFunctions )
	local old_setfenv = setfenv
	local old_getfenv = getfenv

	local function my_setfenv( func, newEnv )
		if type( func ) == 'number' then
			local stackIndex = math.floor( func )
			if stackIndex <= 0 then
				error( "'setfenv' cannot set the global environment, it is protected", 2 )
			end
			if stackIndex > 10 then
				error( "'setfenv' cannot set an environment at a level greater than 10", 2 )
			end

			-- Add one because we are still in Lua and 1 is right here
			stackIndex = stackIndex + 1

			local env = old_getfenv( stackIndex )
			if env == nil or protectedEnvironments[ env ] then
				error( "'setfenv' cannot set the requested environment, it is protected", 2 )
			end
			func = old_setfenv( stackIndex, newEnv )
		elseif type( func ) == 'function' then
			if protectedFunctions[func] then
				error( "'setfenv' cannot be called on a protected function", 2 )
			end
			old_setfenv( func, newEnv )
		else
			error( "'setfenv' can only be called with a function or integer as the first argument", 2 )
		end
		return func
	end

	local function my_getfenv( func )
		local env
		if type( func ) == 'number' then
			if func <= 0 then
				error( "'getfenv' cannot get the global environment" )
			end
			env = old_getfenv( func + 1 )
		elseif type( func ) == 'function' then
			env = old_getfenv( func )
		else
			error( "'getfenv' cannot get the global environment" )
		end

		if protectedEnvironments[env] then
			return nil
		else
			return env
		end
	end

	return my_setfenv, my_getfenv
end

local function newFrame( frameId )
	local frame = {}
	local argCache = {}
	local argNames

	local function getExpandedArgument( dummy, name )
		name = tostring( name )
		if argCache[name] == nil then
			local arg = php.getExpandedArgument( frameId, name )
			if arg == nil then
				argCache[name] = false
			else
				argCache[name] = arg
			end
		end
		if argCache[name] == false then
			return nil
		else
			return argCache[name]
		end
	end

	local function newCallbackParserValue( callback )
		value = {}
		local cache

		function value:expand()
			if not cache then
				cache = callback()
			end
			return cache
		end

		return value
	end

	frame.args = {}
	setmetatable( frame.args, { __index = getExpandedArgument } )

	function frame:getArgument( opt )
		local name
		if type( opt ) == 'table' then
			name = opt.name
		else
			name = opt
		end

		return newCallbackParserValue( 
			function () 
				return getExpandedArgument( nil, name )
			end
			)
	end

	function frame:getParent()
		if frameId == 'parent' then
			return nil
		elseif php.parentFrameExists() then
			return newFrame( 'parent' )
		else
			return nil
		end
	end

	function frame:expandTemplate( opt )
		local title

		if type( opt ) ~= 'table' then
			error( "frame:expandTemplate: the first parameter must be a table" )
		end
		if opt.title == nil then
			error( "frame:expandTemplate: a title is required" )
		else
			title = tostring( opt.title )
		end
		local args
		if opt.args == nil then
			args = {}
		elseif type( opt.args ) ~= 'table' then
			error( "frame:expandTitle: args must be a table" )
		else
			args = opt.args
		end

		return php.expandTemplate( frameId, title, args )
	end

	function frame:preprocess( opt )
		local text
		if type( opt ) == 'table' then
			text = opt.text
		else
			text = opt
		end
		text = tostring( text )
		return php.preprocess( frameId, text )
	end

	function frame:newParserValue( opt )
		local text
		if type( opt ) == 'table' then
			text = opt.text
		else
			text = opt
		end

		return newCallbackParserValue(
			function () 
				return self:preprocess( text )
			end
			)
	end

	function frame:newTemplateParserValue( opt )
		if type( opt ) ~= 'table' then
			error( "frame:newTemplateParserValue: the first parameter must be a table" )
		end
		if opt.title == nil then
			error( "frame:newTemplateParserValue: a title is required" )
		end
		return newCallbackParserValue( 
			function ()
				return self:expandTemplate( opt )
			end
			)
	end

	function frame:argumentPairs()
		local index = 0

		local function argumentNext()
			index = index + 1
			if argNames[index] then
				return argNames[index], argCache[argNames[index]]
			end
		end

		if argNames == nil then
			local arguments = php.getAllExpandedArguments( frameId )
			argNames = {}
			for name, value in pairs( arguments ) do
				table.insert( argNames, name )
				argCache[name] = value
			end
		end
		
		return argumentNext
	end

	return frame
end

function mw.executeFunction( chunk )
	local frame = newFrame( 'current' )

	local results = { chunk( frame ) }
	local stringResults = {}
	for i, result in ipairs( results ) do
		stringResults[i] = tostring( result )
	end
	return table.concat( stringResults )
end

return mw
