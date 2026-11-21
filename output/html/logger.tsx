import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Toggler } from '../components/toggler';
import { Warning } from '../components/warning';
import { EmptyPanel } from '../panels/empty-panel';
import { JsonOutput } from '../components/json-output';
import { buildCountedFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as Utils from '../utils';
import { useContext } from 'preact/hooks';
import { __, _n, sprintf } from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

const loggerWarningLevels = Utils.logLevels.filter( ( level ) => level.isError ).map( ( level ) => level.key );

const isWarningLog = ( level: string ): boolean => loggerWarningLevels.includes( level );

export const loggerMenu = ( data: DataTypes['logger'] ): PanelMenuItem[] => {
	const logs = data.logs ?? [];
	const warningCount = logs.filter( ( log ) => isWarningLog( log.level ) ).length;

	return [ {
		id: 'logger',
		panel: 'logger',
		title: __( 'Logs', 'query-monitor' ),
		warning_count: warningCount || null,
		ok_count: ( logs.length - warningCount ) || null,
	} ];
};

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

	const filterOptions = buildCountedFilters( data.logs, ( row ) => row.level, Utils.logLevels );

	return <TabularPanel
		title={ __( 'Logs', 'query-monitor' ) }
		cols={ {
			level: {
				heading: __( 'Level', 'query-monitor' ),
				render: ( row ) => Utils.logRowHasError( row )
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
		rowHasError={ Utils.logRowHasError }
	/>
};
