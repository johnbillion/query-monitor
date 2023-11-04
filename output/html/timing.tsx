import {
	PanelProps,
	Component,
	TabularPanel,
	Time,
	ApproximateSize,
	EmptyPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export default ( { data }: PanelProps<DataTypes['Timing']> ) => {
	if ( ! data.timing && ! data.warning ) {
		return <EmptyPanel>
			<p>
				{ __( 'No data logged.', 'query-monitor' ) }
			</p>
			<p>
				<a href="https://querymonitor.com/blog/2018/07/profiling-and-logging/">
					{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
				</a>
			</p>
		</EmptyPanel>
	}

	return <TabularPanel
		title={ __( 'Timing', 'query-monitor' ) }
		cols={ {
			function: {
				heading: __( 'Tracked Function', 'query-monitor' ),
				render: ( row ) => (
					<code>
						{ row.function }
						@TODO laps
					</code>
				),
			},
			start_time: {
				className: 'qm-num',
				heading: __( 'Started', 'query-monitor' ),
				render: ( row ) => <Time value={ row.start_time } />,
			},
			end_time: {
				className: 'qm-num',
				heading: __( 'Stopped', 'query-monitor' ),
				render: ( row ) => <Time value={ row.end_time } />,
			},
			function_time: {
				className: 'qm-num',
				heading: __( 'Time', 'query-monitor' ),
				render: ( row ) => <Time value={ row.function_time } />,
			},
			function_memory: {
				className: 'qm-num',
				heading: __( 'Memory', 'query-monitor' ),
				render: ( row ) => <ApproximateSize value={ row.function_memory } />,
			},
			component: {
				heading: __( 'Component', 'query-monitor' ),
				render: ( row ) => <Component component={ row.trace.component } />,
			},
		} }
		data={ data.timing }
	/>;
};
