import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import {
	__,
	_x,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

import Assets, { assetsMenu, assetsMenuClass } from '../assets';

export const scriptsMenu = ( data: DataTypes['assets_scripts'] ): PanelMenuItem[] =>
	assetsMenu( 'assets_scripts', _x( 'Scripts', 'Enqueued scripts', 'query-monitor' ), data );

export const scriptsMenuClass = assetsMenuClass;

export const Scripts = ( props: PanelProps<DataTypes['assets_scripts']> ) => {
	return (
		<Assets
			{ ...props }
			labels={ {
				none: __( 'No JavaScript files were enqueued.', 'query-monitor' ),
			} }
		/>
	);
};
