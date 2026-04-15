/**
 * Content script for the Query Monitor browser extension.
 *
 * Bridges data between the page context (where QueryMonitorData lives)
 * and the extension's DevTools panel via chrome.runtime messaging.
 */

// Announce that this content script is ready.
chrome.runtime.sendMessage( { type: 'qm-content-script-ready' } );

// Relay QM data from the page to the extension.
window.addEventListener( 'message', ( event ) => {
	if ( event.source !== window ) {
		return;
	}

	if ( event.data && event.data.type === 'qm-data' ) {
		chrome.runtime.sendMessage( event.data );
	}
} );

// Listen for requests from the DevTools panel to re-send data.
chrome.runtime.onMessage.addListener( ( message ) => {
	if ( message && message.type === 'qm-request-data' ) {
		window.postMessage( { type: 'qm-request-data' }, window.location.origin );
	}
} );
