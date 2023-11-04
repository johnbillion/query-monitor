import {
	iPanelProps,
	EmptyPanel,
	TabularPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export default ( { data }: iPanelProps<DataTypes['Doing_It_Wrong']> ) => {
	if ( ! data.actions?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No occurrences.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Doing it Wrong', 'query-monitor' ) }
		cols={ {
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => row.message,
			},
			caller: {
				heading: __( 'Caller', 'query-monitor' ),
			},
			component: {
				heading: __( 'Component', 'query-monitor' ),
			},
		} }
		data={ data.actions }
	/>
};
