import {
	PanelProps,
	TabularPanel,
	Warning,
	EmptyPanel,
	getCallerCol,
	getComponentCol
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __, _n, sprintf } from '@wordpress/i18n';

export const Logger = ( { data }: PanelProps<DataTypes['logger']> ) => {
	if ( ! data.logs || ! data.logs.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
				<p>
					<a href="https://querymonitor.com/wordpress-debugging/profiling-and-logging/">
						{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
					</a>
				</p>
			</EmptyPanel>
		);
	}

	const filterOptions = [
		{
			label: 'Emergency',
			key: 'emergency',
		},
		{
			label: 'Alert',
			key: 'alert',
		},
		{
			label: 'Critical',
			key: 'critical',
		},
		{
			label: 'Error',
			key: 'error',
		},
		{
			label: 'Warning',
			key: 'warning',
		},
		{
			label: 'Notice',
			key: 'notice',
		},
		{
			label: 'Info',
			key: 'info',
		},
		{
			label: 'Debug',
			key: 'debug',
		},
	];

	return <TabularPanel
		title={ __( 'Logs', 'query-monitor' ) }
		cols={ {
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ data.warning_levels.includes( row.level ) && ( <Warning /> ) }
						{ row.level }
					</>
				),
				filters: {
					options: filterOptions,
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => <pre>{ row.message }</pre>,
			},
			context: {
				heading: __( 'Context', 'query-monitor' ),
				render: ( row ) => {
					if ( ! row.context || Object.keys( row.context ).length === 0 ) {
						return '';
					}
					return (
						<details>
							<summary>{ sprintf( _n( '%d item', '%d items', Object.keys( row.context ).length, 'query-monitor' ), Object.keys( row.context ).length ) }</summary>
							<pre style={{ fontSize: '11px', marginTop: '4px' }}>
								{ JSON.stringify( row.context, null, 2 ) }
							</pre>
						</details>
					);
				},
			},
			caller: getCallerCol( data.logs ),
			component: getComponentCol( data.logs, data.component_times ),
		} }
		data={ data.logs }
		rowHasError={ ( row ) => data.warning_levels.includes( row.level ) }
	/>
};
