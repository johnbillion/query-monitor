/**
 * Content script for the Query Monitor browser extension.
 *
 * Listens for a "ready" signal from the page and relays it to the
 * DevTools panel so it knows when to read the data.
 */

// Notify the panel that QM data is available on this page.
window.addEventListener( 'message', ( event ) => {
	if ( event.source !== window ) {
		return;
	}

	if ( event.data?.type === 'query-monitor-ready' ) {
		chrome.runtime.sendMessage( { type: 'query-monitor-ready' } );
	}
} );
