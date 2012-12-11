local ustring = require( 'ustring/ustring' )

function ustring.setupInterface()
	-- Boilerplate
	ustring.setupInterface = nil

	-- Replace pure-lua implementation with php callbacks
	for k, v in pairs( mw_interface ) do
		ustring[k] = v
	end

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

	-- Register this module in the "mw" global
	mw = mw or {}
	mw.ustring = ustring
end

return ustring
