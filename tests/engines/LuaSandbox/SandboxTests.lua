local test = require( 'Module:CommonTests' )
local sbtest = {}

function sbtest.getTests()
	return {
		{ 'setfenv1', { error = '%sinvalid level%s' } },
		{ 'getfenv1', { error = '%sinvalid level%s' } },
	}
end

function sbtest.setfenv1()
	setfenv( 2, {} )
end

function sbtest.getfenv1()
	assert( getfenv( 2 ) == nil )
end

return sbtest
