import { render } from 'preact';
import { useCallback, useEffect, useRef, useState } from 'preact/hooks';

import { __ } from '@wordpress/i18n';

import { QM } from '../output/qm';
import { Fatal } from '../output/fatal';
import { iSettings } from '../output/panels/panels';
import { MainContextType, DurationUnit } from '../output/contexts/main-context';

import { iPanelData } from '../output/panels/panels';
import { PanelDataMap } from '../output/types';
import { REQUESTS_OVERVIEW_ID } from '../output/request-nav';

import { iQMData, initializeQMData, mergeSettings, reassembleData, registerAllPanels, buildMenus } from './panels';
import { observeQMRequests, DetectedRequest } from './qm-requests';

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

// Observe Ajax (and other) requests for QM data so they can be inspected too.
// Installed early to catch requests before the DOM is ready; detections are
// buffered until the app wires up its handler below.
const detectedBuffer: DetectedRequest[] = [];
let onRequestDetected: ( ( request: DetectedRequest ) => void ) | null = null;
observeQMRequests( ( request ) => {
	if ( onRequestDetected ) {
		onRequestDetected( request );
	} else {
		detectedBuffer.push( request );
	}
}, {
	ajaxurl: QueryMonitorData.l10n.ajaxurl,
} );

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

	// Query Monitor can show data for more than just the main page load: any Ajax
	// (or other) request made during the page's lifetime that carries QM data is
	// added to the request switcher. Each request's data, panel menu and frame
	// lookup are fetched lazily from its own file when it's first viewed.
	type RequestEntry = {
		id: string;
		dataUrl: string;
		label: string;
		method: string;
		path: string;
		statusCode: number | null;
		status: 'idle' | 'loading' | 'loaded' | 'error';
		partial: boolean;
		data: iPanelData | null;
	};

	const MAX_LOADED_REQUESTS = 10;
	const currentId = QueryMonitorData.data_id;

	const initialItems: RequestEntry[] = [ {
		id: currentId,
		dataUrl: QueryMonitorData.data_url ?? '',
		label: __( 'Page load', 'query-monitor' ),
		method: '',
		path: `${ window.location.pathname }${ window.location.search }`,
		statusCode: QueryMonitorData.status_code ?? null,
		status: 'idle',
		partial: false,
		data: null,
	} ];

	const urlPath = ( url: string ): string => {
		try {
			const u = new URL( url, window.location.origin );
			return `${ u.pathname }${ u.search }`;
		} catch {
			return url;
		}
	};

	const urlPathForDisplay = ( url: string ): string => {
		try {
			const u = new URL( url, window.location.origin );

			if ( u.pathname === '/' && u.search ) {
				return `${ u.pathname }${ u.search }`;
			}

			return u.pathname;
		} catch {
			return url;
		}
	};

	const showDataError = ( message: string ) => {
		if ( adminMenuElement ) {
			adminMenuElement.classList.add( 'qm-error' );
		}
		console.error( message );
	};

	type AppProps = {
		active: string;
		initialItems: RequestEntry[];
		currentId: string;
		isWpAdmin: boolean;
		isRtl: boolean;
		adminMenuElement?: HTMLElement;
		cssUrl: string;
		settings: iSettings;
		side: boolean;
		theme: string;
		fabulous: boolean;
		editor: string;
		filters: MainContextType['filters'];
		containerHeight: number | null;
		seen: string;
		timelineHiddenCategories: string[];
		durationUnit: DurationUnit;
		onPanelChange: ( active: string ) => void;
		onSideChange: ( side: boolean ) => void;
		onThemeChange: ( theme: string ) => void;
		onFabulousChange: ( fabulous: boolean ) => void;
		onEditorChange: ( editor: string ) => void;
		onFiltersChange: ( filters: MainContextType['filters'] ) => void;
		onContainerResize: ( height: number ) => void;
		onSeenChange: ( panel: string ) => void;
		onTimelineHiddenChange: ( categories: string[] ) => void;
		onDurationUnitChange: ( unit: string ) => void;
	};

	const App = ( props: AppProps ) => {
		const {
			active,
			initialItems,
			currentId,
			isWpAdmin,
			isRtl,
			adminMenuElement,
			cssUrl,
			settings,
			side,
			theme,
			fabulous,
			editor,
			filters,
			containerHeight,
			seen,
			timelineHiddenCategories,
			durationUnit,
			onPanelChange,
			onSideChange,
			onThemeChange,
			onFabulousChange,
			onEditorChange,
			onFiltersChange,
			onContainerResize,
			onSeenChange,
			onTimelineHiddenChange,
			onDurationUnitChange,
		} = props;

		const [ items, setItems ] = useState<RequestEntry[]>( initialItems );
		const [ activeRequestId, setActiveRequestId ] = useState<string>( currentId );
		const [ paused, setPaused ] = useState<boolean>( false );
		const [ bodyClasses, setBodyClasses ] = useState( getBodyClasses() );

		// Refs mirror the latest state so callbacks registered once (in effects,
		// or via useCallback with empty deps) never act on stale values.
		const itemsRef = useRef( items );
		const activeRequestIdRef = useRef( activeRequestId );
		const pausedRef = useRef( paused );

		// Ids of requests whose data is currently held in state, oldest first.
		// Capped at MAX_LOADED_REQUESTS; the oldest are evicted back to 'idle'
		// (their large data payloads dropped) and re-fetched if viewed again.
		const loadOrderRef = useRef<string[]>( [] );

		useEffect( () => {
			itemsRef.current = items;
			activeRequestIdRef.current = activeRequestId;
			pausedRef.current = paused;
		} );

		// Fetch a request's data file the first time it's needed.
		const loadRequest = useCallback( ( id: string ) => {
			const request = itemsRef.current.find( ( item ) => item.id === id );

			if ( ! request || request.status === 'loading' || request.status === 'loaded' ) {
				return;
			}

			if ( ! request.dataUrl ) {
				setItems( ( prev ) => prev.map( ( item ) =>
					item.id === id ? { ...item, status: 'error' } : item
				) );
				showDataError( 'Query Monitor: No data available for this request.' );
				return;
			}

			setItems( ( prev ) => prev.map( ( item ) =>
				item.id === id ? { ...item, status: 'loading' } : item
			) );

			fetch( request.dataUrl )
				.then( ( response ) => {
					if ( ! response.ok ) {
						throw new Error( `Query Monitor: data request failed (HTTP ${ response.status }).` );
					}
					return response.text();
				} )
				.then( ( text ) => {
					const reassembled = reassembleData( text );

					// Record this id as the most recently loaded and evict the
					// oldest entries once the cache exceeds its cap.
					const order = loadOrderRef.current.filter( ( loadedId ) => loadedId !== id );
					order.push( id );
					const evicted = new Set<string>();
					while ( order.length > MAX_LOADED_REQUESTS ) {
						const oldest = order.shift();
						if ( oldest !== undefined ) {
							evicted.add( oldest );
						}
					}
					loadOrderRef.current = order;

					setItems( ( prev ) => prev.map( ( item ) => {
						if ( item.id === id ) {
							return {
								...item,
								data: reassembled.data,
								partial: reassembled.partial,
								status: 'loaded',
							};
						}
						if ( evicted.has( item.id ) ) {
							return {
								...item,
								data: null,
								partial: false,
								status: 'idle',
							};
						}
						return item;
					} ) );
				} )
				.catch( ( error ) => {
					setItems( ( prev ) => prev.map( ( item ) =>
						item.id === id ? { ...item, status: 'error' } : item
					) );
					showDataError( `Query Monitor: failed to load data. (${ error })` );
				} );
		}, [] );

		const handlePanelChange = useCallback( ( panel: string ) => {
			onPanelChange( panel );
			if ( panel ) {
				loadRequest( activeRequestIdRef.current );
			}
		}, [ onPanelChange, loadRequest ] );

		const handleRequestChange = useCallback( ( id: string ) => {
			setActiveRequestId( id );
			loadRequest( id );
		}, [ loadRequest ] );

		const handlePauseToggle = useCallback( () => {
			setPaused( ( p ) => ! p );
		}, [] );

		const handleClear = useCallback( () => {
			setItems( ( prev ) => {
				const pageLoad = prev.find( ( item ) => item.id === currentId );
				return pageLoad ? [ pageLoad ] : [];
			} );

			if ( activeRequestIdRef.current !== REQUESTS_OVERVIEW_ID && activeRequestIdRef.current !== currentId ) {
				setActiveRequestId( currentId );
				loadRequest( currentId );
			}
		}, [ currentId, loadRequest ] );

		const addDetectedRequest = useCallback( ( detected: DetectedRequest ) => {
			if ( pausedRef.current ) {
				return;
			}

			setItems( ( prev ) => {
				if ( prev.some( ( item ) => item.id === detected.id ) ) {
					return prev;
				}

				return [ ...prev, {
					id: detected.id,
					dataUrl: detected.dataUrl,
					label: urlPathForDisplay( detected.requestUrl ),
					method: detected.method,
					path: urlPath( detected.requestUrl ),
					statusCode: detected.statusCode,
					status: 'idle',
					partial: false,
					data: null,
				} ];
			} );
		}, [] );

		useEffect( () => {
			onRequestDetected = addDetectedRequest;
			detectedBuffer.forEach( addDetectedRequest );
			detectedBuffer.length = 0;

			return () => {
				onRequestDetected = null;
			};
		}, [ addDetectedRequest ] );

		useEffect( () => {
			const observer = new MutationObserver( () => setBodyClasses( getBodyClasses() ) );
			observer.observe( document.body, { attributes: true, attributeFilter: [ 'class' ] } );
			return () => observer.disconnect();
		}, [] );

		const activeRequest = items.find( ( item ) => item.id === activeRequestId );

		const { menu, panel_menu } = buildMenus(
			QueryMonitorData,
			( activeRequest?.data ?? {} ) as PanelDataMap,
		);

		const requestList = items.map( ( item ) => ( {
			id: item.id,
			label: item.label,
			status: item.status,
			method: item.method,
			path: item.path,
			statusCode: item.statusCode,
		} ) );

		return (
			<QM
				inWP={ true }
				isWpAdmin={ isWpAdmin }
				isRtl={ isRtl }
				active={ active }
				adminMenuElement={ adminMenuElement }
				cssUrl={ cssUrl }
				menu={ menu }
				panel_menu={ panel_menu }
				data={ activeRequest?.data ?? null }
				partial={ activeRequest?.partial ?? false }
				requests={ requestList }
				activeRequestId={ activeRequestId }
				pageLoadId={ currentId }
				isMainPageLoad={ activeRequestId === currentId }
				onRequestChange={ handleRequestChange }
				paused={ paused }
				onPauseToggle={ handlePauseToggle }
				onClear={ handleClear }
				settings={ settings }
				side={ side }
				colorScheme={ settings.color_scheme }
				theme={ theme }
				fabulous={ fabulous }
				editor={ editor }
				filters={ filters }
				containerHeight={ containerHeight }
				onPanelChange={ handlePanelChange }
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
				{ ...bodyClasses }
			/>
		);
	};

	render(
		<App
			active={ active }
			initialItems={ initialItems }
			currentId={ currentId }
			isWpAdmin={ isWpAdmin }
			isRtl={ isRtl }
			adminMenuElement={ adminMenuElement ?? undefined }
			cssUrl={ containerElement.dataset.cssUrl! }
			settings={ settings }
			side={ side }
			theme={ theme }
			fabulous={ fabulous }
			editor={ editor }
			filters={ filters }
			containerHeight={ containerHeight }
			seen={ seen }
			timelineHiddenCategories={ timelineHiddenCategories }
			durationUnit={ durationUnit }
			onPanelChange={ onPanelChange }
			onSideChange={ onSideChange }
			onThemeChange={ onThemeChange }
			onFabulousChange={ onFabulousChange }
			onEditorChange={ onEditorChange }
			onFiltersChange={ onFiltersChange }
			onContainerResize={ onContainerResize }
			onSeenChange={ onSeenChange }
			onTimelineHiddenChange={ onTimelineHiddenChange }
			onDurationUnitChange={ onDurationUnitChange }
		/>,
		mountPoint
	);
} );

} // QueryMonitorData !== false
