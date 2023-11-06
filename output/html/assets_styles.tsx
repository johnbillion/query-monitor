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

export const Styles = ( props: PanelProps<DataTypes['Assets']> ) => {
	return (
		<Assets
			{ ...props }
			labels={ {
				none: __( 'No CSS files were enqueued.', 'query-monitor' ),
			} }
		/>
	);
};
