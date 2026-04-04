import clsx from 'clsx';
import { Fragment } from 'preact';
import * as Utils from './utils';

import { __, _x } from '@wordpress/i18n';

interface Props {
	active: string;
	menu: iNavMenu;
	onSwitch: {
		( active: string ): void;
	}
	seen?: string;
}

export type iNavMenu = {
	[k: string]: iNavMenuItem;
}

interface iNavMenuItem {
	panel: string;
	title: string;
	count?: number | null;
	warning_count?: number | null;
	new: boolean;
	children?: iNavMenu;
}

/**
 * Normalises a menu item that uses a legacy "Title (n)" format by extracting
 * the count into the dedicated `count` property. This provides backwards
 * compatibility with third-party plugins that haven't adopted the new format.
 */
function normaliseMenuItem( item: iNavMenuItem ): iNavMenuItem {
	if ( item.count != null ) {
		return item;
	}

	const match = item.title.match( /^(.+)\s\((\d+)\)$/ );

	if ( ! match ) {
		return item;
	}

	return {
		...item,
		title: match[1],
		count: parseInt( match[2], 10 ) || null,
	};
}

function normaliseMenu( menu: iNavMenu ): iNavMenu {
	const result: iNavMenu = {};

	for ( const [ key, item ] of Object.entries( menu ) ) {
		const normalised = normaliseMenuItem( item );

		result[ key ] = normalised.children
			? { ...normalised, children: normaliseMenu( normalised.children ) }
			: normalised;
	}

	return result;
}

const Badges = ( { item, seen }: { item: iNavMenuItem; seen: boolean } ) => (
	<span aria-hidden="true">
		{ item.new && ! seen && (
			<span className="qm-menu-badge qm-menu-badge-new">
				{ _x( 'New', 'badge', 'query-monitor' ) }
			</span>
		) }
		{ !! item.count && item.count !== item.warning_count && (
			<span className="qm-menu-badge">
				{ Utils.numberFormat( item.count ) }
			</span>
		) }
		{ !! item.warning_count && (
			<span className="qm-menu-badge qm-menu-badge-warning">
				{ Utils.numberFormat( item.warning_count ) }
			</span>
		) }
	</span>
);

function selectLabel( item: iNavMenuItem ): string {
	const parts = [ item.title ];

	if ( item.count || item.warning_count ) {
		const counts = [];

		if ( item.warning_count ) {
			counts.push( `${ item.warning_count }!` );
		}
		if ( item.count && item.count !== item.warning_count ) {
			counts.push( `${ item.count }` );
		}

		parts.push( `(${ counts.join( ', ' ) })` );
	}

	return parts.join( ' ' );
}

const isNewItemSeen = ( item: iNavMenuItem, seen: string ): boolean => {
	return ! item.new || item.panel === seen;
};

export const Nav = ( { menu: rawMenu, onSwitch, active, seen = '' }: Props ) => {
	const menu = normaliseMenu( rawMenu );

	return (
	<nav aria-labelledby="qm-panel-menu-caption" id="qm-panel-menu">
		<h2 className="qm-screen-reader-text" id="qm-panel-menu-caption">
			{ __( 'Query Monitor Menu', 'query-monitor' ) }
		</h2>
		<ul role="tablist">
			<li
				key="overview"
				className={ clsx( {
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
			{ Object.entries( menu ).map( ( [ key, item ] ) => {
				const children = item.children;
				return (
					<li
						key={ key }
						className={ clsx( {
							'qm-current-menu': (
								active === item.panel ||
								( children && Object.keys( children ).map( k => (
									children[ k ].panel
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
							<Badges item={ item } seen={ isNewItemSeen( item, seen ) } />
						</button>
						{ children && (
							<ul role="presentation">
								{ Object.keys( children ).map( k => (
									<li key={ `${ key }-${ k }` } role="presentation">
										<button
											aria-selected={ active === children[ k ].panel }
											role="tab"
											onClick={ () => {
												onSwitch( children[ k ].panel );
											} }
										>
											{ children[ k ].title }
											<Badges item={ children[ k ] } seen={ isNewItemSeen( children[ k ], seen ) } />
										</button>
									</li>
								) ) }
							</ul>
						) }
					</li>
				);
			} ) }
		</ul>
	</nav>
	);
};

export const NavSelect = ( { menu: rawMenu, onSwitch, active }: Props ) => {
	const menu = normaliseMenu( rawMenu );

	return (
	<select
		aria-label={ __( 'Select panel', 'query-monitor' ) }
		value={ active }
		onChange={ ( e ) => {
			onSwitch( e.currentTarget.value );
		} }
	>
		<option key="overview" value="overview">
			{ __( 'Overview', 'query-monitor' ) }
		</option>
		{ Object.entries( menu ).map( ( [ key, item ] ) => {
			const children = item.children;
			return (
				<Fragment key={ key }>
					<option value={ item.panel }>
						{ selectLabel( item ) }
					</option>
					{ children && (
						<>
							{ Object.keys( children ).map( k => (
								<option key={ `${ key }-${ k }` } value={ children[ k ].panel }>
									{ `└ ${ selectLabel( children[ k ] ) }` }
								</option>
							) ) }
						</>
					) }
				</Fragment>
			);
		} ) }
	</select>
	);
};
