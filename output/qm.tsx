import clsx from 'clsx';
import { Icon, IconDefs } from './components/icon';
import { MainContext, MainContextType, DurationUnit } from './contexts/main-context';
import { Fragment, type ComponentChildren } from 'preact';
import { createPortal } from 'preact/compat';
import { useState, useEffect, useRef, useMemo } from 'preact/hooks';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelData, iSettings } from './panels/panels';

const devCssUrl = import.meta.env.DEV
	? new URL( import.meta.url ).origin + '/assets/query-monitor.css'
	: '';

/**
 * Minimal port of WordPress's `createInterpolateElement` that only understands
 * the `<small>` tag, the only markup a menu title is expected to contain.
 */
const createInterpolateElement = ( text: string ): ComponentChildren => (
	text.split( /(<small>.*?<\/small>)/g ).map( ( part, i ) => {
		const match = part.match( /^<small>(.*?)<\/small>$/ );

		return match ? <small key={ i }>{ match[ 1 ] }</small> : part;
	} )
);

type Props = {
	active: string;
	adminMenuElement?: HTMLElement;
	cssUrl: string;
	menu: {
		top: {
			title: string[];
			classname: string;
		};
		sub: {
			[k: string]: {
				id: string;
				panel: string;
				title: string;
				meta?: {
					classname: string;
				}
			}
		}
	};
	data: iPanelData;
	settings: iSettings;
	panel_menu: iNavMenu;
	side: boolean;
	colorScheme: 'fresh' | 'modern';
	theme: string;
	fabulous: boolean;
	editor: string;
	filters: MainContextType['filters'];
	seen: string;
	containerHeight: number | null;
	isWpAdmin: boolean;
	isFolded: boolean;
	isAutoFold: boolean;
	isRtl: boolean;
	isFullscreenMode: boolean;
	inWP?: boolean;
	onPanelChange: ( active: string ) => void;
	onContainerResize: ( height: number ) => void;
	onSideChange: ( side: boolean ) => void;
	onThemeChange: ( theme: string ) => void;
	onFabulousChange: ( fabulous: boolean ) => void;
	onEditorChange: ( editor: string ) => void;
	onFiltersChange: ( filters: MainContextType['filters'] ) => void;
	onSeenChange: ( panel: string ) => void;
	timelineHiddenCategories: string[];
	onTimelineHiddenChange: ( categories: string[] ) => void;
	durationUnit: DurationUnit;
	onDurationUnitChange: ( unit: DurationUnit ) => void;
}

export const QM = ( props: Props ) => {
	const [ active, setActive ] = useState( props.active );
	const [ side, setSide ] = useState( props.side );
	const [ theme, setTheme ] = useState( props.theme );
	const [ fabulous, setFabulous ] = useState( props.fabulous );
	const [ editor, setEditor ] = useState( props.editor );
	const [ filters, setFilters ] = useState( props.filters );
	const [ seen, setSeen ] = useState( props.seen );
	const [ timelineHiddenCategories, setTimelineHiddenCategories ] = useState( props.timelineHiddenCategories );
	const [ durationUnit, setDurationUnit ] = useState( props.durationUnit );
	const [ verified, setVerified ] = useState( props.settings.verified );
	const [ jumpToRow, setJumpToRow ] = useState<MainContextType['jumpToRow']>( null );
	const jumpToRowRef = useRef( jumpToRow );
	jumpToRowRef.current = jumpToRow;

	const handleSeenChange = ( panel: string ) => {
		props.onSeenChange( panel );
		setSeen( panel );
	};

	const handleTimelineHiddenChange = ( categories: string[] ) => {
		props.onTimelineHiddenChange( categories );
		setTimelineHiddenCategories( categories );
	};

	const newPanels = useMemo( () => {
		const panels = new Set<string>();
		const collect = ( menu: iNavMenu ) => {
			for ( const item of Object.values( menu ) ) {
				if ( item.new ) {
					panels.add( item.panel );
				}
				if ( item.children ) {
					collect( item.children );
				}
			}
		};
		collect( props.panel_menu );
		return panels;
	}, [ props.panel_menu ] );

	const setActivePanel = ( active: string ) => {
		setActive( active );
		if ( newPanels.has( active ) && active !== seen ) {
			handleSeenChange( active );
		}
		// @TODO focus the panel for a11y
	};

	const adminMenuElement = props.adminMenuElement;

	let actualTheme = theme;

	if ( ! [ 'light', 'dark' ].includes( actualTheme ) ) {
		actualTheme = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches
			? 'dark'
			: 'light';
	}

	const inWP = props.inWP ?? false;

	const mainClass = clsx( 'qm-container', 'qm-show', {
		'qm-show-right': side,
		'qm-standalone': ! inWP,
		'wp-admin': props.isWpAdmin,
		'folded': props.isFolded,
		'auto-fold': props.isAutoFold,
		'fullscreen-mode': props.isFullscreenMode,
		'rtl': props.isRtl,
	} );

	const contextValue: MainContextType = {
		theme: theme,
		setTheme: ( theme: string ) => {
			props.onThemeChange( theme );
			setTheme( theme );
		},
		fabulous: fabulous,
		setFabulous: ( fabulous: boolean ) => {
			props.onFabulousChange( fabulous );
			setFabulous( fabulous );
		},
		editor: editor,
		setEditor: ( editor: string ) => {
			props.onEditorChange( editor );
			setEditor( editor );
		},
		filters: filters,
		setFilters: ( filters: MainContextType['filters'] ) => {
			props.onFiltersChange( filters );
			setFilters( filters );
		},
		switchToPanel: ( panelId: string, panelFilters?: MainContextType['filters'][string], rowIndex?: number ) => {
			setActivePanel( panelId );
			setFilters( ( prev ) => {
				const newFilters = { ...prev };
				if ( panelFilters && Object.keys( panelFilters ).length > 0 ) {
					newFilters[ panelId ] = panelFilters;
				} else {
					delete newFilters[ panelId ];
				}
				props.onFiltersChange( newFilters );
				return newFilters;
			} );
			setJumpToRow( rowIndex !== undefined ? { panel: panelId, row: rowIndex } : null );
		},
		jumpToRow,
		timelineHiddenCategories: timelineHiddenCategories,
		setTimelineHiddenCategories: handleTimelineHiddenChange,
		durationUnit: durationUnit,
		setDurationUnit: ( unit: DurationUnit ) => {
			props.onDurationUnitChange( unit );
			setDurationUnit( unit );
		},
		verified,
		setVerified,
		settings: {
			file_path_map: props.settings.file_path_map,
			file_link_format: props.settings.file_link_format,
			abspath: props.settings.abspath,
			contentpath: props.settings.contentpath,
		},
	};

	// Apply the menu class (qm-warning, qm-notice, etc.) to the admin bar element
	useEffect( () => {
		if ( ! inWP ) {
			return;
		}

		if ( adminMenuElement && props.menu.top.classname ) {
			// Split the classname string and add each class
			const classes = props.menu.top.classname.split( ' ' ).filter( Boolean );
			classes.forEach( ( cls ) => adminMenuElement.classList.add( cls ) );

			return () => {
				classes.forEach( ( cls ) => adminMenuElement.classList.remove( cls ) );
			};
		}
	}, [ inWP, adminMenuElement, props.menu.top.classname ] );

	const mainRef = useRef<HTMLDivElement>( null );
	const { onContainerResize, onPanelChange, containerHeight } = props;
	const initialHeightApplied = useRef( false );

	useEffect( () => {
		onPanelChange( active );
		if ( jumpToRowRef.current === null ) {
			mainRef.current?.querySelector( '#qm-panels' )?.scrollTo( 0, 0 );
		} else {
			jumpToRowRef.current = null;
		}
	}, [ active, onPanelChange ] );

	useEffect( () => {
		if ( ! active || ! inWP ) {
			return;
		}

		const adminToolbarHeight = document.getElementById( 'wpadminbar' )?.offsetHeight ?? 0;
		let dragging = false;

		const qmMain = mainRef.current;

		if ( ! qmMain ) {
			return;
		}

		const el = qmMain.getElementsByClassName( 'qm-resizer' )[0];

		if ( containerHeight && ! initialHeightApplied.current ) {
			const maxHeight = window.innerHeight - adminToolbarHeight;
			qmMain.style.height = `${ Math.min( containerHeight, maxHeight ) }px`;
			initialHeightApplied.current = true;
		}

		let startY = 0;
		let startHeight = 0;

		const start = (event: PointerEvent) => {
			if ( event.button !== 0 ) {
				return;
			}

			startY = event.clientY;
			startHeight = qmMain.getBoundingClientRect().height;

			dragging = true;
			el.setPointerCapture(event.pointerId);
		}

		const move = (event: PointerEvent) => {
			if (!dragging) {
				return;
			}

			const windowHeight = window.innerHeight;
			let newHeight = startHeight - ( event.clientY - startY );

			newHeight = Math.max( 27, newHeight );
			newHeight = Math.min( windowHeight - adminToolbarHeight, newHeight );

			qmMain.style.height = `${ newHeight }px`;
		}

		const end = (_event: PointerEvent) => {
			if ( dragging ) {
				const rect = qmMain.getBoundingClientRect();
				onContainerResize( rect.height );
			}

			dragging = false;
		}

		const preventTouch = (e: TouchEvent) => e.preventDefault();

		const onWindowResize = () => {
			const maxHeight = window.innerHeight - adminToolbarHeight;
			if ( qmMain.getBoundingClientRect().height > maxHeight ) {
				qmMain.style.height = `${ maxHeight }px`;
			}
		};

		el.addEventListener( 'pointerdown', start );
		el.addEventListener( 'pointermove', move );
		el.addEventListener( 'pointerup', end );
		el.addEventListener( 'pointercancel', end );
		el.addEventListener( 'touchstart', preventTouch, { passive: false } );
		window.addEventListener( 'resize', onWindowResize );

		return () => {
			el.removeEventListener( 'pointerdown', start );
			el.removeEventListener( 'pointermove', move );
			el.removeEventListener( 'pointerup', end );
			el.removeEventListener( 'pointercancel', end );
			el.removeEventListener( 'touchstart', preventTouch );
			window.removeEventListener( 'resize', onWindowResize );
		};
	}, [ active, inWP, containerHeight, onContainerResize ] );

	const [ cssVersion, setCssVersion ] = useState( 0 );

	useEffect( () => {
		if ( import.meta.hot ) {
			const onUpdate = () => setCssVersion( ( v ) => v + 1 );
			import.meta.hot.on( 'qm:css-update', onUpdate );
			return () => import.meta.hot!.off( 'qm:css-update', onUpdate );
		}
	}, [] );

	const cssUrl = import.meta.env.DEV
		? `${ devCssUrl }?t=${ cssVersion }`
		: props.cssUrl;

	// In extension mode, always show a panel (default to overview).
	const effectiveActive = inWP ? active : ( active || 'overview' );

	return (
		<MainContext.Provider value={ contextValue }>
			<IconDefs />
			{ inWP && (
				<link rel="stylesheet" href={ cssUrl } />
			) }
			{ effectiveActive && (
				<div ref={ mainRef } className={ mainClass } data-theme={ actualTheme } data-color-scheme={ props.colorScheme } dir="ltr" id="query-monitor-main">
					<div id="qm-title" className={ clsx( { 'qm-fabulous': fabulous } ) }>
						<h1 className={ clsx( 'qm-title-heading', { 'qm-resizer': inWP } ) }>
							<span>
								{ __( 'Query Monitor', 'query-monitor' ) }
							</span>
						</h1>
						{ side && (
							<div className="qm-title-heading">
								<NavSelect active={ effectiveActive } menu={ props.panel_menu } onSwitch={ setActivePanel } />
							</div>
						) }
						<button
							aria-label={ __( 'Settings', 'query-monitor' ) }
							className="qm-button-container-settings"
							onClick={ () => {
								setActivePanel( 'settings' );
							} }
						>
							<Icon name="admin-generic"/>
						</button>
						{ inWP && (
							<button
								aria-label={ __( 'Toggle panel position', 'query-monitor' ) }
								className="qm-button-container-position"
								onClick={ () => {
									setSide( ! side );
									props.onSideChange( ! side );
								} }
							>
								<Icon name="image-rotate-left"/>
							</button>
						) }
						{ inWP && (
							<button
								aria-label={ __( 'Close Panel', 'query-monitor' ) }
								className="qm-button-container-close"
								onClick={ () => {
									setActivePanel( '' );
								} }
							>
								<Icon name="no-alt"/>
							</button>
						) }
					</div>
					<div id="qm-panels-wrapper">
						{ ! side && (
							<Nav active={ effectiveActive } menu={ props.panel_menu } onSwitch={ setActivePanel } seen={ seen } />
						) }
						<Panels data={ props.data } active={ effectiveActive } settings={ props.settings } />
					</div>
				</div>
			) }
			{ inWP && adminMenuElement && (
				<AdminMenu element={ adminMenuElement }>
					<a
						className="ab-item"
						href="#qm-overview"
						onClick={ ( e ) => {
							setActivePanel( active ? '' : 'overview' );
							adminMenuElement.classList.remove( 'hover' );
							e.preventDefault();
						} }
					>
						{ props.menu.top.title.map( ( part, i ) => (
							<Fragment key={ i }>
								{ i > 0 ? '\u00A0\u00A0' : '' }
								{ createInterpolateElement( part ) }
							</Fragment>
						) ) }
					</a>
					<div className="ab-sub-wrapper">
						<ul className="ab-submenu">
							{ Object.values( props.menu.sub ).map( ( menu ) => (
								<li key={ menu.id } className={ clsx( menu.meta && menu.meta.classname ) }>
									<a
										className="ab-item"
										href={ `#qm-${ menu.panel }` }
										onClick={ ( e ) => {
											setActivePanel( menu.panel );
											adminMenuElement.classList.remove( 'hover' );
											e.preventDefault();
										} }
									>
										{ menu.title }
									</a>
								</li>
							) ) }
						</ul>
					</div>
				</AdminMenu>
			) }
		</MainContext.Provider>
	);
};

interface iAdminMenuProps {
	element: HTMLElement;
	children: ComponentChildren;
}

const AdminMenu = ( props: iAdminMenuProps ) => {
	useMemo(() => {
		// Clear any existing content in the element
		props.element.innerHTML = '';
		props.element.classList.add( 'menupop' );
		return true;
	}, [props.element]);

	return createPortal( <>{ props.children }</>, props.element );
}
