return {
	run = function ( c1, c2, c3, c4, c5 )
		return
			mw.ustring.toNFC( c1 ),
			mw.ustring.toNFC( c2 ),
			mw.ustring.toNFC( c3 ),
			mw.ustring.toNFC( c4 ),
			mw.ustring.toNFC( c5 ),
			mw.ustring.toNFD( c1 ),
			mw.ustring.toNFD( c2 ),
			mw.ustring.toNFD( c3 ),
			mw.ustring.toNFD( c4 ),
			mw.ustring.toNFD( c5 )
	end
}
