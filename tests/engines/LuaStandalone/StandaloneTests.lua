local test = require( 'Module:CommonTests' )
local satest = {}

function satest.getTests()
	return {
		{ 'setfenv1', { error = '%s cannot set the requested environment%s' } },
		{ 'getfenv1', true },
	}
end

function satest.setfenv1()
	setfenv( 3, {} )
end

function satest.getfenv1()
	assert( getfenv( 3 ) == nil )
	return true
end

return satest
