<?php

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;

class LuaCommonTestsFailLibrary extends LibraryBase {
	public function __construct() {
		throw new MWException( 'deferLoad library that is never required was loaded anyway' );
	}

	public function register() {
	}
}
