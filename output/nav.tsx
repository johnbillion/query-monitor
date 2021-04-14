import * as React from 'react';

import { __ } from '@wordpress/i18n';

export interface iNavProps {
	menu: iNavMenu;
}

export interface iNavMenu {
	[k: string]: iNavMenuItem;
}

export interface iNavMenuItem {
	href: string;
	title: string;
	children?: iNavMenu;
}

export class Nav extends React.Component<iNavProps, Record<string, unknown>> {
	render() {
		const menu = this.props.menu;

		return (
			<nav aria-labelledby="qm-panel-menu-caption" id="qm-panel-menu">
				<h2 className="qm-screen-reader-text" id="qm-panel-menu-caption">
					{ __( 'Query Monitor Menu', 'query-monitor' ) }
				</h2>
				<ul role="tablist">
					<li role="presentation">
						<button data-qm-href="#qm-overview" role="tab">
							{ __( 'Overview', 'query-monitor' ) }
						</button>
					</li>
					{ Object.keys( menu ).map( key => (
						<li role="presentation">
							<button data-qm-href={ menu[ key ].href } role="tab">
								{ menu[ key ].title }
							</button>
							{ menu[ key ].children && (
								<ul role="presentation">
									{ Object.keys( menu[ key ].children ).map( k => (
										<li role="presentation">
											<button data-qm-href={ menu[ key ].children[ k ].href } role="tab">
												{ menu[ key ].children[ k ].title }
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
				<option value="#qm-overview">
					{ __( 'Overview', 'query-monitor' ) }
				</option>
				{ Object.keys( menu ).map( key => (
					<>
						<option value={ menu[ key ].href }>
							{ menu[ key ].title }
						</option>
						{ menu[ key ].children && (
							<>
								{ Object.keys( menu[ key ].children ).map( k => (
									<option value={ `└ ${ menu[ key ].children[ k ].href }` }>
										{ menu[ key ].children[ k ].title }
									</option>
								) ) }
							</>
						) }
					</>
				) ) }
				<option value="#qm-settings">
					{ __( 'Settings', 'query-monitor' ) }
				</option>
			</select>
		);
	}
}
