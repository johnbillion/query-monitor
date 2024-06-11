import {
	PanelProps,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

import Assets from '../assets';

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
