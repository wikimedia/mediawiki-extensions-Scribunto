local test = require( 'Module:CommonTests' )
local satest = {}

function satest.getTests()
	return {
		{ 'setfenv1', { error = '%s cannot set the requested environment%s' } },
		{ 'getfenv1', 'ok' },
	}
end

function satest.setfenv1()
	setfenv( 4, {} )
end

function satest.getfenv1()
	assert( getfenv( 4 ) == nil )
	return 'ok'
end

return satest
