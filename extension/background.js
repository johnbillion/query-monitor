/**
 * Background service worker for the Query Monitor browser extension.
 *
 * Relays the "query-monitor-ready" signal from content scripts to the DevTools panel.
 */

/** @type {Map<number, chrome.runtime.Port>} */
const devtoolsPorts = new Map();

// DevTools panels connect here with a port.
chrome.runtime.onConnect.addListener( ( port ) => {
	if ( port.name !== 'query-monitor-devtools' ) {
		return;
	}

	let tabId = null;

	port.onMessage.addListener( ( message ) => {
		if ( message.type === 'query-monitor-init' && message.tabId ) {
			// Register this port for the given tab.
			tabId = message.tabId;
			devtoolsPorts.set( tabId, port );
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
	if ( ! sender.tab?.id || message?.type !== 'query-monitor-ready' ) {
		return;
	}

	const port = devtoolsPorts.get( sender.tab.id );

	if ( ! port ) {
		return;
	}

	if ( port ) {
		port.postMessage( { type: 'query-monitor-ready' } );
	}
} );
