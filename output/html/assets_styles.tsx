import {
	iPanelProps,
} from 'qmi';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

import Assets from '../assets';

class Styles extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const {
			data,
			id,
		} = this.props;

		return (
			<Assets
				data={ data }
				id={ id }
				labels={ {
					none: __( 'No CSS files were enqueued.', 'query-monitor' ),
				} }
			/>
		);
	}

}

export default Styles;
