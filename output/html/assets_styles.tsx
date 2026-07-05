import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import {
	__,
	_x,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

import Assets, { assetsMenu } from '../assets';

export const stylesMenu = ( data: DataTypes['assets_styles'] ): PanelMenuItem[] =>
	assetsMenu( 'assets_styles', _x( 'Styles', 'Enqueued styles', 'query-monitor' ), data );

export const Styles = ( props: PanelProps<DataTypes['assets_styles']> ) => {
	return (
		<Assets
			{ ...props }
			labels={ {
				none: __( 'No CSS files were enqueued.', 'query-monitor' ),
			} }
		/>
	);
};
