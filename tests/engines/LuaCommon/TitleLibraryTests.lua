local testframework = require 'Module:TestFramework'

local title, title_copy, title2, title3, title4, title5, title4p
if mw.ok then
	title = mw.title.getCurrentTitle()
	title_copy = mw.title.getCurrentTitle()
	title2 = mw.title.new( 'Module:TestFramework' )
	title3 = mw.title.new( 'scribuntotitletest:Module:TestFramework' )
	title4 = mw.title.new( 'Talk:Has/A/Subpage' )
	title5 = mw.title.new( 'Not/A/Subpage' )
	title4.fragment = 'frag'

	title4p = mw.title.new( 'Talk:Has/A' )
end

local function prop_foreach( prop )
	return title[prop], title2[prop], title3[prop], title4[prop], title5[prop]
end

local function func_foreach( func, ... )
	return title[func]( title, ... ),
		title2[func]( title2, ... ),
		title3[func]( title3, ... ),
		title4[func]( title4, ... ),
		title5[func]( title5, ... )
end

local function identity( ... )
	return ...
end

local function test_expensive()
	for i = 1, 10 do
		mw.title.new( tostring( i ) )
	end
	return 'did not error'
end

local function test_expensive_cached()
	for i = 1, 100 do
		mw.title.new( 'Title' )
	end
	return 'did not error'
end

local function test_getContent()
	return mw.title.new( 'ScribuntoTestPage' ):getContent()
end

-- Tests
local tests = {
	{ name = 'tostring', func = identity, type = 'ToString',
	  args = { title, title2, title3, title4, title5 },
	  expect = { 'Main Page', 'Module:TestFramework', 'scribuntotitletest:Module:TestFramework', 'Talk:Has/A/Subpage', 'Not/A/Subpage' }
	},

	{ name = 'title.equal', func = mw.title.equals,
	  args = { title, title },
	  expect = { true }
	},
	{ name = 'title.equal (2)', func = mw.title.equals,
	  args = { title, title_copy },
	  expect = { true }
	},
	{ name = 'title.equal (3)', func = mw.title.equals,
	  args = { title, title2 },
	  expect = { false }
	},
	{ name = '==', func = function ()
		return rawequal( title, title_copy ), title == title, title == title_copy, title == title2
	  end,
	  expect = { false, true, true, false }
	},

	{ name = 'title.compare', func = mw.title.compare,
	  args = { title, title },
	  expect = { 0 }
	},
	{ name = 'title.compare (2)', func = mw.title.compare,
	  args = { title, title_copy },
	  expect = { 0 }
	},
	{ name = 'title.compare (3)', func = mw.title.compare,
	  args = { title, title2 },
	  expect = { -1 }
	},
	{ name = 'title.compare (4)', func = mw.title.compare,
	  args = { title2, title },
	  expect = { 1 }
	},
	{ name = 'title.compare (5)', func = mw.title.compare,
	  args = { title2, title3 },
	  expect = { -1 }
	},
	{ name = '<', func = function ()
		return title < title, title < title_copy, title < title2, title2 < title
	  end,
	  expect = { false, false, true, false }
	},
	{ name = '<=', func = function ()
		return title <= title, title <= title_copy, title <= title2, title2 <= title
	  end,
	  expect = { true, true, true, false }
	},

	{ name = 'title.new with namespace', func = mw.title.new, type = 'ToString',
	  args = { 'TestFramework', 'Module' },
	  expect = { 'Module:TestFramework' }
	},
	{ name = 'title.new with namespace (2)', func = mw.title.new, type = 'ToString',
	  args = { 'TestFramework', mw.site.namespaces.Module.id },
	  expect = { 'Module:TestFramework' }
	},
	{ name = 'title.new with namespace (3)', func = mw.title.new, type = 'ToString',
	  args = { 'Template:TestFramework', 'Module' },
	  expect = { 'Template:TestFramework' }
	},
	{ name = 'title.new with invalid title', func = mw.title.new,
	  args = { '<bad title>' },
	  expect = { nil }
	},

	{ name = 'title.makeTitle', func = mw.title.makeTitle, type = 'ToString',
	  args = { 'Module', 'TestFramework' },
	  expect = { 'Module:TestFramework' }
	},
	{ name = 'title.makeTitle (2)', func = mw.title.makeTitle, type = 'ToString',
	  args = { mw.site.namespaces.Module.id, 'TestFramework' },
	  expect = { 'Module:TestFramework' }
	},
	{ name = 'title.makeTitle (3)', func = mw.title.makeTitle, type = 'ToString',
	  args = { mw.site.namespaces.Module.id, 'Template:TestFramework' },
	  expect = { 'Module:Template:TestFramework' }
	},

	{ name = '.isLocal', func = prop_foreach,
	  args = { 'isLocal' },
	  expect = { true, true, false, true, true }
	},
	{ name = '.isTalkPage', func = prop_foreach,
	  args = { 'isTalkPage' },
	  expect = { false, false, false, true, false }
	},
	{ name = '.isSubpage', func = prop_foreach,
	  args = { 'isSubpage' },
	  expect = { false, false, false, true, false }
	},
	{ name = '.text', func = prop_foreach,
	  args = { 'text' },
	  expect = { 'Main Page', 'TestFramework', 'Module:TestFramework', 'Has/A/Subpage', 'Not/A/Subpage' }
	},
	{ name = '.prefixedText', func = prop_foreach,
	  args = { 'prefixedText' },
	  expect = { 'Main Page', 'Module:TestFramework', 'scribuntotitletest:Module:TestFramework', 'Talk:Has/A/Subpage', 'Not/A/Subpage' }
	},
	{ name = '.rootText', func = prop_foreach,
	  args = { 'rootText' },
	  expect = { 'Main Page', 'TestFramework', 'Module:TestFramework', 'Has', 'Not/A/Subpage' }
	},
	{ name = '.baseText', func = prop_foreach,
	  args = { 'baseText' },
	  expect = { 'Main Page', 'TestFramework', 'Module:TestFramework', 'Has/A', 'Not/A/Subpage' }
	},
	{ name = '.subpageText', func = prop_foreach,
	  args = { 'subpageText' },
	  expect = { 'Main Page', 'TestFramework', 'Module:TestFramework', 'Subpage', 'Not/A/Subpage' }
	},
	{ name = '.fullText', func = prop_foreach,
	  args = { 'fullText' },
	  expect = { 'Main Page', 'Module:TestFramework', 'scribuntotitletest:Module:TestFramework', 'Talk:Has/A/Subpage#frag', 'Not/A/Subpage' }
	},
	{ name = '.subjectNsText', func = prop_foreach,
	  args = { 'subjectNsText' },
	  expect = { '', 'Module', '', '', '' }
	},
	{ name = '.fragment', func = prop_foreach,
	  args = { 'fragment' },
	  expect = { '', '', '', 'frag', '' }
	},
	{ name = '.interwiki', func = prop_foreach,
	  args = { 'interwiki' },
	  expect = { '', '', 'scribuntotitletest', '', '' }
	},
	{ name = '.namespace', func = prop_foreach,
	  args = { 'namespace' },
	  expect = { 0, mw.site.namespaces.Module.id, 0, 1, 0 }
	},
	{ name = '.inNamespace()', func = func_foreach,
	  args = { 'inNamespace', 'Module' },
	  expect = { false, true, false, false, false }
	},
	{ name = '.inNamespace() 2', func = func_foreach,
	  args = { 'inNamespace', mw.site.namespaces.Module.id },
	  expect = { false, true, false, false, false }
	},
	{ name = '.inNamespaces()', func = func_foreach,
	  args = { 'inNamespaces', 0, 1 },
	  expect = { true, false, true, true, true }
	},
	{ name = '.hasSubjectNamespace()', func = func_foreach,
	  args = { 'hasSubjectNamespace', 0 },
	  expect = { true, false, true, true, true }
	},
	{ name = '.isSubpageOf() 1', func = func_foreach,
	  args = { 'isSubpageOf', title },
	  expect = { false, false, false, false, false }
	},
	{ name = '.isSubpageOf() 2', func = func_foreach,
	  args = { 'isSubpageOf', title4p },
	  expect = { false, false, false, true, false }
	},
	{ name = '.partialUrl()', func = func_foreach,
	  args = { 'partialUrl' },
	  expect = { 'Main_Page', 'TestFramework', 'Module:TestFramework', 'Has/A/Subpage', 'Not/A/Subpage' }
	},
	{ name = '.fullUrl()', func = func_foreach,
	  args = { 'fullUrl' },
	  expect = {
		  '//wiki.local/wiki/Main_Page',
		  '//wiki.local/wiki/Module:TestFramework',
		  '//test.wikipedia.org/wiki/Module:TestFramework',
		  '//wiki.local/wiki/Talk:Has/A/Subpage#frag',
		  '//wiki.local/wiki/Not/A/Subpage'
	  }
	},
	{ name = '.fullUrl() 2', func = func_foreach,
	  args = { 'fullUrl', { action = 'test' } },
	  expect = {
		  '//wiki.local/w/index.php?title=Main_Page&action=test',
		  '//wiki.local/w/index.php?title=Module:TestFramework&action=test',
		  '//test.wikipedia.org/wiki/Module:TestFramework?action=test',
		  '//wiki.local/w/index.php?title=Talk:Has/A/Subpage&action=test#frag',
		  '//wiki.local/w/index.php?title=Not/A/Subpage&action=test'
	  }
	},
	{ name = '.fullUrl() 3', func = func_foreach,
	  args = { 'fullUrl', nil, 'http' },
	  expect = {
		  'http://wiki.local/wiki/Main_Page',
		  'http://wiki.local/wiki/Module:TestFramework',
		  'http://test.wikipedia.org/wiki/Module:TestFramework',
		  'http://wiki.local/wiki/Talk:Has/A/Subpage#frag',
		  'http://wiki.local/wiki/Not/A/Subpage'
	  }
	},
	{ name = '.localUrl()', func = func_foreach,
	  args = { 'localUrl' },
	  expect = {
		  '/wiki/Main_Page',
		  '/wiki/Module:TestFramework',
		  '//test.wikipedia.org/wiki/Module:TestFramework',
		  '/wiki/Talk:Has/A/Subpage',
		  '/wiki/Not/A/Subpage'
	  }
	},
	{ name = '.canonicalUrl()', func = func_foreach,
	  args = { 'canonicalUrl' },
	  expect = {
		  'http://wiki.local/wiki/Main_Page',
		  'http://wiki.local/wiki/Module:TestFramework',
		  'http://test.wikipedia.org/wiki/Module:TestFramework',
		  'http://wiki.local/wiki/Talk:Has/A/Subpage#frag',
		  'http://wiki.local/wiki/Not/A/Subpage'
	  }
	},

	{ name = '.getContent()', func = test_getContent,
	  expect = { '{{int:mainpage}}<includeonly>...</includeonly><noinclude>...</noinclude>' }
	},

	{ name = 'expensive functions', func = test_expensive,
	  expect = 'too many expensive function calls'
	},
	{ name = 'expensive cached', func = test_expensive_cached,
	  expect = { 'did not error' }
	},
}

return testframework.getTestProvider( tests )
