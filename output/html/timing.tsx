import {
	iPanelProps,
	Component,
	Tabular,
	Time,
	ApproximateSize,
	Notice,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['Timing']> ) => {
	if ( ! data.timing && ! data.warning ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
				<p>
					<a href="https://querymonitor.com/blog/2018/07/profiling-and-logging/">
						{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
					</a>
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Tracked Function', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Started', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Stopped', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Time', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Memory', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Component', 'query-monitor' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ data.timing.map( timer => (
					<React.Fragment key={ timer.function }>
						<tr>
							<td className="qm-ltr qm-nowrap">
								<code>
									{ timer.function }
								</code>
							</td>
							<td>
								<Time value={ timer.start_time } />
							</td>
							<td>
								<Time value={ timer.end_time } />
							</td>
							<td>
								<Time value={ timer.function_time } />
							</td>
							<ApproximateSize value={ timer.function_memory } />
							<Component component={ timer.trace.component } />
						</tr>
						{ timer.laps && (
							<>
								{ Object.entries( timer.laps ).map( ( [ key, value ] ) => (
									<tr key={ `${ timer.function }${ key }` }>
										<td className="qm-ltr qm-nowrap">
											<code>
												{ `- ${ key }` }
											</code>
										</td>
										<td></td>
										<td></td>
										<td>
											<Time value={ value.time_used } />
										</td>
										<ApproximateSize value={ value.memory_used } />
										<td></td>
									</tr>
								) ) }
							</>
						) }
					</React.Fragment>
				) ) }
			</tbody>
		</Tabular>
	);
};
