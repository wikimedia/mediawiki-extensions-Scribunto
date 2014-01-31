--[[
	Tests for the mw.html module

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'

local function getEmptyTestDiv()
	return mw.html.create( 'div' )
end

local function testHelper( obj, method, ... )
	return obj[method]( obj, ... )
end

-- Test attrbutes which will always be paired in the same order
local testAttrs = { foo = 'bar', ab = 'cd' }
setmetatable( testAttrs, { __pairs = function ( t )
	local keys = { 'ab', 'foo' }
	local i = 0
	return function()
		i = i + 1
		if i <= #keys then
			return keys[i], t[keys[i]]
		end
	end
end } )


-- More complex test functions

local function testMultiAddClass()
	return getEmptyTestDiv():addClass( 'foo' ):addClass( 'bar' )
end

local function testCssAndCssText()
	return getEmptyTestDiv():css( 'foo', 'bar' ):cssText( 'abc:def' ):css( 'g', 'h' )
end

local function testTagDone()
	return getEmptyTestDiv():tag( 'span' ):done()
end

local function testNodeDone()
	return getEmptyTestDiv():node( getEmptyTestDiv() ):done()
end

local function testTagNodeAllDone()
	return getEmptyTestDiv():tag( 'p' ):node( getEmptyTestDiv() ):allDone()
end

local function testAttributeOverride()
	return getEmptyTestDiv():attr( 'good', 'MediaWiki' ):attr( 'good', 'Wikibase' )
end

local function testGetAttribute()
	return getEmptyTestDiv():attr( 'town', 'Berlin' ):getAttr( 'town' )
end

local function testGetAttributeEscaping()
	return getEmptyTestDiv():attr( 'foo', '<ble"&rgh>' ):getAttr( 'foo' )
end

local function testNodeSelfClosingDone()
	return getEmptyTestDiv():node( mw.html.create( 'br' ) ):done()
end

local function testNodeAppendToSelfClosing()
	return mw.html.create( 'img' ):node( getEmptyTestDiv() )
end

local function testWikitextAppendToSelfClosing()
	return mw.html.create( 'hr' ):wikitext( 'foo' )
end

local function testEmptyCreate()
	return mw.html.create( '' ):wikitext( 'foo' ):tag( 'div' ):attr( 'a', 'b' ):allDone()
end

local function testComplex()
	local builder = getEmptyTestDiv()

	builder:addClass( 'firstClass' ):attr( 'what', 'ever' )

	builder:tag( 'meh' ):attr( 'whynot', 'Русский' ):tag( 'hr' ):attr( 'a', 'b' )

	builder:node( mw.html.create( 'hr' ) )

	builder:node( getEmptyTestDiv():attr( 'abc', 'def' ):css( 'width', '-1px' ) )

	return builder
end

-- Tests
local tests = {
	-- Simple (inline) tests
	{ name = 'mw.html.create', func = mw.html.create, type='ToString',
	  args = { 'table' },
	  expect = { '<table></table>' }
	},
	{ name = 'mw.html.create (self closing)', func = mw.html.create, type='ToString',
	  args = { 'br' },
	  expect = { '<br />' }
	},
	{ name = 'mw.html.create (self closing - forced)', func = mw.html.create, type='ToString',
	  args = { 'div', { selfClosing = true } },
	  expect = { '<div />' }
	},
	{ name = 'mw.html.create (invalid tag 1)', func = mw.html.create, type='ToString',
	  args = { '$$$$' },
	  expect = 'Invalid tag name: $$$$'
	},
	{ name = 'mw.html.create (invalid tag 2)', func = mw.html.create, type='ToString',
	  args = { {} },
	  expect = 'Tag name must be a string'
	},
	{ name = 'mw.html.create (invalid tag 3)', func = mw.html.create, type='ToString',
	  expect = 'Tag name must be a string'
	},
	{ name = 'mw.html.wikitext', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'wikitext', 'Plain text' },
	  expect = { '<div>Plain text</div>' }
	},
	{ name = 'mw.html.wikitext (invalid input)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'wikitext', 'Plain text', {} },
	  expect = 'Invalid wikitext given: Must be either a string or a number'
	},
	{ name = 'mw.html.newline', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'newline' },
	  expect = { '<div>\n</div>' }
	},
	{ name = 'mw.html.tag', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'tag', 'span' },
	  -- tag is only supposed to return the new (inner) node
	  expect = { '<span></span>' }
	},
	{ name = 'mw.html.attr', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', 'foo', 'bar' },
	  expect = { '<div foo="bar"></div>' }
	},
	{ name = 'mw.html.attr (table 1)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', { foo = 'bar' } },
	  expect = { '<div foo="bar"></div>' }
	},
	{ name = 'mw.html.attr (table 2)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', testAttrs },
	  expect = { '<div ab="cd" foo="bar"></div>' }
	},
	{ name = 'mw.html.attr (invalid name 1)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', true, 'bar' },
	  expect = 'Invalid name given: The name must be either a string or a number'
	},
	{ name = 'mw.html.attr (invalid name 2)', func = testHelper,
	  args = { getEmptyTestDiv(), 'attr', '§§§§', 'foo' },
	  expect = 'Invalid attribute name: §§§§'
	},
	{ name = 'mw.html.attr (table no value)', func = testHelper,
	  args = { getEmptyTestDiv(), 'attr', { foo = 'bar' }, 'foo' },
	  expect = 'If a key->value table is given as first parameter, value must be left empty'
	},
	{ name = 'mw.html.attr (invalid value)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', 'foo', true },
	  expect = 'Invalid value given: The value must be either a string or a number'
	},
	{ name = 'mw.html.attr (invalid table 1)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', { foo = {} } },
	  expect = 'Invalid table given: Must be name (string) value (string|number) pairs'
	},
	{ name = 'mw.html.attr (invalid table 2)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', { 1, 2 ,3 } },
	  expect = 'Invalid table given: Must be name (string) value (string|number) pairs'
	},
	{ name = 'mw.html.attr (invalid table 3)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', { foo = 'bar', blah = true } },
	  expect = 'Invalid table given: Must be name (string) value (string|number) pairs'
	},
	{ name = 'mw.html.attr (invalid table 4)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', { [{}] = 'foo' } },
	  expect = 'Invalid table given: Must be name (string) value (string|number) pairs'
	},
	{ name = 'mw.html.getAttr (nil)', func = testHelper,
	  args = { getEmptyTestDiv(), 'getAttr', 'foo' },
	  expect = { nil }
	},
	{ name = 'mw.html.addClass', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'addClass', 'foo' },
	  expect = { '<div class="foo"></div>' }
	},
	{ name = 'mw.html.addClass (invalid value)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'addClass', {} },
	  expect = 'Invalid class given: The name must be either a string or a number'
	},
	{ name = 'mw.html.css', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', 'foo', 'bar' },
	  expect = { '<div style="foo:bar;"></div>' }
	},
	{ name = 'mw.html.css (invalid name 1)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', function() end, 'bar' },
	  expect = 'Invalid CSS given: The name must be either a string or a number'
	},
	{ name = 'mw.html.css (table no value)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', {}, 'bar' },
	  expect = 'If a key->value table is given as first parameter, value must be left empty'
	},
	{ name = 'mw.html.css (invalid value)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', 'foo', {} },
	  expect = 'Invalid CSS given: The value must be either a string or a number'
	},
	{ name = 'mw.html.css (table)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', testAttrs },
	  expect = { '<div style="ab:cd;foo:bar;"></div>' }
	},
	{ name = 'mw.html.css (invalid table)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', { foo = 'bar', ab = true } },
	  expect = 'Invalid table given: Must be name (string|number) value (string|number) pairs'
	},
	{ name = 'mw.html.cssText', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'cssText', 'Unit tests, ftw' },
	  expect = { '<div style="Unit tests, ftw;"></div>' }
	},
	{ name = 'mw.html.cssText (invalid value)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'cssText', {} },
	  expect = 'Invalid CSS given: Must be either a string or a number'
	},
	{ name = 'mw.html attribute escaping (value with double quotes)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', 'foo', 'ble"rgh' },
	  expect = { '<div foo="ble&quot;rgh"></div>' }
	},
	{ name = 'mw.html attribute escaping 1', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', 'foo', 'ble<rgh' },
	  expect = { '<div foo="ble&lt;rgh"></div>' }
	},
	{ name = 'mw.html attribute escaping 2', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'attr', 'foo', '<ble"&rgh>' },
	  expect = { '<div foo="&lt;ble&quot;&amp;rgh&gt;"></div>' }
	},
	{ name = 'mw.html attribute escaping (CSS)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'css', 'mu"ha', 'ha"ha' },
	  expect = { '<div style="mu&quot;ha:ha&quot;ha;"></div>' }
	},
	{ name = 'mw.html attribute escaping (CSS raw)', func = testHelper, type='ToString',
	  args = { getEmptyTestDiv(), 'cssText', 'mu"ha:-ha"ha' },
	  expect = { '<div style="mu&quot;ha:-ha&quot;ha;"></div>' }
	},

	-- Tests defined above

	{ name = 'mw.html.addClass (twice) ', func = testMultiAddClass, type='ToString',
	  expect = { '<div class="foo bar"></div>' }
	},
	{ name = 'mw.html.css.cssText.css', func = testCssAndCssText, type='ToString',
	  expect = { '<div style="foo:bar;abc:def;g:h;"></div>' }
	},
	{ name = 'mw.html.tag (using done)', func = testTagDone, type='ToString',
	  expect = { '<div><span></span></div>' }
	},
	{ name = 'mw.html.node (using done)', func = testNodeDone, type='ToString',
	  expect = { '<div><div></div></div>' }
	},
	{ name = 'mw.html.node (self closing, using done)', func = testNodeSelfClosingDone, type='ToString',
	  expect = { '<div><br /></div>' }
	},
	{ name = 'mw.html.node (append to self closing)', func = testNodeAppendToSelfClosing, type='ToString',
	  expect = "Self-closing tags can't have child nodes"
	},
	{ name = 'mw.html.wikitext (append to self closing)', func = testWikitextAppendToSelfClosing, type='ToString',
	  expect = "Self-closing tags can't have child nodes"
	},
	{ name = 'mw.html.tag.node (using allDone)', func = testTagNodeAllDone, type='ToString',
	  expect = { '<div><p><div></div></p></div>' }
	},
	{ name = 'mw.html.attr (overrides)', func = testAttributeOverride, type='ToString',
	  expect = { '<div good="Wikibase"></div>' }
	},
	{ name = 'mw.html.getAttr', func = testGetAttribute, type='ToString',
	  expect = { 'Berlin' }
	},
	{ name = 'mw.html.getAttr (escaping)', func = testGetAttributeEscaping, type='ToString',
	  expect = { '<ble"&rgh>' }
	},
	{ name = 'mw.html.create (empty)', func = testEmptyCreate, type='ToString',
	  expect = { 'foo<div a="b"></div>' }
	},
	{ name = 'mw.html complex test', func = testComplex, type='ToString',
	  expect = {
		'<div class="firstClass" what="ever"><meh whynot="Русский"><hr a="b" /></meh>' ..
		'<hr /><div abc="def" style="width:-1px;"></div></div>'
	  }
	},
}

return testframework.getTestProvider( tests )
