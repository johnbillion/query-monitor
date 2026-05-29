import { render } from 'preact';

import { QM } from '../output/qm';
import { Fatal } from '../output/fatal';
import { iSettings } from '../output/panels/panels';
import { MainContextType, DurationUnit } from '../output/contexts/main-context';

import { iQMData, initializeQMData, mergeSettings, registerAllPanels } from './panels';

declare const QueryMonitorData: iQMData;

if ( QueryMonitorData === false ) {
	document.addEventListener( 'DOMContentLoaded', function () {
		const adminMenuElement = document.getElementById( 'wp-admin-bar-query-monitor' );

		if ( adminMenuElement ) {
			adminMenuElement.classList.add( 'qm-error' );
		}

		console.error( 'Query Monitor: Failed to encode output data.' );
	} );
} else {

initializeQMData( QueryMonitorData );
registerAllPanels();

document.addEventListener( 'DOMContentLoaded', function () {
	const fatalElement = document.getElementById( 'qm-fatal' );
	const containerElement = document.getElementById( 'query-monitor-container' );
	const adminMenuElement = document.getElementById( 'wp-admin-bar-query-monitor' );

	if ( fatalElement ) {
		render(
			<Fatal
				adminMenuElement={ adminMenuElement ?? undefined }
			/>,
			fatalElement
		);
		return;
	}

	const isWpAdmin = document.body.classList.contains( 'wp-admin' );
	const isRtl = document.documentElement.dir === 'rtl';
	const panelKey = `qm-${ isWpAdmin ? 'admin' : 'front' }-panel`;
	const positionKey = 'qm-container-position';
	const themeKey = 'qm-theme';
	const fabulousKey = 'qm-fabulous';
	const editorKey = 'qm-editor';
	const filtersKey = 'qm-filters';
	const containerHeightKey = 'qm-container-height';
	const seenKey = 'qm-seen';
	const timelineHiddenKey = 'qm-timeline-hidden';
	const durationUnitKey = 'qm-duration-unit';

	const onPanelChange = ( active: string ) => {
		localStorage.setItem( panelKey, active );

		// Legacy key names:
		localStorage.removeItem( 'qm-admin-container-pinned' );
		localStorage.removeItem( 'qm-front-container-pinned' );
	}

	const onSideChange = ( side: boolean ) => {
		localStorage.setItem( positionKey, ( side ? 'right' : '' ) );
	}

	const onThemeChange = ( theme: string ) => {
		localStorage.setItem( themeKey, theme );
	}

	const onFabulousChange = ( fabulous: boolean ) => {
		if ( fabulous ) {
			localStorage.setItem( fabulousKey, '1' );
		} else {
			localStorage.removeItem( fabulousKey );
		}
	}

	const onEditorChange = ( editor: string ) => {
		localStorage.setItem( editorKey, editor );
	}

	const onFiltersChange = ( filters: MainContextType['filters'] ) => {
		sessionStorage.setItem( filtersKey, JSON.stringify( filters ) );
	}

	const onContainerResize = ( height: number ) => {
		localStorage.setItem( containerHeightKey, height.toString() );
	}

	const onSeenChange = ( panel: string ) => {
		localStorage.setItem( seenKey, panel );
	}

	const onTimelineHiddenChange = ( categories: string[] ) => {
		sessionStorage.setItem( timelineHiddenKey, JSON.stringify( categories ) );
	}

	const onDurationUnitChange = ( unit: string ) => {
		localStorage.setItem( durationUnitKey, unit );
	}

	const active = localStorage.getItem( panelKey ) ?? '';
	const side = localStorage.getItem( positionKey ) === 'right';
	const editor = localStorage.getItem( editorKey ) ?? '';
	const theme = localStorage.getItem( themeKey ) ?? 'auto';
	const fabulous = !! localStorage.getItem( fabulousKey );
	const rawFilters = sessionStorage.getItem( filtersKey );
	const filters = rawFilters ? JSON.parse( rawFilters ) : {};
	const rawContainerHeight = localStorage.getItem( containerHeightKey );
	const containerHeight = rawContainerHeight ? parseFloat( rawContainerHeight ) : null;
	const seen = localStorage.getItem( seenKey ) ?? '';
	const rawTimelineHidden = sessionStorage.getItem( timelineHiddenKey );
	const timelineHiddenCategories: string[] = rawTimelineHidden ? JSON.parse( rawTimelineHidden ) : [];
	const durationUnit = ( localStorage.getItem( durationUnitKey ) ?? 's' ) as DurationUnit;
	const settings: iSettings = mergeSettings( QueryMonitorData );

	if ( ! containerElement ) {
		return;
	}

	// Attach shadow root for CSS isolation
	const shadow = containerElement.attachShadow( { mode: 'open' } );

	// Create a mount point inside the shadow DOM
	const mountPoint = document.createElement( 'div' );
	shadow.appendChild( mountPoint );

	const getBodyClasses = () => ( {
		isFolded: document.body.classList.contains( 'folded' ),
		isAutoFold: document.body.classList.contains( 'auto-fold' ),
		isFullscreenMode: document.body.classList.contains( 'is-fullscreen-mode' ),
	} );

	const renderQM = () => {
		render(
			<QM
				inWP={ true }
				isWpAdmin={ isWpAdmin }
				isRtl={ isRtl }
				active={ active }
				adminMenuElement={ adminMenuElement ?? undefined }
				cssUrl={ containerElement.dataset.cssUrl! }
				menu={ QueryMonitorData.menu }
				panel_menu={ QueryMonitorData.panel_menu }
				data={ QueryMonitorData.data }
				settings={ settings }
				side={ side }
				colorScheme={ settings.color_scheme }
				theme={ theme }
				fabulous={ fabulous }
				editor={ editor }
				filters={ filters }
				containerHeight={ containerHeight }
				onPanelChange={ onPanelChange }
				onContainerResize={ onContainerResize }
				onSideChange={ onSideChange }
				onThemeChange={ onThemeChange }
				onFabulousChange={ onFabulousChange }
				onEditorChange={ onEditorChange }
				onFiltersChange={ onFiltersChange }
				seen={ seen }
				onSeenChange={ onSeenChange }
				timelineHiddenCategories={ timelineHiddenCategories }
				onTimelineHiddenChange={ onTimelineHiddenChange }
				durationUnit={ durationUnit }
				onDurationUnitChange={ onDurationUnitChange }
				{ ...getBodyClasses() }
			/>,
			mountPoint
		);
	};

	renderQM();

	// Watch for body class changes (admin menu fold/unfold) and re-render
	const bodyObserver = new MutationObserver( renderQM );
	bodyObserver.observe( document.body, { attributes: true, attributeFilter: [ 'class' ] } );
} );

} // QueryMonitorData !== false
