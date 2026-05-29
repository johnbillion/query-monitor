import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Toggler } from '../components/toggler';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { JsonOutput } from '../components/json-output';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import { __, _n, sprintf } from '@wordpress/i18n';

const warningLevels = [
	'emergency',
	'alert',
	'critical',
	'error',
	'warning',
];

export const Logger = ( { data }: PanelProps<DataTypes['logger']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.logs || ! data.logs.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
				<p>
					<a href="https://querymonitor.com/wordpress-debugging/profiling-and-logging/" target="_blank" rel="noopener noreferrer" className="qm-external-link">
						{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
					</a>
				</p>
			</EmptyPanel>
		);
	}

	const filterOptions = buildCountedFilters( data.logs, ( row ) => row.level, [
		{ key: 'emergency', label: 'Emergency' },
		{ key: 'alert', label: 'Alert' },
		{ key: 'critical', label: 'Critical' },
		{ key: 'error', label: 'Error' },
		{ key: 'warning', label: 'Warning' },
		{ key: 'notice', label: 'Notice' },
		{ key: 'info', label: 'Info' },
		{ key: 'debug', label: 'Debug' },
	] );

	return <TabularPanel
		title={ __( 'Logs', 'query-monitor' ) }
		cols={ {
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => warningLevels.includes( row.level )
					? <Warning>{ row.level }</Warning>
					: row.level,
				filters: {
					options: [ filterOptions ],
					callback: ( row, filter ) => row.level === filter,
				},
			},
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => <pre>{ row.message }</pre>,
			},
			context: {
				heading: __( 'Context', 'query-monitor' ),
				className: 'qm-has-toggle',
				render: ( row ) => {
					if ( ! row.context || Object.keys( row.context ).length === 0 ) {
						return '';
					}
					return (
						<Toggler summary={ sprintf(
							/* translators: %d: Number of items */
							_n( '%d item', '%d items', Object.keys( row.context ).length, 'query-monitor' ),
							Object.keys( row.context ).length
						) }>
							<JsonOutput data={ row.context } />
						</Toggler>
					);
				},
			},
			caller: getCallerCol( data.logs, settings ),
			component: getComponentCol( data.logs ),
		} }
		data={ data.logs }
		rowHasError={ ( row ) => warningLevels.includes( row.level ) }
	/>
};
