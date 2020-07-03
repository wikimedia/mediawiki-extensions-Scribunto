-- 
-- Function allowing for consistent treatment of boolean-like wikitext input.
-- It works similarly to the template {{yesno}}.

return function (val, default)
	-- If your wiki uses non-ascii characters for any of "yes", "no", etc., you
	-- should replace "val:lower()" with "mw.ustring.lower(val)" in the
	-- following line.
	val = type(val) == 'string' and val:lower() or val
	if val == nil then
		return nil
	elseif val == true 
		or val == 'yes'
		or val == 'y'
		or val == 'true'
		or val == 't'
		or val == 'да'
		or val == 'д'
        or val == '+'
		or tonumber(val) == 1
	then
		return true
	elseif val == false
		or val == 'no'
		or val == 'n'
		or val == 'false'
		or val == 'f'
		or val == 'нет'
		or val == 'н'
		or val == '-'
		or tonumber(val) == 0
	then
		return false
	else
		return default
	end
end
