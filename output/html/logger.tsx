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

import { __ } from '@wordpress/i18n';

export const Logger = ( { data }: PanelProps<DataTypes['Logger']> ) => {
	if ( ! data.logs.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
				<p>
					<a href="https://querymonitor.com/blog/2018/07/profiling-and-logging/">
						{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
					</a>
				</p>
			</EmptyPanel>
		);
	}

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
					options: data.levels.map( ( level ) => ( {
						key: level,
						label: level,
					} ) ),
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => row.message,
			},
			caller: getCallerCol( data.logs ),
			component: getComponentCol( data.logs, data.component_times ),
		} }
		data={ data.logs }
		hasError={ ( row ) => data.warning_levels.includes( row.level ) }
	/>
};
