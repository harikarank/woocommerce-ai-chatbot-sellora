( function () {
	const config = window.AIWooAssistant;

	if ( ! config || ! config.ajaxUrl || ! config.actions ) {
		return;
	}

	const root = document.querySelector( '[data-aiwoo-widget]' );

	if ( ! root ) {
		return;
	}

	const state = {
		messages: [],
		isOpen: false,
	};

	const storageKey  = config.widgetStateKey || 'ai_woo_assistant_widget_state';
	const sessionKey  = 'aiwoo_session_id';

	function getOrCreateSessionId() {
		let id = window.sessionStorage.getItem( sessionKey );
		if ( ! id ) {
			id = 'aiwoo-' + Date.now().toString( 36 ) + '-' + Math.random().toString( 36 ).substring( 2, 10 );
			window.sessionStorage.setItem( sessionKey, id );
		}
		return id;
	}
	const elements = {
		root,
		panel: root.querySelector( '.aiwoo-panel' ),
		messages: root.querySelector( '.aiwoo-messages' ),
		loading: root.querySelector( '.aiwoo-loading' ),
		form: root.querySelector( '.aiwoo-form' ),
		input: root.querySelector( '.aiwoo-input' ),
		send: root.querySelector( '.aiwoo-send' ),
		launcher: root.querySelector( '.aiwoo-launcher' ),
		close: root.querySelector( '.aiwoo-close' ),
	};

	function loadState() {
		try {
			const saved = JSON.parse( window.sessionStorage.getItem( storageKey ) || '{}' );
			state.messages = Array.isArray( saved.messages ) ? saved.messages : [];
			state.isOpen = Boolean( saved.isOpen );
		} catch ( error ) {
			state.messages = [];
			state.isOpen = false;
		}
	}

	function persistState() {
		window.sessionStorage.setItem(
			storageKey,
			JSON.stringify( {
				messages: state.messages.slice( -12 ),
				isOpen: state.isOpen,
			} )
		);
	}

	function addMessage( role, content, options = {} ) {
		state.messages.push( {
			role,
			content,
			html: Boolean( options.html ),
			enquiryForm: Boolean( options.enquiryForm ),
			enquiryFormHtml: options.enquiryFormHtml || '',
		} );
		renderMessages();
		persistState();
	}

	function createAvatar( role ) {
		const avatar = document.createElement( 'div' );
		avatar.className = 'aiwoo-avatar aiwoo-avatar--' + role;

		if ( role === 'assistant' && config.ui.employeePhoto ) {
			const img = document.createElement( 'img' );
			img.src = config.ui.employeePhoto;
			img.alt = '';
			avatar.appendChild( img );
		} else if ( role === 'user' ) {
			avatar.innerHTML =
				'<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4Z"/></svg>';
		}

		return avatar;
	}

	function createMessageRow( role, content, options = {} ) {
		const row = document.createElement( 'div' );
		row.className = 'aiwoo-message-row aiwoo-message-row--' + role;

		const avatar = createAvatar( role );
		const item = document.createElement( 'article' );
		item.className = 'aiwoo-message aiwoo-message--' + role;

		if ( options.html ) {
			item.innerHTML = content;
		} else {
			item.textContent = content;
		}

		if ( role === 'assistant' ) {
			row.appendChild( avatar );
			row.appendChild( item );
		} else {
			row.appendChild( item );
			row.appendChild( avatar );
		}

		return row;
	}

	function renderMessages() {
		elements.messages.innerHTML = '';

		if ( state.messages.length === 0 ) {
			const row = createMessageRow( 'assistant', config.strings.welcome );
			elements.messages.appendChild( row );
			return;
		}

		state.messages.forEach( ( message ) => {
			const row = createMessageRow( message.role, message.content, { html: message.html } );
			elements.messages.appendChild( row );

			if ( message.enquiryForm && message.enquiryFormHtml ) {
				const wrapper = document.createElement( 'div' );
				wrapper.innerHTML = message.enquiryFormHtml;

				if ( wrapper.firstElementChild ) {
					const enquiryForm = wrapper.firstElementChild.querySelector( '.aiwoo-enquiry-form' );
					if ( enquiryForm ) {
						enquiryForm.addEventListener( 'submit', handleEnquirySubmit );
					}
					elements.messages.appendChild( wrapper.firstElementChild );
				}
			}
		} );

		elements.messages.scrollTop = elements.messages.scrollHeight;
	}

	function setOpen( isOpen ) {
		state.isOpen = isOpen;
		elements.panel.setAttribute( 'aria-hidden', String( ! isOpen ) );
		elements.launcher.setAttribute( 'aria-expanded', String( isOpen ) );
		elements.root.classList.toggle( 'is-open', isOpen );
		persistState();

		if ( isOpen ) {
			window.setTimeout( function () {
				elements.input.focus();
				adjustTextareaHeight();
			}, 150 );
		}
	}

	function setLoading( isLoading ) {
		elements.loading.hidden = ! isLoading;
		elements.root.classList.toggle( 'is-loading', isLoading );
		elements.send.disabled = isLoading;
		elements.input.disabled = isLoading;
		if ( isLoading ) {
			elements.messages.scrollTop = elements.messages.scrollHeight;
		}
	}

	function adjustTextareaHeight() {
		elements.input.style.height = 'auto';
		elements.input.style.height = Math.min( elements.input.scrollHeight, 120 ) + 'px';
	}

	async function postAjax( action, payload ) {
		const formData = new window.FormData();
		formData.append( 'action', action );
		formData.append( 'nonce', config.nonce );

		Object.keys( payload ).forEach( ( key ) => formData.append( key, payload[ key ] ) );

		const response = await window.fetch( config.ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} );
		const json = await response.json();

		if ( ! response.ok || ! json.success ) {
			throw new Error( json?.data?.message || config.strings.error );
		}

		return json.data;
	}

	async function handleSend( rawMessage ) {
		const message = rawMessage.trim();

		if ( ! message ) {
			window.alert( config.strings.emptyValidation );
			return;
		}

		addMessage( 'user', message );
		setLoading( true );

		try {
			const payload = await postAjax( config.actions.chat, {
				message,
				session_id: getOrCreateSessionId(),
				history: JSON.stringify( state.messages.map( ( item ) => ( { role: item.role, content: item.content } ) ) ),
				pageContext: JSON.stringify( config.storeContext ),
			} );

			addMessage( 'assistant', payload.message, {
				html: Boolean( payload.html ),
				enquiryForm: Boolean( payload.enquiry_form ),
				enquiryFormHtml: payload.enquiry_form_html || '',
			} );
		} catch ( error ) {
			addMessage( 'assistant', error.message || config.strings.error );
		} finally {
			setLoading( false );
		}
	}

	async function handleEnquirySubmit( event ) {
		event.preventDefault();
		const form = event.currentTarget;

		try {
			const payload = await postAjax( config.actions.enquiry, {
				name: form.name.value.trim(),
				phone: form.phone.value.trim(),
				email: form.email.value.trim(),
				message: form.message.value.trim(),
				session_id: getOrCreateSessionId(),
			} );
			addMessage( 'assistant', payload.message );
			form.closest( '.aiwoo-enquiry' )?.remove();
		} catch ( error ) {
			addMessage( 'assistant', error.message || config.strings.error );
		}
	}

	// ── Character counter ────────────────────────────────────────────────────
	const maxLen = ( config.settings && config.settings.maxMessageLength ) || 200;
	elements.input.setAttribute( 'maxlength', String( maxLen ) );

	const counter = document.createElement( 'div' );
	counter.className = 'aiwoo-char-counter';

	const clearBtn = document.createElement( 'button' );
	clearBtn.type = 'button';
	clearBtn.className = 'aiwoo-clear';
	clearBtn.textContent = 'Clear chat history';
	counter.appendChild( clearBtn );

	const countSpan = document.createElement( 'span' );
	countSpan.setAttribute( 'aria-live', 'polite' );
	countSpan.setAttribute( 'aria-atomic', 'true' );
	counter.appendChild( countSpan );

	elements.form.parentNode.insertBefore( counter, elements.form );

	clearBtn.addEventListener( 'click', function () {
		state.messages = [];
		window.sessionStorage.removeItem( storageKey );
		window.sessionStorage.removeItem( sessionKey );
		renderMessages();
	} );

	function updateCounter() {
		const used      = elements.input.value.length;
		const remaining = maxLen - used;
		countSpan.textContent = remaining + ' / ' + maxLen;
		const nearLimit = remaining <= Math.max( 20, Math.floor( maxLen * 0.15 ) );
		counter.classList.toggle( 'is-near-limit', nearLimit && remaining > 0 );
		counter.classList.toggle( 'is-at-limit', remaining <= 0 );
	}

	loadState();
	renderMessages();
	setLoading( false );
	setOpen( state.isOpen );

	elements.launcher.addEventListener( 'click', function () {
		setOpen( ! state.isOpen );
	} );

	elements.close.addEventListener( 'click', function () {
		setOpen( false );
	} );

	elements.form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		const value = elements.input.value;
		elements.input.value = '';
		adjustTextareaHeight();
		handleSend( value );
	} );

	elements.input.addEventListener( 'input', function () {
		adjustTextareaHeight();
		updateCounter();
	} );

	elements.input.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Enter' && ! event.shiftKey ) {
			event.preventDefault();
			elements.form.requestSubmit();
		}
	} );

	adjustTextareaHeight();
	updateCounter();
}() );
