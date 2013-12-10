( function ( $, mw ) {

	var scribunto = mw.scribunto = {
		errors: null,

		setErrors: function ( errors ) {
			scribunto.errors = errors;
		},

		init: function () {
			var regex = /^mw-scribunto-error-(\d+)/,
				$dialog = $( '<div>' );

			$dialog.dialog( {
				title: mw.msg( 'scribunto-parser-dialog-title' ),
				autoOpen: false
			} );

			$( '.scribunto-error' ).each( function ( index, span ) {
				var errorId,
					matches = regex.exec( span.id );
				if ( matches === null ) {
					mw.log( 'mw.scribunto.init: regex mismatch!' );
					return;
				}
				errorId = parseInt( matches[1], 10 );
				$( span ).on( 'click', function ( e ) {
					if ( typeof scribunto.errors[ errorId ] !== 'string' ) {
						mw.log( 'mw.scribunto.init: error ' + matches[1] + ' not found, ' +
							'mw.loader.using() callback may not have been called yet.' );
						return;
					}
					var error = scribunto.errors[ errorId ];
					$dialog
						.dialog( 'close' )
						.html( error )
						.dialog( 'option', 'position', [ e.clientX + 5, e.clientY + 5 ] )
						.dialog( 'open' );
				} );
			} );
		}
	};

	$( mw.scribunto.init );

} ) ( jQuery, mediaWiki );

