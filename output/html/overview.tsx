import {
	NonTabularPanel,
	FilterLink,
	Icon,
	Warning,
	Utils,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import { iPanelData } from '../panels';
import * as React from 'react';

import {
	__,
	sprintf,
	_x,
} from '@wordpress/i18n';

type OverviewProps = {
	data: iPanelData;
};

// Parse memory limit string like "1024M" or "1G" to bytes
const parseMemoryLimit = (limit: string): number => {
	const match = limit.match(/^(\d+)([KMG])?$/i);
	if (!match) return 0;

	const value = parseInt(match[1], 10);
	const unit = match[2]?.toUpperCase() || 'B';

	switch (unit) {
		case 'G': return value * 1024 * 1024 * 1024;
		case 'M': return value * 1024 * 1024;
		case 'K': return value * 1024;
		default: return value;
	}
};

export const Overview = ( { data }: OverviewProps ) => {
	// Get data from various collectors
	const dbQueriesData = data.db_queries?.data;
	const cacheData = data.cache?.data;
	const httpData = data.http?.data;
	const rawRequestData = data.raw_request?.data;
	const overviewData = data.overview.data;

	const timeTaken = overviewData.time_taken || 0;
	const memory = overviewData.memory || 0;
	const timeLimit = overviewData.time_limit || 0;
	const memoryLimit = overviewData.memory_limit || 0;
	const timeUsage = overviewData.time_usage || 0;
	const memoryUsage = overviewData.memory_usage || 0;
	const displayTimeUsageWarning = overviewData.display_time_usage_warning || false;
	const displayMemoryUsageWarning = overviewData.display_memory_usage_warning || false;

	return (
		<NonTabularPanel>
			<div className="qm-boxed">
				{ rawRequestData && rawRequestData.response?.status && (
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
						{ sprintf(
							/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
							_x( '%ss', 'Time in seconds', 'query-monitor' ),
							Utils.numberFormat( timeTaken, 4 )
						) }
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
								{ sprintf(
									/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
									_x( '%ss', 'Time in seconds', 'query-monitor' ),
									Utils.numberFormat( dbQueriesData.total_time, 4 )
								) }
							</p>
							<p>
								{ Object.keys( dbQueriesData.types ).length > 1 && Object.entries( dbQueriesData.types ).map( ( [ typeName, typeCount ] ) => {
									if ( typeName === 'SELECT' && Object.keys( dbQueriesData.types ).length === 1 ) {
										return null;
									}
									return (
										<React.Fragment key={ typeName }>
											<FilterLink
												targetPanel="db_queries"
												filterName="sql"
												filterValue={ typeName }
											>
												{ sprintf( '%1$s: %2$s', typeName, Utils.numberFormat( typeCount ) ) }
											</FilterLink>
											<br />
										</React.Fragment>
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
									{ sprintf(
										/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
										_x( '%ss', 'Time in seconds', 'query-monitor' ),
										Utils.numberFormat( httpData.ltime, 4 )
									) }
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
										Utils.numberFormat( cacheData.stats.cache_hits, 0 ),
										Utils.numberFormat( cacheData.stats.cache_misses, 0 )
									) }
								</p>
							) }
							{ cacheData.has_object_cache ? (
								<p>
									<span className="qm-info">
										<a
											href="/wp-admin/network/plugins.php?plugin_status=dropins"
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
													__( 'The %1$s object cache extension for PHP is installed but is not in use by WordPress. You should ', 'query-monitor' ),
													name
												) }
												<a
													href={ `https://wordpress.org/plugins/search/${ name.toLowerCase() }/` }
													target="_blank"
													rel="noopener noreferrer"
													className="qm-external-link"
												>
													{ sprintf(
														/* translators: %s: PHP extension name */
														__( 'install a %s plugin', 'query-monitor' ),
														name
													) }
												</a>
												.
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
