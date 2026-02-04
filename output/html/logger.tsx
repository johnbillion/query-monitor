import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { JsonOutput } from '../components/json-output';
import { getCallerCol, getComponentCol } from '../table';
import { getFilterLabel } from '../utils';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
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

	const counts = data.logs.reduce( ( acc, log ) => {
		acc[ log.level ] = ( acc[ log.level ] || 0 ) + 1;
		return acc;
	}, {} as Record<string, number> );

	const filterOptions = [
		{
			label: getFilterLabel( 'Emergency', counts.emergency ),
			key: 'emergency',
		},
		{
			label: getFilterLabel( 'Alert', counts.alert ),
			key: 'alert',
		},
		{
			label: getFilterLabel( 'Critical', counts.critical ),
			key: 'critical',
		},
		{
			label: getFilterLabel( 'Error', counts.error ),
			key: 'error',
		},
		{
			label: getFilterLabel( 'Warning', counts.warning ),
			key: 'warning',
		},
		{
			label: getFilterLabel( 'Notice', counts.notice ),
			key: 'notice',
		},
		{
			label: getFilterLabel( 'Info', counts.info ),
			key: 'info',
		},
		{
			label: getFilterLabel( 'Debug', counts.debug ),
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
							<JsonOutput data={ row.context } />
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
