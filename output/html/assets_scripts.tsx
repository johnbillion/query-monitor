import {
	iPanelProps,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

import Assets from '../assets';

class Scripts extends React.Component<iPanelProps<DataTypes['Assets']>, Record<string, unknown>> {

	render() {
		return (
			<Assets
				{ ...this.props }
				labels={ {
					none: __( 'No JavaScript files were enqueued.', 'query-monitor' ),
				} }
			/>
		);
	}

}

export default Scripts;
