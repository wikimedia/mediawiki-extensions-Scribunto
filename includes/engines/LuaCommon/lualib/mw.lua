mw = mw or {
	ustring = require('ustring'),
	text = require('text'),
	loglevel = 3
}

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

local logBuffer = ''

function mw.allToString( ... )
	local t = { ... }
	for i = 1, select( '#', ... ) do
		t[i] = tostring( t[i] )
	end
	return table.concat( t, '\t' )
end

function mw.log( ... )
	if mw.loglevel ~= 0 then
		logBuffer = logBuffer .. mw.allToString( ... ) .. '\n'
	end
end

function mw.dumpObject( object )
	local doneTable = {}
	local doneObj = {}
	local ct = {}
	local function sorter( a, b )
		local ta, tb = type( a ), type( b )
		if ta ~= tb then
			return ta < tb
		end
		if ta == 'string' or ta == 'number' then
			return a < b
		end
		if ta == 'boolean' then
			return tostring( a ) < tostring( b )
		end
		return false -- Incomparable
	end
	local function _dumpObject( object, indent, expandTable )
		local tp = type( object )
		if tp == 'number' or tp == 'nil' or tp == 'boolean' then
			return tostring( object )
		elseif tp == 'string' then
			return string.format( "%q", object )
		elseif tp == 'table' then
			if not doneObj[object] then
--				local s = 'table' -- tostring( object )
--				
--				if s == 'table' then
					ct[tp] = ( ct[tp] or 0 ) + 1
					doneObj[object] = 'table#' .. ct[tp]
--				else
--					doneObj[object] = s
--					doneTable[object] = true
--				end
			end
			if doneTable[object] or not expandTable then
				return doneObj[object]
			end
			doneTable[object] = true

			local ret = { doneObj[object], ' {\n' }
			local mt = getmetatable( object )
			if mt then
				ret[#ret + 1] = string.rep( " ", indent + 2 )
				ret[#ret + 1] = 'metatable = '
				ret[#ret + 1] = _dumpObject( mt, indent + 2, false )
				ret[#ret + 1] = "\n"
			end

			local doneKeys = {}
			for key, value in ipairs( object ) do
				doneKeys[key] = true
				ret[#ret + 1] = string.rep( " ", indent + 2 )
				ret[#ret + 1] = _dumpObject( value, indent + 2, true )
				ret[#ret + 1] = ',\n'
			end
			local keys = {}
			for key in pairs( object ) do
				if not doneKeys[key] then
					keys[#keys + 1] = key
				end
			end
			table.sort( keys, sorter )
			for i = 1, #keys do
				local key = keys[i]
				ret[#ret + 1] = string.rep( " ", indent + 2 )
				ret[#ret + 1] = '['
				ret[#ret + 1] = _dumpObject( key, indent + 3, false )
				ret[#ret + 1] = '] = '
				ret[#ret + 1] = _dumpObject( object[key], indent + 2, true )
				ret[#ret + 1] = ",\n"
			end
			ret[#ret + 1] = string.rep( " ", indent )
			ret[#ret + 1] = '}'
			return table.concat( ret )
		else
			if not doneObj[object] then
				ct[tp] = ( ct[tp] or 0 ) + 1
				doneObj[object] = tostring( object ) .. '#' .. ct[tp]
			end
			return doneObj[object]
		end
	end
	return _dumpObject( object, 0, true )
end

function mw.logObject( object, prefix )
	if prefix and prefix ~= '' then
		logBuffer = logBuffer .. prefix .. ' = '
	end
	logBuffer = logBuffer .. mw.dumpObject( object ) .. '\n'
end

function mw.clearLogBuffer()
	logBuffer = ''
end

function mw.getLogBuffer()
	return logBuffer
end

local loadedData = {}

---
-- Wrapper for mw.loadData. This creates the read-only dummy table for
-- accessing the real data.
--
-- @param data table Data to access
-- @param seen table|nil Table of already-seen tables.
-- @return table
local function dataWrapper( data, seen )
	local t = {}
	seen = seen or { [data] = t }

	local function pairsfunc( s, k )
		k = next( data, k )
		if k ~= nil then
			return k, t[k]
		end
		return nil
	end

	local function ipairsfunc( s, i )
		i = i + 1
		if data[i] ~= nil then
			return i, t[i]
		end
		return -- no nil to match default ipairs()
	end

	local mt = {
		mw_loadData = true,
		__index = function ( tt, k )
			assert( t == tt )
			local v = data[k]
			if type( v ) == 'table' then
				seen[v] = seen[v] or dataWrapper( v, seen )
				return seen[v]
			end
			return v
		end,
		__newindex = function ( t, k, v )
			error( "table from mw.loadData is read-only", 2 )
		end,
		__pairs = function ( tt )
			assert( t == tt )
			return pairsfunc, t, nil
		end,
		__ipairs = function ( tt )
			assert( t == tt )
			return ipairsfunc, t, 0
		end,
	}
	-- This is just to make setmetatable() fail
	mt.__metatable = mt

	return setmetatable( t, mt )
end

---
-- Validator for mw.loadData. This scans through the data looking for things
-- that are not supported, e.g. functions (which may be closures).
--
-- @param d table Data to access.
-- @param seen table|nil Table of already-seen tables.
-- @return string|nil Error message, if any
local function validateData( d, seen )
	seen = seen or {}
	local tp = type( d )
	if tp == 'nil' or tp == 'boolean' or tp == 'number' or tp == 'string' then
		return nil
	elseif tp == 'table' then
		if seen[d] then
			return nil
		end
		seen[d] = true
		if getmetatable( d ) ~= nil then
			return "data for mw.loadData contains a table with a metatable"
		end
		for k, v in pairs( d ) do
			if type( k ) == 'table' then
				return "data for mw.loadData contains a table as a key"
			end
			local err = validateData( k, seen ) or validateData( v, seen )
			if err then
				return err
			end
		end
		return nil
	else
		return "data for mw.loadData contains unsupported data type '" .. tp .. "'"
	end
end

function mw.loadData( module )
	local data = loadedData[module]
	if type( data ) == 'string' then
		-- No point in re-validating
		error( data, 2 )
	end
	if not data then
		--[[ Don't allow accessing the current frame's info (bug 65687)
		local oldFrame = currentFrame
		currentFrame = newFrame( 'empty' )

		-- The point of this is to load big data, so don't save it in package.loaded
		-- where it will have to be copied for all future modules.
		local l = package.loaded[module]
		local _

		_, data = mw.executeModule( function() return require( module ) end )

		package.loaded[module] = l
		currentFrame = oldFrame
		]]

		data = require(module)
		-- Validate data
		local err
		if type( data ) == 'table' then
			err = validateData( data )
		else
			err = module .. ' returned ' .. type( data ) .. ', table expected'
		end
		if err then
			loadedData[module] = err
			error( err, 2 )
		end
		loadedData[module] = data
	end

	-- return dataWrapper( data )
	return data;
end

return mw