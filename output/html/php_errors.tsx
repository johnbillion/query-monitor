import {
	PanelProps,
	TabularPanel,
	Warning,
	getCallerCol,
	getComponentCol
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data }: PanelProps<DataTypes['PHP_Errors']> ) => (
	<TabularPanel
		title={ __( 'PHP Errors', 'query-monitor' ) }
		cols={{
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ row.level === 'warning' && ( <Warning /> ) }
						{ row.level }
					</>
				),
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => ( row.message ),
			},
			...getCallerCol( Object.values( data.errors ) ),
			count: {
				className: 'qm-num',
				heading: __( 'Count', 'query-monitor' ),
				render: ( row ) => ( row.calls ),
			},
			...getComponentCol( Object.values( data.errors ), data.component_times ),
		}}
		hasError={ ( row ) => ( row.level === 'warning' ) }
		data={ Object.values( data.errors ) }
	/>
);
