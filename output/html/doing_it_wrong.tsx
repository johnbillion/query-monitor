import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	getCallerCol,
	getComponentCol
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export const DoingItWrong = ( { data }: PanelProps<DataTypes['doing_it_wrong']> ) => {
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
			caller: getCallerCol( data.actions ),
			component: getComponentCol( data.actions, data.component_times ),
		} }
		data={ data.actions }
	/>
};
