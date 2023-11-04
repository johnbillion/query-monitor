import * as classNames from 'classnames';
import {
	Icon,
	MainContext,
	MainContextType,
} from 'qmi';
import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelsProps } from './panels';

export interface iQMProps {
	active: string;
	adminMenuElement?: HTMLElement;
	menu: {
		top: {
			title: string[];
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
	panels: iPanelsProps;
	panel_menu: iNavMenu;
	side: boolean;
	theme: string;
	editor: string;
	filters: MainContextType['filters'];
	onPanelChange: ( active: string ) => void;
	onSideChange: ( side: boolean ) => void;
	onThemeChange: ( theme: string ) => void;
	onEditorChange: ( editor: string ) => void;
	onFiltersChange: ( filters: MainContextType['filters'] ) => void;
}

export const QM = ( props: iQMProps ) => {
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

	const mainClass = classNames( 'qm-show', {
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
	};

	return (
		<MainContext.Provider value={ contextValue }>
			{ active && (
				<div className={ mainClass } data-theme={ actualTheme } dir="ltr" id="query-monitor-main">
					<div className="qm-resizer" id="qm-side-resizer"></div>
					<div className="qm-resizer" id="qm-title">
						<h1 className="qm-title-heading">
							{ __( 'Query Monitor', 'query-monitor' ) }
						</h1>
						<div className="qm-title-heading">
							<NavSelect active={ active } menu={ props.panel_menu } onSwitch={ setActivePanel } />
						</div>
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
						<Nav active={ active } menu={ props.panel_menu } onSwitch={ setActivePanel } />
						<Panels { ...props.panels } active={ active }/>
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
						{ props.menu.top.title.join( ' ' ) }
					</a>
					<div className="ab-sub-wrapper">
						<ul className="ab-submenu">
							{ Object.values( props.menu.sub ).map( ( menu ) => (
								<li key={ menu.id } className={ classNames( menu.meta && menu.meta.classname ) }>
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

class AdminMenu extends React.Component<iAdminMenuProps, Record<string, unknown>> {
	constructor( props: iAdminMenuProps ) {
		super( props );

		this.props.element.innerHTML = '';
	}

	render() {
		return ReactDOM.createPortal( this.props.children, this.props.element );
	}
}
