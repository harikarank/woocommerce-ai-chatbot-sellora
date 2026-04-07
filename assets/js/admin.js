( function ( $ ) {
	$( function () {

		// ── Color pickers ──────────────────────────────────────────────────────
		$( '.aiwoo-color-field' ).wpColorPicker();

		// ── Media uploader ─────────────────────────────────────────────────────
		$( document ).on( 'click', '.aiwoo-upload-button', function ( event ) {
			event.preventDefault();

			var button   = $( this );
			var targetId = button.data( 'target' );
			var input    = $( '#' + targetId );

			var frame = wp.media( {
				title:    button.data( 'title' ) || 'Select image',
				button:   { text: 'Use image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				input.val( attachment.url );
			} );

			frame.open();
		} );

		// ── Provider credential row visibility ────────────────────────────────
		var providerSelect = document.getElementById( 'ai-woo-assistant-provider' );
		var providerRows   = document.querySelectorAll( '[data-aiwoo-provider]' );

		if ( providerSelect && providerRows.length ) {
			function updateProviderRows() {
				var selected = providerSelect.value;
				providerRows.forEach( function ( row ) {
					row.hidden = row.getAttribute( 'data-aiwoo-provider' ) !== selected;
				} );
			}
			updateProviderRows();
			providerSelect.addEventListener( 'change', updateProviderRows );
		}

		// ── Settings tabs ──────────────────────────────────────────────────────
		var tabLinks     = document.querySelectorAll( '#aiwoo-tab-nav [data-aiwoo-tab]' );
		var tabPanes     = document.querySelectorAll( '.aiwoo-tab-pane' );
		var storageKey   = 'aiwoo_settings_active_tab';

		if ( ! tabLinks.length ) {
			return;
		}

		function activateTab( tabId ) {
			var found = false;

			tabLinks.forEach( function ( link ) {
				var isActive = link.getAttribute( 'data-aiwoo-tab' ) === tabId;
				link.classList.toggle( 'nav-tab-active', isActive );
				if ( isActive ) {
					found = true;
				}
			} );

			// Fall back to first tab if tabId doesn't match anything.
			if ( ! found ) {
				tabId = tabLinks[ 0 ].getAttribute( 'data-aiwoo-tab' );
				tabLinks[ 0 ].classList.add( 'nav-tab-active' );
			}

			tabPanes.forEach( function ( pane ) {
				pane.hidden = pane.id !== 'aiwoo-tab-' + tabId;
			} );

			try {
				localStorage.setItem( storageKey, tabId );
			} catch ( e ) { /* storage unavailable */ }
		}

		// Restore last active tab.
		var saved = '';
		try {
			saved = localStorage.getItem( storageKey ) || '';
		} catch ( e ) { /* storage unavailable */ }

		activateTab( saved || tabLinks[ 0 ].getAttribute( 'data-aiwoo-tab' ) );

		tabLinks.forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				activateTab( link.getAttribute( 'data-aiwoo-tab' ) );
			} );
		} );

	} );
}( jQuery ) );
