( function ( $ ) {
	$( function () {
		$( '.aiwoo-color-field' ).wpColorPicker();

		$( document ).on( 'click', '.aiwoo-upload-button', function ( event ) {
			event.preventDefault();

			const button = $( this );
			const targetId = button.data( 'target' );
			const input = $( '#' + targetId );

			const frame = wp.media( {
				title: 'Select image',
				button: {
					text: 'Use image',
				},
				multiple: false,
			} );

			frame.on( 'select', function () {
				const attachment = frame.state().get( 'selection' ).first().toJSON();
				input.val( attachment.url );
			} );

			frame.open();
		} );
	} );
}( jQuery ) );
