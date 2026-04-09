import { type JSX } from 'preact';
import { useContext } from 'preact/hooks';
import { TabularPanel } from '../panels/tabular-panel';
import { Component, HTTP, Logger, PHP_Errors, QueryRow, Timing, Transients } from '../data-types';
import { Cols, componentFilterCallback, deriveComponentFilters } from '../table';
import { iPanelData, iSettings } from '../panels/panels';
import { JumpLink } from '../components/jump-link';
import { Duration } from '../components/duration';
import { MainContext } from '../contexts/main-context';
import { PanelContext } from '../contexts/panel-context';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface TimelineItem {
	label: string | JSX.Element[];
	time: number;
	duration: number | null;
	category: 'db' | 'http' | 'php-error' | 'timing' | 'action' | 'log' | 'transient';
	panel: string;
	rowIndex?: number;
	component?: Component;
}

type TimelineProps = {
	data: iPanelData;
	settings: iSettings;
};

const categoryColors: Record<TimelineItem['category'], string> = {
	'db': 'var(--qm-timeline-db)',
	'http': 'var(--qm-timeline-http)',
	'php-error': 'var(--qm-timeline-php-error)',
	'timing': 'var(--qm-timeline-timing)',
	'action': 'var(--qm-timeline-action)',
	'log': 'var(--qm-timeline-log)',
	'transient': 'var(--qm-timeline-transient)',
};

const categoryLabels: Record<TimelineItem['category'], string> = {
	'db': __( 'Database Queries', 'query-monitor' ),
	'http': __( 'HTTP Requests', 'query-monitor' ),
	'php-error': __( 'PHP Errors', 'query-monitor' ),
	'timing': __( 'Timings', 'query-monitor' ),
	'action': __( 'Notable Actions', 'query-monitor' ),
	'log': __( 'Logs', 'query-monitor' ),
	'transient': __( 'Transient Updates', 'query-monitor' ),
};

const segmentLabels: Record<string, string> = {
	'init': __( 'Initialization', 'query-monitor' ),
	'request': __( 'Request', 'query-monitor' ),
	'query': __( 'Query', 'query-monitor' ),
	'template': __( 'Template', 'query-monitor' ),
	'shutdown': __( 'Shutdown', 'query-monitor' ),
};

const buildTimelineItems = (
	dbRows?: QueryRow[] | null,
	httpRequests?: HTTP['http'] | null,
	phpErrors?: PHP_Errors['errors'] | null,
	timings?: Timing['timing'] | null,
	logs?: Logger['logs'] | null,
	transients?: Transients['trans'] | null,
): TimelineItem[] => {
	const items: TimelineItem[] = [];

	if ( dbRows ) {
		for ( let i = 0; i < dbRows.length; i++ ) {
			const row = dbRows[ i ];

			if ( ! row.trace ) {
				continue;
			}

			items.push( {
				label: row.sql,
				time: row.trace.time,
				duration: row.ltime,
				category: 'db',
				panel: 'db_queries',
				rowIndex: i,
				component: row.trace.component,
			} );
		}
	}

	if ( httpRequests ) {
		for ( let i = 0; i < httpRequests.length; i++ ) {
			const req = httpRequests[ i ];

			items.push( {
				label: req.url,
				time: req.trace.time,
				duration: req.ltime,
				category: 'http',
				panel: 'http',
				rowIndex: i,
				component: req.trace.component,
			} );
		}
	}

	if ( phpErrors ) {
		let i = 0;
		for ( const error of Object.values( phpErrors ) ) {
			if ( ! error.trace ) {
				i++;
				continue;
			}

			items.push( {
				label: `[${ error.level }] ${ error.message }`,
				time: error.trace.time,
				duration: null,
				category: 'php-error',
				panel: 'php_errors',
				rowIndex: i,
				component: error.trace.component,
			} );
			i++;
		}
	}

	if ( timings ) {
		for ( let i = 0; i < timings.length; i++ ) {
			const timing = timings[ i ];

			items.push( {
				label: timing.function,
				time: timing.start_time * 1000,
				duration: timing.function_time,
				category: 'timing',
				panel: 'timing',
				rowIndex: i,
				component: timing.trace.component,
			} );
		}
	}

	if ( logs ) {
		for ( let i = 0; i < logs.length; i++ ) {
			const log = logs[ i ];

			items.push( {
				label: `[${ log.level }] ${ log.message }`,
				time: log.trace.time,
				duration: null,
				category: 'log',
				panel: 'logger',
				rowIndex: i,
				component: log.trace.component,
			} );
		}
	}

	if ( transients ) {
		for ( let i = 0; i < transients.length; i++ ) {
			const transient = transients[ i ];

			items.push( {
				label: transient.name,
				time: transient.trace.time,
				duration: null,
				category: 'transient',
				panel: 'transients',
				rowIndex: i,
				component: transient.trace.component,
			} );
		}
	}

	return items;
};

export const Timeline = ( { data }: TimelineProps ) => {
	const { filters, setFilter } = useContext( PanelContext );
	const componentFilter = filters['component'] ?? '';
	const { timelineHiddenCategories, setTimelineHiddenCategories } = useContext( MainContext );
	const hiddenCategories = new Set< TimelineItem['category'] >( timelineHiddenCategories as TimelineItem['category'][] );

	const dbQueriesData = data.db_queries?.data;
	const httpData = data.http?.data;
	const phpErrorsData = data.php_errors?.data;
	const timingData = data.timing?.data;
	const loggerData = data.logger?.data;
	const transientsData = data.transients?.data;

	if ( ! data.overview ) {
		return null;
	}

	const overviewData = data.overview.data;
	const totalTime = overviewData.time_taken || 0;
	const actions = overviewData.actions;
	const segments = overviewData.segments;

	const items = buildTimelineItems(
		dbQueriesData?.rows,
		httpData?.http,
		phpErrorsData?.errors,
		timingData?.timing,
		loggerData?.logs,
		transientsData?.trans,
	);

	// Merge action markers into items.
	const allItems = [ ...items ];
	if ( actions ) {
		for ( const [ name, occurrences ] of Object.entries( actions ) ) {
			for ( const occurrence of occurrences ) {
				const durationSecs = occurrence.end !== undefined
					? ( occurrence.end - occurrence.start ) / 1000
					: null;
				allItems.push( {
					label: name,
					time: occurrence.start,
					duration: durationSecs,
					category: 'action',
					panel: 'overview',
				} );
			}
		}
	}

	// Determine which categories are present.
	const presentCategories = [ ...new Set( allItems.map( ( item ) => item.category ) ) ] as TimelineItem['category'][];

	const componentFilters = deriveComponentFilters( allItems, ( item ) => item.component );

	if ( ! allItems.length || ! totalTime ) {
		return null;
	}

	const totalTimeMs = totalTime * 1000;
	const visible = allItems.filter( ( item ) =>
		! hiddenCategories.has( item.category )
		&& ( ! componentFilter || ! item.component || componentFilterCallback( item.component, componentFilter ) )
	);
	const sorted = [ ...visible ].sort( ( a, b ) => a.time - b.time );

	// Build segment boundaries as sorted [time, label] pairs.
	const segmentBoundaries: { time: number; label: string }[] = [];
	if ( segments ) {
		for ( const [ key, time ] of Object.entries( segments ) ) {
			if ( time !== undefined && segmentLabels[ key ] ) {
				segmentBoundaries.push( { time, label: segmentLabels[ key ] } );
			}
		}
		segmentBoundaries.sort( ( a, b ) => a.time - b.time );
	}

	// Calculate tick marks at round intervals.
	const maxTicks = 6;
	const candidates = [ 1, 2, 5, 10, 20, 25, 50, 100, 200, 500, 1000, 2000 ];
	const tickInterval = candidates.find( c => Math.floor( totalTimeMs / c ) <= maxTicks ) ?? 1000;
	const ticks: number[] = [];
	for ( let t = 0; t <= totalTimeMs; t += tickInterval ) {
		ticks.push( t );
	}

	const toggleCategory = ( category: TimelineItem['category'] ) => {
		const next = new Set( hiddenCategories );
		if ( next.has( category ) ) {
			next.delete( category );
		} else {
			next.add( category );
		}
		setTimelineHiddenCategories( [ ...next ] );
	};

	const cols: Cols<TimelineItem> = {
		timeline: {
			heading: __( 'Timeline', 'query-monitor' ),
			render: ( item ) => {
				// Using 99% as the upper left position to allow short events at the end of the timeline to remain visible.
				const leftPct = ( item.time / totalTimeMs ) * 99;
				const durationMs = ( item.duration ?? 0 ) * 1000;
				const widthPct = ( durationMs / totalTimeMs ) * 100;
				const isPoint = item.duration === null || widthPct < 0.3;
				const color = categoryColors[ item.category ];
				// If the bar is wide enough, overlay the label on top of it.
				const isWide = widthPct > 30;
				// Prefer right-aligned labels unless there's more space on the left.
				const displayLabelLeft = ! isWide && ( leftPct > 60 || leftPct > ( 100 - leftPct - widthPct ) );

				return (
					<>
						<span
							className={ `timeline-bar ${ isPoint ? 'timeline-point' : '' }` }
							style={ {
								left: `${ leftPct }%`,
								width: isPoint ? undefined : `${ Math.max( widthPct, 0.3 ) }%`,
								backgroundColor: color,
							} }
						/>
						<span
							className={ `timeline-bar-label ${ isWide ? 'timeline-bar-label-overlay' : '' }` }
							style={ isWide
								? { left: `${ leftPct }%`, width: `${ widthPct }%` }
								: displayLabelLeft
									? { right: `${ 100 - leftPct }%`, textAlign: 'right' }
									: { left: `${ leftPct + ( isPoint ? 0.3 : Math.max( widthPct, 0.3 ) ) }%` }
							}
						>
							<span className="timeline-bar-label-text">
								{ item.rowIndex !== undefined ? (
									<JumpLink targetPanel={ item.panel } rowIndex={ item.rowIndex }>
										{ item.label }
									</JumpLink>
								) : (
									item.label
								) }
							</span>
							{ item.duration !== null && (
								<span className="timeline-bar-label-time">
									<Duration value={ durationMs / 1000 } />
								</span>
							) }
						</span>
					</>
				);
			},
		},
	};

	const header = (
		<>
			<div className="timeline-header">
				<div className="timeline-filters">
					{ componentFilters.length > 0 && (
						<div className="qm-filter-container">
							<label htmlFor="qm-filter-timeline-component" className="qm-screen-reader-text">
								{ __( 'Component', 'query-monitor' ) }
							</label>
							<select
								id="qm-filter-timeline-component"
								className="qm-filter"
								value={ componentFilter }
								onChange={ ( e ) => setFilter( 'component', e.currentTarget.value ) }
							>
								<option value="">{ __( 'All components', 'query-monitor' ) }</option>
								{ componentFilters.map( ( group, gi ) => (
									<>
										<hr/>
										{ group.map( ( filter ) => (
											<option
												key={ `${ gi }-${ filter.key }` }
												value={ filter.key }
											>{ filter.label }</option>
										) ) }
									</>
								) ) }
							</select>
						</div>
					) }
					{ presentCategories.map( ( category ) => (
						<label key={ category } className="timeline-filter">
							<input
								type="checkbox"
								checked={ ! hiddenCategories.has( category ) }
								onChange={ () => toggleCategory( category ) }
								style={ { accentColor: categoryColors[ category ] } }
							/>
							{ categoryLabels[ category ] }
						</label>
					) ) }
				</div>
			</div>
			<div className="timeline-axis">
				{ ticks.map( ( tick, i ) => (
					<span
						key={ i }
						className="timeline-tick"
						style={ { left: `${ ( tick / totalTimeMs ) * 100 }%` } }
					>
						<Duration value={ tick / 1000 } />
					</span>
				) ) }
			</div>
			{ segmentBoundaries.length > 0 && (
				<div className="timeline-segments">
					{ segmentBoundaries.map( ( seg, i ) => {
						const nextTime = i < segmentBoundaries.length - 1
							? segmentBoundaries[ i + 1 ].time
							: totalTimeMs;
						const leftPct = ( seg.time / totalTimeMs ) * 100;
						const widthPct = ( ( nextTime - seg.time ) / totalTimeMs ) * 100;

						return (
							<div
								key={ i }
								className="timeline-segment"
								style={ {
									left: `${ leftPct }%`,
									width: `${ widthPct }%`,
								} }
							>
								<span className="timeline-segment-label">
									{ seg.label }
								</span>
							</div>
						);
					} ) }
				</div>
			) }
		</>
	);

	return (
		<TabularPanel
			title={ __( 'Timeline', 'query-monitor' ) }
			cols={ cols }
			data={ sorted }
			footer={ () => null }
			header={ header }
		/>
	);
};
