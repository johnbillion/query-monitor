import clsx from 'clsx';
import { NonTabularPanel } from '../panels/non-tabular-panel';
import { FilterLink } from '../components/filter-link';
import { Icon } from '../components/icon';
import * as Utils from '../utils';
import { Duration } from '../components/duration';
import { iPanelData, iSettings } from '../panels/panels';
import { Fragment } from 'preact';

import {
	__,
	sprintf,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const overviewMenu = (): PanelMenuItem[] => [ {
	id: 'overview',
	panel: 'overview',
	title: __( 'Overview', 'query-monitor' ),
	adminBar: false,
} ];

type OverviewProps = {
	data: iPanelData;
	settings: iSettings;
};

const UsageBar = ( { usage, warn }: { usage: number; warn: boolean } ) => (
	<div className="qm-usage-bar">
		<div
			className={ clsx( 'qm-usage-bar-fill', { 'qm-usage-bar-warn': warn } ) }
			style={ { width: `${ Math.min( usage, 100 ) }%` } }
		/>
	</div>
);

export const Overview = ( { data, settings }: OverviewProps ) => {
	// Get data from various collectors
	const dbQueriesData = data.db_queries?.data;
	const dbQueryTypes = dbQueriesData?.rows ? Utils.getQueryTypes( dbQueriesData.rows ) : {};
	const cacheData = data.cache?.data;
	const httpData = data.http?.data;
	const rawRequestData = data.raw_request?.data;

	if ( ! data.overview ) {
		return null;
	}

	const overviewData = data.overview.data;

	const timeTaken = overviewData.time_taken ?? 0;
	const memory = overviewData.memory;
	const timeLimit = overviewData.time_limit;
	const memoryLimit = overviewData.memory_limit;
	const timeUsage = overviewData.time_usage;
	const memoryUsage = overviewData.memory_usage;
	const displayTimeUsageWarning = timeUsage >= 75;
	const displayMemoryUsageWarning = memoryUsage >= 75;

	return (
		<NonTabularPanel title={ __( 'Overview', 'query-monitor' ) }>
			{ rawRequestData && rawRequestData.response?.status != null && (
				<div className="qm-boxed">
					<section id="qm-overview-raw-request">
						<h3>
							{ sprintf(
								'%1$s %2$s → %3$s',
								rawRequestData.request.method,
								rawRequestData.request.url,
								rawRequestData.response.status || __( 'Unknown HTTP Response Code', 'query-monitor' )
							) }
						</h3>
					</section>
				</div>
			) }
			<div className="qm-dashboard-grid">
				<section className="qm-dashboard-metric">
					<h3>{ __( 'Page Generation Time', 'query-monitor' ) }</h3>
					<p className="qm-dashboard-value">
						<Duration value={ timeTaken } secondsLabel />
					</p>
					{ timeLimit > 0 ? (
						<>
							<UsageBar usage={ timeUsage } warn={ displayTimeUsageWarning } />
							<p>
								<span className={ displayTimeUsageWarning ? 'qm-warn' : 'qm-info' }>
									{ displayTimeUsageWarning && <Icon name="warning" /> }
									{ sprintf(
										/* translators: 1: Percentage of time limit used, 2: Time limit in seconds */
										__( '%1$s%% of %2$ss limit', 'query-monitor' ),
										Utils.numberFormat( timeUsage, 1 ),
										Utils.numberFormat( timeLimit )
									) }
								</span>
							</p>
						</>
					) : (
						<p>
							<span className="qm-warn">
								<Icon name="warning" />
								{ sprintf(
									/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
									__( 'No execution time limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
									'max_execution_time',
									'0'
								) }
							</span>
						</p>
					) }
				</section>

				<section className="qm-dashboard-metric">
					<h3>{ __( 'Peak Memory Usage', 'query-monitor' ) }</h3>
					{ memory === 0 ? (
						<p className="qm-dashboard-value">{ __( 'Unknown', 'query-monitor' ) }</p>
					) : (
						<>
							<p className="qm-dashboard-value">
								{ sprintf(
									/* translators: %s: Memory in megabytes */
									__( '%s MB', 'query-monitor' ),
									Utils.numberFormat( memory / 1024 / 1024, 1 )
								) }
							</p>
							{ memoryLimit > 0 ? (
								<>
									<UsageBar usage={ memoryUsage } warn={ displayMemoryUsageWarning } />
									<p>
										<span className={ displayMemoryUsageWarning ? 'qm-warn' : 'qm-info' }>
											{ displayMemoryUsageWarning && <Icon name="warning" /> }
											{ sprintf(
												/* translators: 1: Percentage of memory limit used, 2: Memory limit in megabytes */
												__( '%1$s%% of %2$s MB limit', 'query-monitor' ),
												Utils.numberFormat( memoryUsage, 1 ),
												Utils.numberFormat( memoryLimit / 1024 / 1024 )
											) }
										</span>
									</p>
								</>
							) : (
								<p>
									<span className="qm-warn">
										<Icon name="warning" />
										{ sprintf(
											/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
											__( 'No memory limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
											'memory_limit',
											'0'
										) }
									</span>
								</p>
							) }
						</>
					) }
				</section>

				<section className="qm-dashboard-metric">
					<h3>{ __( 'Database Queries', 'query-monitor' ) }</h3>
					{ dbQueriesData?.rows?.length ? (
						<>
							<p className="qm-dashboard-value">
								<FilterLink
									targetPanel="db_queries"
									filterName="type"
									filterValue=""
								>
									{ Utils.numberFormat( dbQueriesData.total_qs ) }
								</FilterLink>
							</p>
							<p>
								<Duration value={ dbQueriesData.rows.reduce( ( acc, row ) => acc + row.ltime, 0 ) } secondsLabel />
							</p>
							<p>
								{ Object.keys( dbQueryTypes ).length > 1 && Object.entries( dbQueryTypes ).map( ( [ typeName, typeCount ] ) => (
									<Fragment key={ typeName }>
										<FilterLink
											key={ typeName }
											targetPanel="db_queries"
											filterName="sql"
											filterValue={ typeName }
										>
											{ sprintf( '%1$s: %2$s', typeName, Utils.numberFormat( typeCount ) ) }
										</FilterLink>
										<br />
									</Fragment>
								) ) }
							</p>
						</>
					) : (
						<p className="qm-dashboard-value">
							{ dbQueriesData?.total_qs ? (
								Utils.numberFormat( dbQueriesData.total_qs )
							) : (
								<em>{ __( 'None', 'query-monitor' ) }</em>
							) }
						</p>
					) }
				</section>

				{ httpData && (
					<section className="qm-dashboard-metric">
						<h3>{ __( 'HTTP API Calls', 'query-monitor' ) }</h3>
						{ httpData.http?.length ? (
							<>
								<p className="qm-dashboard-value">
									<FilterLink
										targetPanel="http"
										filterName="type"
										filterValue=""
									>
										{ Utils.numberFormat( httpData.http.length ) }
									</FilterLink>
								</p>
								<p>
									<Duration value={ httpData.ltime } secondsLabel />
								</p>
							</>
						) : (
							<p className="qm-dashboard-value">
								<em>{ __( 'None', 'query-monitor' ) }</em>
							</p>
						) }
					</section>
				) }

				<section className="qm-dashboard-metric">
					<h3>{ __( 'Object Cache', 'query-monitor' ) }</h3>
					{ cacheData ? (
						<>
							{ cacheData.stats && cacheData.cache_hit_percentage !== undefined ? (
								<>
									<div className="qm-dashboard-value">
										{ sprintf(
											/* translators: %s: Cache hit percentage */
											__( '%s%% hit rate', 'query-monitor' ),
											Utils.numberFormat( cacheData.cache_hit_percentage, 1 )
										) }
										<UsageBar usage={ cacheData.cache_hit_percentage } warn={ cacheData.cache_hit_percentage < 75 } />
									</div>
									<p>
										{ sprintf(
											/* translators: 1: Number of cache hits, 2: Number of cache misses */
											__( '%1$s hits, %2$s misses', 'query-monitor' ),
											Utils.numberFormat( cacheData.stats.cache_hits as number, 0 ),
											Utils.numberFormat( cacheData.stats.cache_misses as number, 0 )
										) }
									</p>
								</>
							) : (
								<p className="qm-dashboard-value qm-dashboard-value-text">
									{ __( 'No stats available', 'query-monitor' ) }
								</p>
							) }
							{ cacheData.has_object_cache ? (
								<p>
									<span className="qm-info">
										<a
											href={ `${ settings.admin_url }network/plugins.php?plugin_status=dropins` }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ __( 'Persistent object cache in use', 'query-monitor' ) }
										</a>
									</span>
								</p>
							) : (
								<>
									<p>
										{ __( 'No persistent object cache', 'query-monitor' ) }
									</p>
									{ Object.entries( cacheData.object_cache_extensions ).some( ( [ , value ] ) => value ) && (
										Object.entries( cacheData.object_cache_extensions ).filter( ( [ , value ] ) => value ).map( ( [ name ] ) => (
											<p key={ name }>
												{ sprintf(
													/* translators: 1: PHP extension name */
													__( 'The %1$s object cache extension for PHP is installed but is not in use by WordPress. You should install a %1$s plugin.', 'query-monitor' ),
													name
												) }
											</p>
										) )
									) }
								</>
							) }
						</>
					) : (
						<p className="qm-dashboard-value qm-dashboard-value-text">
							{ __( 'Not available', 'query-monitor' ) }
						</p>
					) }
				</section>

				{ cacheData && (
					<section className="qm-dashboard-metric">
						<h3>{ __( 'Opcode Cache', 'query-monitor' ) }</h3>
						{ cacheData.has_opcode_cache ? (
							<>
								<p className="qm-dashboard-value qm-dashboard-value-text">
									{ __( 'Enabled', 'query-monitor' ) }
								</p>
								{ Object.entries( cacheData.opcode_cache_extensions ).filter( ( [ , value ] ) => value ).map( ( [ name ] ) => (
									<p key={ name }>
										{ name }
									</p>
								) ) }
							</>
						) : (
							<>
								<p className="qm-dashboard-value qm-dashboard-value-text">
									<span className="qm-warn">
										<Icon name="warning" />
										{ __( 'Disabled', 'query-monitor' ) }
									</span>
								</p>
								<p>
									{ __( 'Speak to your web host about enabling an opcode cache such as OPcache.', 'query-monitor' ) }
								</p>
							</>
						) }
					</section>
				) }
			</div>
		</NonTabularPanel>
	);
};
