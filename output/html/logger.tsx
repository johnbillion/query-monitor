import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as React from 'react';

import { __, _n, sprintf } from '@wordpress/i18n';

const warningLevels = [
	'emergency',
	'alert',
	'critical',
	'error',
	'warning',
];

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
						{ warningLevels.includes( row.level ) && ( <Warning /> ) }
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
			component: getComponentCol( data.logs ),
		} }
		data={ data.logs }
		rowHasError={ ( row ) => warningLevels.includes( row.level ) }
	/>
};
