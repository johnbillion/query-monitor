import { render } from 'preact';
import { useState, useEffect, useCallback } from 'preact/hooks';

import { QM } from '../../output/qm';
import { DurationUnit } from '../../output/contexts/main-context';

import { iQM, type iQMData, initializeQMData, mergeSettings, registerAllPanels } from '../../src/panels';

/**
 * Wrapper component that manages data fetching from the inspected page
 * and renders the QM UI when data is available.
 */
const ExtensionPanel = () => {
	const [ qmData, setQmData ] = useState<iQM | null>( null );

	const fetchData = useCallback( () => {
		chrome.devtools.inspectedWindow.eval(
			'window.QueryMonitorData',
			( result: iQMData | undefined, exceptionInfo ) => {
				if ( exceptionInfo || ! result ) {
					return;
				}

				initializeQMData( result );
				registerAllPanels();
				setQmData( result );
			},
		);
	}, [] );

	useEffect( () => {
		const tabId = chrome.devtools.inspectedWindow.tabId;
		let port: ReturnType<typeof chrome.runtime.connect>;

		const connect = () => {
			port = chrome.runtime.connect( { name: 'query-monitor-devtools' } );
			port.postMessage( { type: 'query-monitor-init', tabId } );

			port.onMessage.addListener( ( message: { type?: string } ) => {
				if ( message?.type === 'query-monitor-ready' ) {
					fetchData();
				}
			} );

			// Reconnect when the service worker goes idle.
			port.onDisconnect.addListener( () => {
				connect();
			} );
		};

		connect();

		// Fetch immediately in case the page already has data.
		fetchData();

		const onNavigated = () => {
			setQmData( null );
		};

		chrome.devtools.network.onNavigated.addListener( onNavigated );

		return () => {
			port.disconnect();
			chrome.devtools.network.onNavigated.removeListener( onNavigated );
		};
	}, [ fetchData ] );

	if ( ! qmData ) {
		return (
			<div className="qm-waiting">
				<div className="qm-waiting-card">
					<p>Waiting for Query Monitor data&hellip;</p>
					<p>
						Make sure Query Monitor is active on the page you are inspecting.
					</p>
					<p>
						<a
							href="https://querymonitor.com/help/browser-extension/"
							target="_blank"
							rel="noreferrer noopener"
							className="qm-waiting-button"
						>
							Learn more
						</a>
					</p>
				</div>
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
			isWpAdmin={ false }
			isRtl={ false }
			active={ active }
			cssUrl=""
			menu={ qmData.menu }
			panel_menu={ qmData.panel_menu }
			data={ qmData.data }
			settings={ settings }
			side={ true }
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
