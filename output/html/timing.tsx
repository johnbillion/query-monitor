import {
	iPanelProps,
	QMComponent,
	Tabular,
	Time,
	ApproximateSize,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export default class Timing extends React.Component<iPanelProps<DataTypes['Timing']>, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.timing ) {
			return null;
		}

		return (
			<Tabular id={ this.props.id }>
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
								<Time value={ timer.start_time } />
								<Time value={ timer.end_time } />
								<Time value={ timer.function_time } />
								<ApproximateSize value={ timer.function_memory } />
								<QMComponent component={ timer.trace.component } />
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
											<Time value={ value.time_used } />
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
	}

}
