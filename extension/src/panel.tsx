import { render } from 'preact';
import { useState, useEffect } from 'preact/hooks';

import { QM } from '../../output/qm';
import { DurationUnit } from '../../output/contexts/main-context';

import { iQM, initializeQMData, mergeSettings, registerAllPanels } from '../../src/panels';

/**
 * Wrapper component that manages data fetching from the inspected page
 * and renders the QM UI when data is available.
 */
const ExtensionPanel = () => {
	const [ qmData, setQmData ] = useState<iQM | null>( null );

	useEffect( () => {
		const tabId = chrome.devtools.inspectedWindow.tabId;

		// Connect to the background service worker via a long-lived port.
		const port = chrome.runtime.connect( { name: 'qm-devtools' } );
		port.postMessage( { type: 'qm-init', tabId } );

		const requestData = () => {
			port.postMessage( { type: 'qm-request-data' } );
		};

		port.onMessage.addListener( ( message: { type?: string; data?: iQM } ) => {
			if ( message && message.type === 'qm-content-script-ready' ) {
				// Content script on the new page is ready, request data.
				requestData();
			} else if ( message && message.type === 'qm-data' && message.data ) {
				initializeQMData( message.data );
				registerAllPanels();
				setQmData( message.data );
			}
		} );

		// Request data in case the page loaded before DevTools opened.
		requestData();

		// Clear data when the inspected page navigates.
		// The content script on the new page will announce itself when ready.
		const onNavigated = () => {
			setQmData( null );
		};

		chrome.devtools.network.onNavigated.addListener( onNavigated );

		return () => {
			port.disconnect();
			chrome.devtools.network.onNavigated.removeListener( onNavigated );
		};
	}, [] );

	if ( ! qmData ) {
		return (
			<div style={{ padding: '2em', fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif', color: '#666' }}>
				<p>Waiting for Query Monitor data&hellip;</p>
				<p style={{ fontSize: '0.85em' }}>
					Make sure Query Monitor is active on the page you are inspecting.
				</p>
			</div>
		);
	}

	const settings = mergeSettings( qmData );

	const panelKey = 'qm-extension-panel';
	const themeKey = 'qm-theme';
	const editorKey = 'qm-editor';
	const durationUnitKey = 'qm-duration-unit';

	const active = localStorage.getItem( panelKey ) ?? 'overview';
	const theme = localStorage.getItem( themeKey ) ?? 'auto';
	const editor = localStorage.getItem( editorKey ) ?? '';
	const durationUnit = ( localStorage.getItem( durationUnitKey ) ?? 's' ) as DurationUnit;

	return (
		<QM
			isExtension={ true }
			isWpAdmin={ false }
			isRtl={ false }
			active={ active }
			cssUrl=""
			menu={ qmData.menu }
			panel_menu={ qmData.panel_menu }
			data={ qmData.data }
			settings={ settings }
			side={ false }
			colorScheme={ settings.color_scheme }
			theme={ theme }
			fabulous={ false }
			editor={ editor }
			filters={ {} }
			containerHeight={ null }
			onPanelChange={ ( active ) => localStorage.setItem( panelKey, active ) }
			onContainerResize={ () => {} }
			onSideChange={ () => {} }
			onThemeChange={ ( theme ) => localStorage.setItem( themeKey, theme ) }
			onFabulousChange={ () => {} }
			onEditorChange={ ( editor ) => localStorage.setItem( editorKey, editor ) }
			onFiltersChange={ () => {} }
			seen=""
			onSeenChange={ () => {} }
			timelineHiddenCategories={ [] }
			onTimelineHiddenChange={ () => {} }
			durationUnit={ durationUnit }
			onDurationUnitChange={ ( unit ) => localStorage.setItem( durationUnitKey, unit ) }
			isFolded={ false }
			isAutoFold={ false }
			isFullscreenMode={ false }
		/>
	);
};

const mountPoint = document.getElementById( 'query-monitor-panel' );

if ( mountPoint ) {
	render( <ExtensionPanel />, mountPoint );
}
