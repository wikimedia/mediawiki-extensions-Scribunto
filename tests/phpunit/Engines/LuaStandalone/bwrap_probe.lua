-- Run by StandaloneBwrapInterpreterTest through lua_bwrap.sh, outside of
-- Scribunto's Lua sandbox, to check what the bubblewrap sandbox allows.
local dir, outsideFile, hostPid = ...

local function canOpen( path, mode )
	local f = io.open( path, mode )
	if f then
		f:close()
		return true
	end
	return false
end

print( 'readOwnDir=' .. tostring( canOpen( dir .. '/bwrap_probe.lua' ) ) )
print( 'readOutside=' .. tostring( canOpen( outsideFile ) ) )
print( 'writeOwnDir=' .. tostring( canOpen( dir .. '/bwrap_probe.out', 'w' ) ) )
print( 'writeTmp=' .. tostring( canOpen( '/tmp/bwrap_probe.out', 'w' ) ) )
print( 'hostProcess=' .. tostring( canOpen( '/proc/' .. hostPid .. '/stat' ) ) )
print( 'envPath=' .. tostring( os.getenv( 'PATH' ) ) )
print( 'envHome=' .. tostring( os.getenv( 'HOME' ) ) )
