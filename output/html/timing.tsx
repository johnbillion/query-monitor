import { MainContext } from '../contexts/main-context';
import { TabularPanel } from '../panels/tabular-panel';
import { Duration } from '../components/duration';
import { ApproximateSize } from '../components/approximate-size';
import { EmptyPanel } from '../panels/empty-panel';
import { getCallerCol, getComponentCol } from '../table';
import { Warning } from '../components/warning';
import { DataTypes, Backtrace } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
} from '@wordpress/i18n';

interface LapData {
	time: number;
	time_used: number;
	memory: number;
	memory_used: number;
	data: unknown;
}

interface TimingRow {
	function: string;
	function_time: number;
	function_memory: number;
	laps: Record<string, LapData>;
	trace: Backtrace;
	start_time: number;
	end_time: number;
	isLap?: boolean;
	lapName?: string;
	lapData?: LapData;
}

interface WarningRow {
	function: string;
	message: string;
	trace: Backtrace;
}

type FlattenedRow = TimingRow | WarningRow;

export const Timing = ( { data }: PanelProps<DataTypes['timing']> ) => {
	const { settings } = useContext( MainContext );

	if ( ( ! data.timing || data.timing.length === 0 ) && ( ! data.warning || data.warning.length === 0 ) ) {
		return <EmptyPanel>
			<p>
				{ __( 'No data logged.', 'query-monitor' ) }
			</p>
			<p>
				<a href="https://querymonitor.com/wordpress-debugging/profiling-and-logging/">
					{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
				</a>
			</p>
		</EmptyPanel>
	}

	// Flatten timing data to include laps as separate rows
	const flattenedData: FlattenedRow[] = [];

	if ( data.timing ) {
		data.timing.forEach( row => {
			// Add the main timing row
			flattenedData.push( row );

			// Add lap rows if they exist
			if ( row.laps && Object.keys( row.laps ).length > 0 ) {
				Object.entries( row.laps ).forEach( ( [ lapName, lap ] ) => {
					flattenedData.push( {
						...row,
						isLap: true,
						lapName: lapName,
						lapData: lap,
						function: `${row.function}: ${lapName}`,
					} );
				} );
			}
		} );
	}

	// Add warning data
	if ( data.warning ) {
		flattenedData.push( ...data.warning );
	}

	return <TabularPanel
		title={ __( 'Timing', 'query-monitor' ) }
		cols={ {
			function: {
				heading: __( 'Tracked Function', 'query-monitor' ),
				render: ( row ) => {
					if ( 'message' in row ) {
						// This is a warning row
						return (
							<>
								<code>{ row.function }</code>
								<br />
								<Warning>
									{ row.message }
								</Warning>
							</>
						);
					}
					// Check if this is a lap row
					if ( row.isLap ) {
						return (
							<code>&mdash;&nbsp;{ row.function }</code>
						);
					}
					// This is a main timing row
					return (
						<code>{ row.function }</code>
					);
				},
			},
			start_time: {
				className: 'qm-num',
				heading: __( 'Started', 'query-monitor' ),
				render: ( row ) => {
					// Lap rows don't show start time
					if ( 'isLap' in row && row.isLap ) {
						return '';
					}
					return 'start_time' in row ? <Duration value={ row.start_time } /> : '';
				},
			},
			end_time: {
				className: 'qm-num',
				heading: __( 'Stopped', 'query-monitor' ),
				render: ( row ) => {
					// Lap rows don't show end time
					if ( 'isLap' in row && row.isLap ) {
						return '';
					}
					return 'end_time' in row ? <Duration value={ row.end_time } /> : '';
				},
			},
			function_time: {
				className: 'qm-num',
				heading: __( 'Time', 'query-monitor' ),
				render: ( row ) => {
					// For lap rows, show lap-specific time
					if ( 'isLap' in row && row.isLap && row.lapData ) {
						return <Duration value={ row.lapData.time_used } />;
					}
					return 'function_time' in row ? <Duration value={ row.function_time } /> : '';
				},
			},
			function_memory: {
				className: 'qm-num',
				heading: __( 'Memory', 'query-monitor' ),
				render: ( row ) => {
					// For lap rows, show lap-specific memory
					if ( 'isLap' in row && row.isLap && row.lapData ) {
						return <ApproximateSize value={ row.lapData.memory_used } />;
					}
					return 'function_memory' in row ? <ApproximateSize value={ row.function_memory } /> : '';
				},
			},
			caller: {
				...getCallerCol( flattenedData, settings ),
				render: ( row, i ) => {
					// Don't show caller for lap rows
					if ( 'isLap' in row && row.isLap ) {
						return '';
					}
					return getCallerCol( flattenedData, settings ).render( row, i );
				},
			},
			component: {
				...getComponentCol( flattenedData ),
				render: ( row, i ) => {
					// Don't show component for lap rows
					if ( 'isLap' in row && row.isLap ) {
						return '';
					}
					return getComponentCol( flattenedData ).render( row, i );
				},
			},
		} }
		data={ flattenedData }
		rowHasError={ ( row ) => 'message' in row }
	/>;
};
