import * as classNames from 'classnames';
import { Icon } from 'qmi';
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
	panel_key: string;
	position_key: string;
	side: boolean;
}

interface iState {
	active: string;
	side: boolean;
}

export class QM extends React.Component<iQMProps, iState> {
	constructor( props: iQMProps ) {
		super( props );

		this.state = {
			active: props.active,
			side: props.side,
		};
	}

	render() {
		const setActivePanel = ( active: string ) => {
			this.setState( {
				active,
			} );
			// @TODO focus the panel for a11y
		};

		const adminMenuElement = this.props.adminMenuElement;

		// @TODO lift this up, use compose()
		localStorage.setItem( this.props.panel_key, this.state.active );
		localStorage.setItem( this.props.position_key, this.state.side ? 'right' : '' );

		// @TODO light/dark/auto theme support

		const mainClass = classNames( 'qm-show', {
			'qm-show-right': this.state.side,
		} );

		return (
			<>
				{ this.state.active && (
					<div className={ mainClass } data-theme="auto" dir="ltr" id="query-monitor-main">
						<div className="qm-resizer" id="qm-side-resizer"></div>
						<div className="qm-resizer" id="qm-title">
							<h1 className="qm-title-heading">
								{ __( 'Query Monitor', 'query-monitor' ) }
							</h1>
							<div className="qm-title-heading">
								<NavSelect active={ this.state.active } menu={ this.props.panel_menu } onSwitch={ setActivePanel }/>
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
								onClick={ () => {
									this.setState( {
										side: ! this.state.side,
									} );
								} }
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
							<Nav active={ this.state.active } menu={ this.props.panel_menu } onSwitch={ setActivePanel } />
							<Panels { ...this.props.panels } active={ this.state.active }/>
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
							{ this.props.menu.top.title.join( ' ' ) }
						</a>
						<div className="ab-sub-wrapper">
							<ul className="ab-submenu">
								{ Object.values( this.props.menu.sub ).map( ( menu ) => (
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
			</>
		);
	}
}

interface iAdminMenuProps {
	element: HTMLElement;
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
