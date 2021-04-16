import classNames from 'classnames';
import { Icon } from 'qmi';
import * as React from 'react';
import * as ReactDOM from 'react-dom';

import { __ } from '@wordpress/i18n';

import { Nav, iNavMenu, NavSelect } from './nav';
import { Panels, iPanelsProps } from './panels';

export interface iQMProps {
	menu: {
		top: {
			title: string[];
		};
		sub: {
			[k: string]: {
				id: string;
				title: string;
				meta?: {
					classname: string;
				}
			}
		}
	};
	panels: iPanelsProps;
	panel_menu: iNavMenu;
	panel_key: string;
}

interface iState {
	active: string;
}

export class QM extends React.Component<iQMProps, iState> {
	constructor( props: iQMProps ) {
		super( props );

		this.state = {
			active: props.panels.active,
		};
	}

	render() {
		const setActivePanel = ( active: string ) => {
			this.setState( {
				active,
			} );
			// @TODO focus the panel for a11y
		};

		const adminMenuId = 'wp-admin-bar-query-monitor';
		const adminMenuElement = document.getElementById( adminMenuId );

		const adminMenu = adminMenuElement && (
			<AdminMenu element={ adminMenuElement }>
				<a className="ab-item" href="#qm-overview">
					{ this.props.menu.top.title.join( ' ' ) }
				</a>
				<div className="ab-sub-wrapper">
					<ul className="ab-submenu">
						{ Object.values( this.props.menu.sub ).map( ( menu ) => (
							<li key={ menu.id } className={ classNames( menu.meta && menu.meta.classname ) }>
								<a
									className="ab-item"
									href={ `#qm-${ menu.id }` }
									onClick={ ( e ) => {
										setActivePanel( menu.id );
										document.getElementById( adminMenuId ).classList.remove( 'hover' );
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
		);

		// @TODO lift this up, use compose()
		localStorage.setItem( this.props.panel_key, this.state.active );

		if ( ! this.state.active ) {
			return adminMenu;
		}

		return (
			<>
				<div dir="ltr" id="query-monitor-main">
					<div className="qm-resizer" id="qm-side-resizer"></div>
					<div className="qm-resizer" id="qm-title">
						<h1 className="qm-title-heading">
							{ __( 'Query Monitor', 'query-monitor' ) }
						</h1>
						<div className="qm-title-heading">
							<NavSelect menu={ this.props.panel_menu } onSwitch={ setActivePanel }/>
						</div>
						<button
							aria-label={ __( 'Settings', 'query-monitor' ) }
							className="qm-title-button qm-button-container-settings"
							onClick={ () => {
								setActivePanel( 'settings' );
							} }
						>
							<Icon name="admin-generic"/>
						</button>
						<button
							aria-label={ __( 'Toggle panel position', 'query-monitor' ) }
							className="qm-title-button qm-button-container-position"
						>
							<Icon name="image-rotate-left"/>
						</button>
						<button
							aria-label={ __( 'Close Panel', 'query-monitor' ) }
							className="qm-title-button qm-button-container-close"
							onClick={ () => {
								setActivePanel( '' );
							} }
						>
							<Icon name="no-alt"/>
						</button>
					</div>
					<div id="qm-wrapper">
						<Nav menu={ this.props.panel_menu } onSwitch={ setActivePanel } />
						<Panels { ...this.props.panels } active={ this.state.active }/>
					</div>
				</div>
				{ adminMenu }
			</>
		);
	}
}

interface iAdminMenuProps {
	element: HTMLElement;
}

export class AdminMenu extends React.Component<iAdminMenuProps, Record<string, unknown>> {
	el: HTMLElement = document.createElement( 'li' );

	constructor( props: iAdminMenuProps ) {
		super( props );

		this.el.id = props.element.id;
		this.el.className = props.element.className;
	}

	componentDidMount() {
		this.props.element.replaceWith( this.el );
	}

	componentWillUnmount() {
		this.props.element.innerHTML = '';
	}

	render() {
		return ReactDOM.createPortal( this.props.children, this.el );
	}
}
