/**
 * Background service worker for the Query Monitor browser extension.
 *
 * Relays messages between content scripts and DevTools panels.
 * DevTools panels can't reliably receive chrome.runtime.sendMessage
 * from content scripts, so this service worker acts as a hub.
 */

/** @type {Map<number, chrome.runtime.Port>} */
const devtoolsPorts = new Map();

// DevTools panels connect here with a port.
chrome.runtime.onConnect.addListener( ( port ) => {
	if ( port.name !== 'qm-devtools' ) {
		return;
	}

	let tabId = null;

	port.onMessage.addListener( ( message ) => {
		if ( message.type === 'qm-init' && message.tabId ) {
			// Register this port for the given tab.
			tabId = message.tabId;
			devtoolsPorts.set( tabId, port );
		} else if ( message.type === 'qm-request-data' && tabId ) {
			// Relay data request to the content script.
			chrome.tabs.sendMessage( tabId, { type: 'qm-request-data' } ).catch( () => {
				// Content script not ready yet, ignore.
			} );
		}
	} );

	port.onDisconnect.addListener( () => {
		if ( tabId ) {
			devtoolsPorts.delete( tabId );
		}
	} );
} );

// Content scripts send messages here; relay to the matching DevTools port.
chrome.runtime.onMessage.addListener( ( message, sender ) => {
	if ( ! sender.tab || ! sender.tab.id ) {
		return;
	}

	const port = devtoolsPorts.get( sender.tab.id );

	if ( ! port ) {
		return;
	}

	if ( message && ( message.type === 'qm-data' || message.type === 'qm-content-script-ready' ) ) {
		port.postMessage( message );
	}
} );
