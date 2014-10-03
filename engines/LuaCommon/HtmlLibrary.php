<?php

class Scribunto_LuaHtmlLibrary extends Scribunto_LuaLibraryBase {
	function register() {
		return $this->getEngine()->registerInterface( 'mw.html.lua', array() );
	}
}
