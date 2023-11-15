import classNames from 'classnames';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	active: string;
	menu: iNavMenu;
	onSwitch: {
		( active: string ): void;
	}
}

export type iNavMenu = {
	[k: string]: iNavMenuItem;
}

interface iNavMenuItem {
	panel: string;
	title: string;
	children?: iNavMenu;
}

export const Nav = ( { menu, onSwitch, active }: Props ) => (
	<nav aria-labelledby="qm-panel-menu-caption" id="qm-panel-menu">
		<h2 className="qm-screen-reader-text" id="qm-panel-menu-caption">
			{ __( 'Query Monitor Menu', 'query-monitor' ) }
		</h2>
		<ul role="tablist">
			<li
				key="overview"
				className={ classNames( {
					'qm-current-menu': active === 'overview',
				} ) }
				role="presentation"
			>
				<button aria-selected={ active === 'overview' } role="tab" onClick={ () => {
					onSwitch( 'overview' );
				} }>
					{ __( 'Overview', 'query-monitor' ) }
				</button>
			</li>
			{ Object.entries( menu ).map( ( [ key, item ] ) => (
				<li
					key={ key }
					className={ classNames( {
						'qm-current-menu': (
							active === item.panel ||
							( item.children && Object.keys( item.children ).map( k => (
								item.children[ k ].panel
							) ).includes( active ) )
						),
					} ) }
					role="presentation"
				>
					<button
						aria-selected={ active === item.panel }
						role="tab"
						onClick={ () => {
							onSwitch( item.panel );
						} }
					>
						{ item.title }
					</button>
					{ item.children && (
						<ul role="presentation">
							{ Object.keys( item.children ).map( k => (
								<li key={ `${ key }-${ k }` } role="presentation">
									<button
										aria-selected={ active === item.children[ k ].panel }
										role="tab"
										onClick={ () => {
											onSwitch( item.children[ k ].panel );
										} }
									>
										{ item.children[ k ].title }
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

export const NavSelect = ( { active, menu, onSwitch }: Props ) => (
	<select
		value={ active }
		onChange={ ( e ) => {
			onSwitch( e.target.value );
		} }
	>
		<option key="overview" value="overview">
			{ __( 'Overview', 'query-monitor' ) }
		</option>
		{ Object.entries( menu ).map( ( [ key, item ] ) => (
			<React.Fragment key={ key }>
				<option value={ item.panel }>
					{ item.title }
				</option>
				{ item.children && (
					<>
						{ Object.keys( item.children ).map( k => (
							<option key={ `${ key }-${ k }` } value={ item.children[ k ].panel }>
								{ `└ ${ item.children[ k ].title }` }
							</option>
						) ) }
					</>
				) }
			</React.Fragment>
		) ) }
		<option key="settings" value="settings">
			{ __( 'Settings', 'query-monitor' ) }
		</option>
	</select>
);
