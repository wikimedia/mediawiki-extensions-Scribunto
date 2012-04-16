MWServer = {}

function MWServer:new()
	obj = {
		chunks = {},
		xchunks = {},
		protectedFunctions = {},
		baseEnv = {}
	}
	setmetatable( obj, self )
	self.__index = self

	obj:init()

	return obj
end

function MWServer:init()
	self.baseEnv = self:newEnvironment()
	for funcName, func in pairs( self ) do
		if type(func) == 'function' then
			self.protectedFunctions[func] = true
		end
	end
end

function MWServer:execute()
	self:dispatch( nil )
end

function MWServer:call( id, args )
	local result = self:dispatch( {
		op = 'call',
		id = id,
		args = args
	} )
	if result.op == 'return' then
		return unpack( result.values )
	elseif result.op == 'error' then
		-- Raise an error in the actual user code that called the function
		-- The level is 3 since our immediate caller is a closure
		error( result.value, 3 )
	else
		error( 'MWServer:call: unexpected result op' )
	end
end

function MWServer:handleCall( message )
	local result = { pcall( self.chunks[message.id], unpack( message.args ) ) }
	if (result[1]) then
		table.remove( result, 1 )
		return {
			op = 'return',
			values = result
		}
	else
		return {
			op = 'error',
			value = result[2]
		}
	end
end

function MWServer:handleLoadString( message )
	if string.find( message.text, '\27Lua', 1, true ) then
		return {
			op = 'error',
			value = 'cannot load code with a Lua binary chunk marker escape sequence in it'
		}
	end
	local chunk, errorMsg = loadstring( message.text, message.chunkName )
	if chunk then
		setfenv( chunk, self.baseEnv )
		local id = self:addChunk( chunk )
		return {
			op = 'return',
			values = {id}
		}
	else
		return {
			op = 'error',
			value = errorMsg
		}
	end
end

function MWServer:addChunk( chunk )
	local id = #self.chunks + 1
	self.chunks[id] = chunk
	self.xchunks[chunk] = id
	return id
end

function MWServer:handleRegisterLibrary( message )
	local startPos = 1
	local component
	local t = self.baseEnv
	while startPos <= #message.name do
		local dotPos = string.find( message.name, '.', startPos, true )
		if not dotPos then
			dotPos = #message.name + 1
		end
		component = string.sub( message.name, startPos, dotPos - 1 )
		if t[component] == nil then
			t[component] = {}
		end
		t = t[component]
		startPos = dotPos + 1
	end

	for name, id in pairs( message.functions ) do
		t[name] = function( ... )
			return self:call( id, { ... } )
		end
		-- Protect the function against setfenv()
		self.protectedFunctions[t[name]] = true
	end
	
	return {
		op = 'return',
		values = {}
	}
end

function MWServer:handleGetStatus( message )
	local nullRet = {
		op = 'return',
		values = {}
	}
	local file = io.open( '/proc/self/stat' )
	if not file then
		return nullRet
	end
	local s = file:read('*a')
	file:close()
	local t = {}
	for token in string.gmatch(s, '[^ ]+') do
		t[#t + 1] = token
	end
	if #t < 22 then
		return nullRet
	end
	return {
		op = 'return',
		values = {{
			pid = tonumber(t[1]),
			time = tonumber(t[14]) + tonumber(t[15]) + tonumber(t[16]) + tonumber(t[17]),
			vsize = tonumber(t[23]),
		}}
	}
end

function MWServer:dispatch( msgToPhp )
	if msgToPhp then
		self:sendMessage( msgToPhp )
	end
	while true do
		local msgFromPhp = self:receiveMessage()
		local msgToPhp
		local op = msgFromPhp.op
		if op == 'return' or op == 'error' then
			return msgFromPhp
		elseif op == 'call' then
			msgToPhp = self:handleCall( msgFromPhp )
			self:sendMessage( msgToPhp )
		elseif op == 'loadString' then
			msgToPhp = self:handleLoadString( msgFromPhp )
			self:sendMessage( msgToPhp )
		elseif op == 'registerLibrary' then
			msgToPhp = self:handleRegisterLibrary( msgFromPhp )
			self:sendMessage( msgToPhp )
		elseif op == 'getStatus' then
			msgToPhp = self:handleGetStatus( msgFromPhp )
			self:sendMessage( msgToPhp )
		elseif op == 'quit' then
			os.exit(0)
		else
			error( "Invalid message operation" )
		end
	end
end

function MWServer:debug( s )
	if ( type(s) == 'string' ) then
		io.stderr:write( s .. '\n' )
	else
		io.stderr:write( self:serialize( s ) .. '\n' )
	end
end

function MWServer:ioError( header, info )
	if type( info) == 'string' then
		error( header .. ': ' .. info, 2 )
	else
		error( header, 2 )
	end
end

function MWServer:sendMessage( msg )
	if not msg.op then
		error( "MWServer:sendMessage: invalid message", 2 )
	end
	self:debug('==> ' .. msg.op)
	local encMsg = self:encodeMessage( msg )
	local success, errorMsg = io.stdout:write( encMsg )
	if not success then
		self:ioError( 'Write error', errorMsg )
	end
	io.stdout:flush()
end

function MWServer:receiveMessage()
	-- Read the header
	local header, errorMsg = io.stdin:read( 16 )
	if header == nil and errorMsg == nil then
		-- End of file on stdin, exit gracefully
		os.exit(0)
	end

	if not header or #header ~= 16 then
		self:ioError( 'Read error', errorMsg )
	end
	local length = self:decodeHeader( header )

	-- Read the body
	local body, errorMsg = io.stdin:read( length )
	if not body then
		self:ioError( 'Read error', errorMsg )
	end
	if #body ~= length then
		self:ioError( 'Read error', errorMsg )
	end

	-- Unserialize it
	msg = self:unserialize( body )
	self:debug('<== ' .. msg.op)
	return msg
end

function MWServer:encodeMessage( message )
	local serialized = self:serialize( message )
	local length = #serialized
	local check = length * 2 - 1
	return string.format( '%08x%08x%s', length, check, serialized )
end

function MWServer:serialize( var )
	local done = {}
	local int_min = -2147483648
	local int_max = 2147483647

	local function isInteger( var )
		return type(var) == 'number'
			and math.floor( var ) == var 
			and var >= int_min 
			and var <= int_max
	end

	local function recursiveEncode( var, level )
		local t = type( var )
		if t == 'nil' then
			return 'N;'
		elseif t == 'number' then
			if isInteger(var) then
				return 'i:' .. var .. ';'
			else
				return 'd:' .. var .. ';'
			end
		elseif t == 'string' then
			return 's:' .. string.len( var ) .. ':"' .. var .. '";'
		elseif t == 'boolean' then
			if var then
				return 'b:1;'
			else
				return 'b:0;'
			end
		elseif t == 'table' then
			if done[var] then
				error("Cannot pass circular reference to PHP")
			end
			done[var] = true
			local buf = { '' }
			local tmpString
			local numElements = 0
			for key, value in pairs(var) do
				if (isInteger(key)) then
					buf[#buf + 1] = 'i:' .. key .. ';'
				else
					tmpString = tostring( key )
					buf[#buf + 1] = recursiveEncode( tostring( key ), level + 1 )
				end
				buf[#buf + 1] = recursiveEncode( value, level + 1 )
				numElements = numElements + 1
			end
			buf[1] = 'a:' .. numElements .. ':{'
			buf[#buf + 1] = '}'
			return table.concat(buf)
		elseif t == 'function' then
			local id
			if self.xchunks[var] then
				id = self.xchunks[var]
			else
				id = self:addChunk(var)
			end
			return 'O:42:"Scribunto_LuaStandaloneInterpreterFunction":1:{s:2:"id";i:' .. id .. ';}'
		elseif t == 'thread' then
			error("Cannot pass thread to PHP")
		elseif t == 'userdata' then
			error("Cannot pass userdata to PHP")
		else
			error("Cannot pass unrecognised type to PHP")
		end
	end

	return recursiveEncode( var, 0 )
end

function MWServer:unserialize( text )
	local func = loadstring( 'return ' .. text )
	-- Don't waste JIT cache space by storing every message in it
	if jit then
		jit.off( func )
	end
	setfenv( func, { chunks = self.chunks } )
	return func()
end

function MWServer:decodeHeader( header )
	local length = string.sub( header, 1, 8 )
	local check = string.sub( header, 9, 16 )
	if not string.match( length, '^%x+$' ) or not string.match( check, '^%x+$' ) then
		error( "Error decoding message header: " .. length .. '/' .. check )
	end
	length = tonumber( length, 16 )
	check = tonumber( check, 16 )
	if length * 2 - 1 ~= check then
		error( "Error decoding message header" )
	end
	return length
end

function MWServer:clone( val )
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

function MWServer:newEnvironment()
	local allowedGlobals = {
		-- base
		"assert",
		"error",
		"getmetatable",
		"getmetatable",
		"ipairs",
		"next",
		"pairs",
		"pcall",
		"rawequal",
		"rawget",
		"rawset",
		"select",
		"setmetatable",
		"tonumber",
		"type",
		"unpack",
		"xpcall",
		"_VERSION",
		-- libs
		"string",
		"table",
		"math"
	}

	local env = {}
	local i
	for i = 1, #allowedGlobals do
		env[allowedGlobals[i]] = self:clone( _G[allowedGlobals[i]] )
	end

	env._G = env
	env.tostring = function( val )
		self:tostring( val )
	end
	env.string.dump = nil
	env.setfenv = function( func, newEnv )
		self:setfenv( func, newEnv )
	end
	return env
end

function MWServer:tostring(val)
	local mt = getmetatable( val )
	if mt and mt.__tostring then
		return mt.__tostring(val)
	end
	local typeName = type(val)
	local nonPointerTypes = {number = true, string = true, boolean = true, ['nil'] = true}
	if nonPointerTypes[typeName] then
		return tostring(val)
	else
		return typeName
	end
end

function MWServer:setfenv( func, newEnv )
	if type( func ) ~= 'function' then
		error( "'setfenv' can only be called with a function as the first argument" )
	end
	if self.protectedFunctions[func] then
		error( "'setfenv' cannot be called on a protected function" )
	end
	setfenv( func, newEnv )
	return func
end

return MWServer
