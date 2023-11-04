import {
	PanelProps,
	TabularPanel,
	Warning,
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
			caller: {
				heading: __( 'Location', 'query-monitor' ),
				// @todo render
			},
			count: {
				className: 'qm-num',
				heading: __( 'Count', 'query-monitor' ),
				render: ( row ) => ( row.calls ),
			},
			component: {
				heading: __( 'Component', 'query-monitor' ),
			},
		}}
		hasError={ ( row ) => ( row.level === 'warning' ) }
		data={ Object.values( data.errors ) }
	/>
);
