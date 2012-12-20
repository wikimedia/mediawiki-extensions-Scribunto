local language = {}
local php

function language.setupInterface()
	-- Boilerplate
	language.setupInterface = nil
	php = mw_interface
	mw_interface = nil

	-- Register this module in the "mw" global
	mw = mw or {}
	mw.language = language
	mw.getContentLanguage = language.getContentLanguage
	mw.getLanguage = mw.language.new
end

function language.isValidCode( code )
	return php.isValidCode( code )
end

function language.isValidBuiltInCode( code )
	return php.isValidBuiltInCode( code )
end

function language.fetchLanguageName( code, inLanguage )
	return php.fetchLanguageName( code, inLanguage )
end

function language.new( code )
	if code == nil then
		error( "too few arguments to mw.language.new()", 2 )
	end

	local lang = { code = code }

	local function checkSelf( self, method )
		if self ~= lang then
			error( "mw.language:" .. method .. ": invalid language object. " ..
				"Did you call " .. method .. " with a dot instead of a colon, i.e. " ..
				"lang." .. method .. "() instead of lang:" .. method .. "()?",
				3 )
		end
	end

	local wrappers = {
		isRTL = 0,
		lcfirst = 1,
		ucfirst = 1,
		lc = 1,
		uc = 1,
		formatNum = 1,
		parseFormattedNumber = 1,
		convertPlural = 2,
	}

	for name, numArgs in pairs( wrappers ) do
		lang[name] = function ( self, ... )
			checkSelf( self, name )
			if #{...} < numArgs then
				error( "too few arguments to mw.language:" .. name, 2 )
			end
			return php[name]( self.code, ... )
		end
	end

	function lang:getCode()
		checkSelf( self, 'getCode' )
		return self.code
	end

	return lang
end

local contLangCode

function language.getContentLanguage()
	if contLangCode == nil then
		contLangCode = php.getContLangCode()
	end
	return language.new( contLangCode )
end

return language
