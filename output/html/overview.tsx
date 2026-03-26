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
	_x,
} from '@wordpress/i18n';

type OverviewProps = {
	data: iPanelData;
	settings: iSettings;
};

export const Overview = ( { data, settings }: OverviewProps ) => {
	// Get data from various collectors
	const dbQueriesData = data.db_queries?.data;
	const cacheData = data.cache?.data;
	const httpData = data.http?.data;
	const rawRequestData = data.raw_request?.data;

	if ( ! data.overview ) {
		return null;
	}

	const overviewData = data.overview.data;

	const timeTaken = overviewData.time_taken || 0;
	const memory = overviewData.memory || 0;
	const timeLimit = overviewData.time_limit || 0;
	const memoryLimit = overviewData.memory_limit || 0;
	const timeUsage = overviewData.time_usage || 0;
	const memoryUsage = overviewData.memory_usage || 0;
	const displayTimeUsageWarning = timeUsage >= 75;
	const displayMemoryUsageWarning = memoryUsage >= 75;

	return (
		<NonTabularPanel>
			<div className="qm-boxed">
				{ rawRequestData && rawRequestData.response?.status != null && (
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
				) }
			</div>
			<div className="qm-grid">
				<section>
					<h3>{ __( 'Page Generation Time', 'query-monitor' ) }</h3>
					<p>
						<Duration value={ timeTaken } secondsLabel />
						{ timeLimit > 0 ? (
							<>
								<br />
								<span className={ displayTimeUsageWarning ? 'qm-warn' : 'qm-info' }>
									{ displayTimeUsageWarning && <Icon name="warning" /> }
									{ sprintf(
										/* translators: 1: Percentage of time limit used, 2: Time limit in seconds */
										__( '%1$s%% of %2$ss limit', 'query-monitor' ),
										Utils.numberFormat( timeUsage, 1 ),
										Utils.numberFormat( timeLimit )
									) }
								</span>
							</>
						) : (
							<>
								<br />
								<span className="qm-warn">
									<Icon name="warning" />
									{ sprintf(
										/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
										__( 'No execution time limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
										'max_execution_time',
										'0'
									) }
								</span>
							</>
						) }
					</p>
				</section>

				<section>
					<h3>{ __( 'Peak Memory Usage', 'query-monitor' ) }</h3>
					<p>
						{ memory === 0 ? (
							__( 'Unknown', 'query-monitor' )
						) : (
							<>
								{ sprintf(
									/* translators: 1: Memory used in bytes, 2: Memory used in megabytes */
									__( '%1$s bytes (%2$s MB)', 'query-monitor' ),
									Utils.numberFormat( memory ),
									Utils.numberFormat( memory / 1024 / 1024, 1 )
								) }
								{ memoryLimit > 0 ? (
									<>
										<br />
										<span className={ displayMemoryUsageWarning ? 'qm-warn' : 'qm-info' }>
											{ displayMemoryUsageWarning && <Icon name="warning" /> }
											{ sprintf(
												/* translators: 1: Percentage of memory limit used, 2: Memory limit in megabytes */
												__( '%1$s%% of %2$s MB server limit', 'query-monitor' ),
												Utils.numberFormat( memoryUsage, 1 ),
												Utils.numberFormat( memoryLimit / 1024 / 1024 )
											) }
										</span>
									</>
								) : (
									<>
										<br />
										<span className="qm-warn">
											<Icon name="warning" />
											{ sprintf(
												/* translators: 1: Name of the PHP directive, 2: Value of the PHP directive */
												__( 'No memory limit. The %1$s PHP configuration directive is set to %2$s.', 'query-monitor' ),
												'memory_limit',
												'0'
											) }
										</span>
									</>
								) }
							</>
						) }
					</p>
				</section>

				<section>
					<h3>{ __( 'Database Queries', 'query-monitor' ) }</h3>
					{ dbQueriesData?.rows?.length ? (
						<>
							<p>
								<Duration value={ dbQueriesData.rows.reduce( ( acc, row ) => acc + row.ltime, 0 ) } secondsLabel />
							</p>
							<p>
								{ Object.keys( dbQueriesData.types ).length > 1 && Object.entries( dbQueriesData.types ).map( ( [ typeName, typeCount ] ) => {
									if ( typeName === 'SELECT' && Object.keys( dbQueriesData.types ).length === 1 ) {
										return null;
									}
									return (
										<Fragment key={ typeName }>
											<FilterLink
												targetPanel="db_queries"
												filterName="sql"
												filterValue={ typeName }
											>
												{ sprintf( '%1$s: %2$s', typeName, Utils.numberFormat( typeCount ) ) }
											</FilterLink>
											<br />
										</Fragment>
									);
								} ) }
								<FilterLink
									targetPanel="db_queries"
									filterName="type"
									filterValue=""
								>
									{ sprintf(
										'%1$s: %2$s',
										_x( 'Total', 'database queries', 'query-monitor' ),
										Utils.numberFormat( dbQueriesData.total_qs )
									) }
								</FilterLink>
							</p>
						</>
					) : (
						<p><em>{ __( 'None', 'query-monitor' ) }</em></p>
					) }
				</section>

				{ httpData && (
					<section>
						<h3>{ __( 'HTTP API Calls', 'query-monitor' ) }</h3>
						{ httpData.http?.length ? (
							<>
								<p>
									<Duration value={ httpData.ltime } secondsLabel />
								</p>
								<FilterLink
									targetPanel="http"
									filterName="type"
									filterValue=""
								>
									{ sprintf(
										'%1$s: %2$s',
										_x( 'Total', 'HTTP API calls', 'query-monitor' ),
										Utils.numberFormat( httpData.http.length )
									) }
								</FilterLink>
							</>
						) : (
							<p><em>{ __( 'None', 'query-monitor' ) }</em></p>
						) }
					</section>
				) }

				<section>
					<h3>{ __( 'Object Cache', 'query-monitor' ) }</h3>
					{ cacheData ? (
						<>
							{ cacheData.stats && cacheData.cache_hit_percentage !== undefined && (
								<p>
									{ sprintf(
										/* translators: 1: Cache hit rate percentage, 2: number of cache hits, 3: number of cache misses */
										__( '%1$s%% hit rate (%2$s hits, %3$s misses)', 'query-monitor' ),
										Utils.numberFormat( cacheData.cache_hit_percentage, 1 ),
										Utils.numberFormat( cacheData.stats.cache_hits as number, 0 ),
										Utils.numberFormat( cacheData.stats.cache_misses as number, 0 )
									) }
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
											{ __( 'Persistent object cache plugin in use', 'query-monitor' ) }
										</a>
									</span>
								</p>
							) : (
								<>
									<p>
										<span className="qm-warn">
											<Icon name="warning" />
											{ __( 'Persistent object cache plugin not in use', 'query-monitor' ) }
										</span>
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
									{ ! Object.values( cacheData.object_cache_extensions ).some( value => value ) && (
										<p>
											{ __( 'Speak to your web host about enabling an object cache extension such as Redis or Memcached.', 'query-monitor' ) }
										</p>
									) }
								</>
							) }
						</>
					) : (
						<p>{ __( 'Object cache statistics are not available', 'query-monitor' ) }</p>
					) }
				</section>

				{ cacheData && (
					<section>
						<h3>{ __( 'Opcode Cache', 'query-monitor' ) }</h3>
						{ cacheData.has_opcode_cache ? (
							Object.entries( cacheData.opcode_cache_extensions ).filter( ( [ , value ] ) => value ).map( ( [ name ] ) => (
								<p key={ name }>
									{ sprintf(
										/* translators: %s: Name of cache driver */
										__( 'Opcode cache in use: %s', 'query-monitor' ),
										name
									) }
								</p>
							) )
						) : (
							<>
								<p>
									<span className="qm-warn">
										<Icon name="warning" />
										{ __( 'Opcode cache not in use', 'query-monitor' ) }
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
