import { type ComponentChildren } from 'preact';

import {
	DataTypes,
} from '../data-types';
import { iPanelData, iSettings } from './panels';

interface Panels<TDataKey extends keyof DataTypes> {
	[ id: string ]: Panel<TDataKey> | OverviewPanel | SettingsPanel;
}

interface Panel<TDataKey extends keyof DataTypes> {
	render: ( data: DataTypes[ TDataKey ], enabled: boolean ) => ComponentChildren;
	data: TDataKey;
	type?: 'standard';
}

interface OverviewPanel {
	render: ( data: iPanelData, settings: iSettings ) => ComponentChildren;
	type: 'overview';
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
}

export const registerOverview = (
	args: Omit<OverviewPanel, 'type'>,
) => {
	panels['overview'] = {
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

type AnyPanel = Panel<keyof DataTypes> | OverviewPanel | SettingsPanel | null;

export const isOverviewPanel = ( panel: AnyPanel ): panel is OverviewPanel => {
	return panel?.type === 'overview';
}

export const isSettingsPanel = ( panel: AnyPanel ): panel is SettingsPanel => {
	return panel?.type === 'settings';
}
