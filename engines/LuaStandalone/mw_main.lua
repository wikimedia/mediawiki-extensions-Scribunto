package.path = arg[1] .. '/?.lua'
require('MWServer')
server = MWServer:new()
server:execute()

