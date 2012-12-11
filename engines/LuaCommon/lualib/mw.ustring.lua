-- Get a fresh copy of the basic ustring
local old_ustring = package.loaded.ustring
package.loaded.ustring = nil
local ustring = require( 'ustring' )
package.loaded.ustring = old_ustring
old_ustring = nil

local util = require 'libraryUtil'
checkType = util.checkType

local gmatch_init = nil
local gmatch_callback = nil
local function php_gmatch( s, pattern )
	checkType( 'gmatch', 1, s, 'string' )
	checkType( 'gmatch', 2, pattern, 'string' )

	if string.sub( pattern, 1, 1 ) == '^' then
		return function() return nil end, nil, nil
	end

	local re, capt = gmatch_init( s, pattern )
	local pos = 0
	return function()
		local ret
		pos, ret = gmatch_callback( s, re, capt, pos )
		return unpack( ret )
	end, nil, nil
end

function ustring.setupInterface( opt )
	-- Boilerplate
	ustring.setupInterface = nil

	-- Set string limits
	ustring.maxStringLength = opt.stringLengthLimit
	ustring.maxPatternLength = opt.patternLengthLimit

	-- Gmatch
	if mw_interface.gmatch_callback and mw_interface.gmatch_init then
		gmatch_init = mw_interface.gmatch_init
		gmatch_callback = mw_interface.gmatch_callback
		ustring.gmatch = php_gmatch
	end
	mw_interface.gmatch_init = nil
	mw_interface.gmatch_callback = nil

	-- Replace pure-lua implementation with php callbacks
	local nargs = {
		char = 0,
		find = 2,
		match = 2,
		gsub = 3,
	}
	for k, v in pairs( mw_interface ) do
		local n = nargs[k] or 1
		if n == 0 then
			ustring[k] = v
		else
			-- Avoid PHP warnings for missing arguments by checking before
			-- calling PHP.
			ustring[k] = function ( ... )
				if select( '#', ... ) < n then
					error( "too few arguments to mw.ustring." .. k, 2 )
				end
				return v( ... )
			end
		end
	end
	mw_interface = nil

	-- Replace upper/lower with mw.language versions if available
	if mw and mw.language then
		local lang = mw.language.getContentLanguage()
		ustring.upper = function ( s )
			return lang:uc( s )
		end
		ustring.lower = function ( s )
			return lang:lc( s )
		end
	end

	-- Extend string
	local map = {
		isutf8 = ustring.isutf8,
		byteoffset = ustring.byteoffset,
		codepoint = ustring.codepoint,
		gcodepoint = ustring.gcodepoint,
		toNFC = ustring.toNFC,
		toNFD = ustring.toNFD,
		uchar = ustring.char,
		ulen = ustring.len,
		usub = ustring.sub,
		uupper = ustring.upper,
		ulower = ustring.lower,
		ufind = ustring.find,
		umatch = ustring.match,
		ugmatch = ustring.gmatch,
		ugsub = ustring.gsub
	}
	for k, v in pairs( map ) do
		if not string[k] then
			string[k] = v
		end
	end

	-- Register this library in the "mw" global
	mw = mw or {}
	mw.ustring = ustring

	package.loaded['mw.ustring'] = ustring
end

return ustring
