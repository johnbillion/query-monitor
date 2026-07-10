import { render } from 'preact';
import { useState, useEffect } from 'preact/hooks';

import { QM } from '../../output/qm';
import { DurationUnit } from '../../output/contexts/main-context';

import { iQM, type iQMData, initializeQMData, mergeSettings, registerAllPanels, buildMenus } from '../../src/panels';

/**
 * Wrapper component that manages data fetching from the inspected page
 * and renders the QM UI when data is available.
 */
const ExtensionPanel = () => {
	const [ qmData, setQmData ] = useState<iQM | null>( null );

	useEffect( () => {
		let cancelled = false;
		let timer: ReturnType<typeof setTimeout> | null = null;

		const poll = () => {
			chrome.devtools.inspectedWindow.eval(
				'window.QueryMonitorData',
				( result: iQMData | undefined, exceptionInfo ) => {
					if ( cancelled ) {
						return;
					}

					if ( exceptionInfo || ! result ) {
						timer = setTimeout( poll, 500 );
						return;
					}

					initializeQMData( result );
					registerAllPanels();
					setQmData( result );
				},
			);
		};

		poll();

		const onNavigated = () => {
			setQmData( null );
			if ( timer ) {
				clearTimeout( timer );
			}
			poll();
		};

		chrome.devtools.network.onNavigated.addListener( onNavigated );

		return () => {
			cancelled = true;
			if ( timer ) {
				clearTimeout( timer );
			}
			chrome.devtools.network.onNavigated.removeListener( onNavigated );
		};
	}, [] );

	if ( ! qmData ) {
		return (
			<div className="qm-waiting">
				<div className="qm-waiting-card">
					<p>Waiting for Query Monitor data&hellip;</p>
					<hr/>
					<p>
						Make sure the Query Monitor plugin is active on the page you are inspecting, and you have permission to view its output.
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
	const { menu, panel_menu } = buildMenus( qmData );

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
			menu={ menu }
			panel_menu={ panel_menu }
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
			queryDiffEnabled={ false }
			onQueryDiffEnabledChange={ () => {} }
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
