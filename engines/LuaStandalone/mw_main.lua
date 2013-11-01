package.path = arg[1] .. '/engines/LuaStandalone/?.lua;' ..
	arg[1] .. '/engines/LuaCommon/lualib/?.lua'

require('MWServer')
require('mw')
server = MWServer:new( arg[2] )
server:execute()

