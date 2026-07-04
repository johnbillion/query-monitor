import { type ComponentChildren } from 'preact';
import { __ } from '@wordpress/i18n';

import {
	AbstractData,
	DataTypes,
} from '../data-types';
import { iPanelData, iSettings } from './panels';
import { PanelDataMap } from '../types';
import { ConcernedHooks } from '../html/concerned_hooks';

interface Panels<TDataKey extends keyof DataTypes> {
	[ id: string ]: Panel<TDataKey> | OverviewPanel<TDataKey> | SettingsPanel;
}

/**
 * A single menu entry contributed by a panel. The same entries feed both the
 * panel nav menu and the admin toolbar menu.
 */
export interface PanelMenuItem {
	id: string;
	panel: string;
	title: string;
	ok_count?: number | null;
	notice_count?: number | null;
	warning_count?: number | null;
	classname?: string;
	/** Entries nested beneath this one in the nav menu, in display order. */
	children?: PanelMenuItem[];
	/** id of the item this entry should be nested beneath as a child. */
	parent?: string;
	/** Set to false to omit the entry from the admin toolbar submenu. */
	adminBar?: boolean;
	/** Set to false to omit the entry from the panel navigation menu. */
	nav?: boolean;
	/** Whether to show the "New" badge in the navigation menu. */
	new?: boolean;
}

interface Panel<TDataKey extends keyof DataTypes> {
	render: ( data: DataTypes[ TDataKey ], enabled: boolean ) => ComponentChildren;
	data: TDataKey;
	type?: 'standard';
	order?: number;
	menu?: ( data: DataTypes[ TDataKey ], enabled: boolean ) => PanelMenuItem[];
	menuTitle?: ( data: DataTypes[ TDataKey ] ) => string[];
}

interface OverviewPanel<TDataKey extends keyof DataTypes> {
	render: ( data: iPanelData, settings: iSettings ) => ComponentChildren;
	type: 'overview';
	order?: number;
	data?: TDataKey;
	menu?: () => PanelMenuItem[];
	menuTitle?: ( data: DataTypes[ TDataKey ] ) => string[];
}

interface SettingsPanel {
	render: ( settings: iSettings ) => ComponentChildren;
	type: 'settings';
}

const panels: Panels<keyof DataTypes> = {};

export const registerPanel = <
	TDataKey extends keyof DataTypes,
>(
	id: string,
	args: Panel<TDataKey>,
) => {
	panels[ id ] = {
		...args,
		type: 'standard',
	};

	panels[ `${id}-concerned_hooks` ] = {
		render: ( data, enabled ) => <ConcernedHooks data={ data } enabled={ enabled } />,
		data: id as keyof DataTypes,
		menu: ( data ) => {
			const { concerned_filters, concerned_actions } = data as AbstractData;
			const count = Object.keys( concerned_filters ?? {} ).length + Object.keys( concerned_actions ?? {} ).length;

			if ( ! count ) {
				return [];
			}

			return [ {
				id: `${id}-concerned_hooks`,
				panel: `${id}-concerned_hooks`,
				parent: id,
				title: __( 'Hooks in Use', 'query-monitor' ),
				ok_count: count,
				adminBar: false,
			} ];
		},
		type: 'standard',
	};
}

export const registerOverview = <
	TDataKey extends keyof DataTypes,
>(
	id: string,
	args: Omit<OverviewPanel<TDataKey>, 'type'>,
) => {
	panels[ id ] = {
		...args,
		type: 'overview',
	};
}

export const registerSettings = (
	args: Omit<SettingsPanel, 'type'>,
) => {
	panels['settings'] = {
		...args,
		type: 'settings',
	};
}

export const getPanel = ( id: string ) => {
	return panels[ id ] ?? null;
}

/**
 * Returns the menu order declared by a panel's registration, or undefined if it
 * has none (such panels sort to the bottom of the menu).
 */
export const getMenuOrder = ( id: string ): number | undefined => {
	const panel = panels[ id ];
	return panel && ( 'order' in panel ) ? panel.order : undefined;
}

type AnyPanel = Panel<keyof DataTypes> | OverviewPanel<keyof DataTypes> | SettingsPanel | null;

export const isOverviewPanel = ( panel: AnyPanel ): panel is OverviewPanel<keyof DataTypes> => {
	return panel?.type === 'overview';
}

export const isSettingsPanel = ( panel: AnyPanel ): panel is SettingsPanel => {
	return panel?.type === 'settings';
}

/**
 * Gathers the menu, title, and menu class contributions from every panel.
 */
export const collectMenuContributions = (
	data: PanelDataMap,
) => {
	const items: PanelMenuItem[] = [];
	const menuTitle: string[] = [];

	for ( const panel of Object.values( panels ) ) {
		if ( panel.type === 'overview' ) {
			if ( panel.menu ) {
				items.push( ...panel.menu() );
			}
			if ( panel.menuTitle && panel.data ) {
				const slice = data[ panel.data ];

				if ( slice ) {
					menuTitle.push( ...panel.menuTitle( slice.data as DataTypes[ keyof DataTypes ] ) );
				}
			}
			continue;
		}

		if ( panel.type !== 'standard' ) {
			continue;
		}

		const slice = data[ panel.data ];

		if ( ! slice ) {
			continue;
		}

		const panelData = slice.data as DataTypes[ keyof DataTypes ];

		if ( panel.menu ) {
			items.push( ...panel.menu( panelData, slice.enabled ) );
		}
		if ( panel.menuTitle ) {
			menuTitle.push( ...panel.menuTitle( panelData ) );
		}
	}

	// Nest any item that declared a parent beneath that parent's entry. An item
	// whose parent is absent is dropped rather than surfaced at the top level.
	const itemsById = new Map( items.map( ( item ) => [ item.id, item ] ) );
	const topLevel = items.filter( ( item ) => {
		if ( ! item.parent ) {
			return true;
		}

		const parent = itemsById.get( item.parent );

		if ( parent ) {
			( parent.children ??= [] ).push( item );
		}

		return false;
	} );

	return { items: topLevel, menuTitle };
}
