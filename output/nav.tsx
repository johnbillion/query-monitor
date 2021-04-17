import * as React from 'react';

import { __ } from '@wordpress/i18n';

export interface iNavProps {
	active: string;
	menu: iNavMenu;
	onSwitch: {
		( active: string ): void;
	}
}

export interface iNavMenu {
	[k: string]: iNavMenuItem;
}

export interface iNavMenuItem {
	panel: string;
	title: string;
	children?: iNavMenu;
}

export class Nav extends React.Component<iNavProps, Record<string, unknown>> {
	render() {
		const menu = this.props.menu;
		const onSwitch = this.props.onSwitch;

		return (
			<nav aria-labelledby="qm-panel-menu-caption" id="qm-panel-menu">
				<h2 className="qm-screen-reader-text" id="qm-panel-menu-caption">
					{ __( 'Query Monitor Menu', 'query-monitor' ) }
				</h2>
				<ul role="tablist">
					<li key="overview" role="presentation">
						<button aria-selected={ this.props.active === 'overview' } role="tab" onClick={ () => {
							onSwitch( 'overview' );
						} }>
							{ __( 'Overview', 'query-monitor' ) }
						</button>
					</li>
					{ Object.keys( menu ).map( key => (
						<li key={ key } role="presentation">
							<button
								aria-selected={ this.props.active === menu[ key ].panel }
								role="tab"
								onClick={ () => {
									onSwitch( menu[ key ].panel );
								} }
							>
								{ menu[ key ].title }
							</button>
							{ menu[ key ].children && (
								<ul role="presentation">
									{ Object.keys( menu[ key ].children ).map( k => (
										<li key={ `${ key }-${ k }` } role="presentation">
											<button
												aria-selected={ this.props.active === menu[ key ].children[ k ].panel }
												role="tab"
												onClick={ () => {
													onSwitch( menu[ key ].children[ k ].panel );
												} }
											>
												{ `└ ${ menu[ key ].children[ k ].title }` }
											</button>
										</li>
									) ) }
								</ul>
							) }
						</li>
					) ) }
				</ul>
			</nav>
		);
	}
}

export class NavSelect extends React.Component<iNavProps, Record<string, unknown>> {
	render() {
		const menu = this.props.menu;

		return (
			<select>
				<option key="overview" value="#qm-overview">
					{ __( 'Overview', 'query-monitor' ) }
				</option>
				{ Object.keys( menu ).map( key => (
					<React.Fragment key={ key }>
						<option value={ menu[ key ].panel }>
							{ menu[ key ].title }
						</option>
						{ menu[ key ].children && (
							<>
								{ Object.keys( menu[ key ].children ).map( k => (
									<option key={ `${ key }-${ k }` } value={ menu[ key ].children[ k ].panel }>
										{ `└ ${ menu[ key ].children[ k ].title }` }
									</option>
								) ) }
							</>
						) }
					</React.Fragment>
				) ) }
				<option key="settings" value="#qm-settings">
					{ __( 'Settings', 'query-monitor' ) }
				</option>
			</select>
		);
	}
}
