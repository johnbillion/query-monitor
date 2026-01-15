import clsx from 'clsx';
import { Icon } from './components/icon';
import { MainContext, MainContextType } from './contexts/main-context';
import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelData, iSettings } from './panels/panels';

type Props = {
	active: string;
	adminMenuElement?: HTMLElement;
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
	theme: string;
	editor: string;
	filters: MainContextType['filters'];
	onPanelChange: ( active: string ) => void;
	onContainerResize: ( height: number, width: number ) => void;
	onSideChange: ( side: boolean ) => void;
	onThemeChange: ( theme: string ) => void;
	onEditorChange: ( editor: string ) => void;
	onFiltersChange: ( filters: MainContextType['filters'] ) => void;
}

export const QM = ( props: Props ) => {
	const [ active, setActive ] = React.useState( props.active );
	const [ side, setSide ] = React.useState( props.side );
	const [ theme, setTheme ] = React.useState( props.theme );
	const [ editor, setEditor ] = React.useState( props.editor );
	const [ filters, setFilters ] = React.useState( props.filters );

	const setActivePanel = ( active: string ) => {
		setActive( active );
		props.onPanelChange( active );
		// @TODO focus the panel for a11y
	};

	const adminMenuElement = props.adminMenuElement;

	let actualTheme = theme;

	if ( ! [ 'light', 'dark' ].includes( actualTheme ) ) {
		actualTheme = window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches
			? 'dark'
			: 'light';
	}

	const mainClass = clsx( 'qm-show', {
		'qm-show-right': side,
	} );

	const contextValue = {
		theme: theme,
		setTheme: ( theme: string ) => {
			props.onThemeChange( theme );
			setTheme( theme );
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
		switchToPanel: ( panelId: string, panelFilters?: MainContextType['filters'][string] ) => {
			setActivePanel( panelId );
			if ( panelFilters ) {
				const newFilters = {
					...filters,
					[panelId]: panelFilters,
				};
				props.onFiltersChange( newFilters );
				setFilters( newFilters );
			}
		},
	};

	// Apply the menu class (qm-warning, qm-notice, etc.) to the admin bar element
	React.useEffect( () => {
		if ( adminMenuElement && props.menu.top.classname ) {
			// Split the classname string and add each class
			const classes = props.menu.top.classname.split( ' ' ).filter( Boolean );
			classes.forEach( ( cls ) => adminMenuElement.classList.add( cls ) );

			return () => {
				classes.forEach( ( cls ) => adminMenuElement.classList.remove( cls ) );
			};
		}
	}, [ adminMenuElement, props.menu.top.classname ] );

	/**
	 * Many thanks to https://www.redblobgames.com/making-of/draggable/ for
	 * a comprehensive explanantion of modern pointer event handling.
	 */
	React.useEffect( () => {
		if ( ! active ) {
			return;
		}

		let dragging = false;

		const el = document.getElementsByClassName( 'qm-resizer' )[0];
		const qmMain = document.getElementById( 'query-monitor-main' );
		let windowHeight = window.innerHeight;
		let offset = 0;

		const start = (event: PointerEvent) => {
			if ( event.button !== 0 ) {
				return;
			}

			offset = event.clientY - el.getBoundingClientRect().top;

			dragging = true;
			el.setPointerCapture(event.pointerId);
		}

		const move = (event: PointerEvent) => {
			if (!dragging) {
				return;
			}

			let newHeight = windowHeight - event.clientY + offset;

			newHeight = Math.max( 27, newHeight );
			newHeight = Math.min( windowHeight - 32, newHeight );

			qmMain.style.height = `${ newHeight }px`;
		}

		const end = (_event: PointerEvent) => {
			dragging = false;
		}

		el.addEventListener( 'pointerdown', start );
		el.addEventListener( 'pointermove', move );
		el.addEventListener( 'pointerup', end );
		el.addEventListener( 'pointercancel', end );
		el.addEventListener( 'touchstart', (e) => e.preventDefault() );
	}, [ active ] );

	return (
		<MainContext.Provider value={ contextValue }>
			{ active && (
				<div className={ mainClass } data-theme={ actualTheme } dir="ltr" id="query-monitor-main">
					{ side && (
						<div className="qm-resizer" id="qm-side-resizer"></div>
					) }
					<div id="qm-title">
						<h1 className="qm-title-heading qm-resizer">
							{ __( 'Query Monitor', 'query-monitor' ) }
						</h1>
						{ side && (
							<div className="qm-title-heading">
								<NavSelect active={ active } menu={ props.panel_menu } onSwitch={ setActivePanel } />
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
						<button
							aria-label={ __( 'Close Panel', 'query-monitor' ) }
							className="qm-button-container-close"
							onClick={ () => {
								setActivePanel( '' );
							} }
						>
							<Icon name="no-alt"/>
						</button>
					</div>
					<div id="qm-wrapper">
						{ ! side && (
							<Nav active={ active } menu={ props.panel_menu } onSwitch={ setActivePanel } />
						) }
						<Panels data={ props.data } active={ active } settings={ props.settings } />
					</div>
				</div>
			) }
			{ adminMenuElement && (
				<AdminMenu element={ adminMenuElement }>
					<a
						className="ab-item"
						href="#qm-overview"
						onClick={ ( e ) => {
							setActivePanel( 'overview' );
							adminMenuElement.classList.remove( 'hover' );
							e.preventDefault();
						} }
					>
						<span dangerouslySetInnerHTML={{ __html: props.menu.top.title.join('\u00A0\u00A0') }} />
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
	children: React.ReactNode;
}

const AdminMenu = ( props: iAdminMenuProps ) => {
	React.useMemo(() => {
		// Clear any existing content in the element
		props.element.innerHTML = '';
		props.element.classList.add( 'menupop' );
		return true;
	}, []);

	return ReactDOM.createPortal( props.children, props.element );
}
