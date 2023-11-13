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

export interface iNavMenu {
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
			{ Object.keys( menu ).map( key => (
				<li
					key={ key }
					className={ classNames( {
						'qm-current-menu': (
							active === menu[ key ].panel ||
							( menu[ key ].children && Object.keys( menu[ key ].children ).map( k => (
								menu[ key ].children[ k ].panel
							) ).includes( active ) )
						),
					} ) }
					role="presentation"
				>
					<button
						aria-selected={ active === menu[ key ].panel }
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
										aria-selected={ active === menu[ key ].children[ k ].panel }
										role="tab"
										onClick={ () => {
											onSwitch( menu[ key ].children[ k ].panel );
										} }
									>
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

export const NavSelect = ( { active, menu, onSwitch }: Props ) => (
	<select
		value={ active }
		onChange={ ( e ) => {
			onSwitch( e.target.value );
		} }
	>
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
