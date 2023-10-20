import {
	iPanelProps,
	iQM_i18n,
	QMComponent,
	Tabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

declare const QM_i18n: iQM_i18n;

class Timing extends React.Component<iPanelProps<DataTypes['Timing']>, Record<string, unknown>> {

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
								<td className="qm-num">
									{ QM_i18n.number_format( timer.start_time, 4 ) }
								</td>
								<td className="qm-num">
									{ QM_i18n.number_format( timer.end_time, 4 ) }
								</td>
								<td className="qm-num">
									{ QM_i18n.number_format( timer.function_time, 4 ) }
								</td>
								<td className="qm-num">
									{ sprintf(
										'~%s kB',
										QM_i18n.number_format( timer.function_memory / 1024 )
									) }
								</td>
								<QMComponent component={ timer.component } />
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
											<td className="qm-num">
												{ QM_i18n.number_format( value.time_used, 4 ) }
											</td>
											<td className="qm-num">
												{ sprintf(
													'~%s kB',
													QM_i18n.number_format( value.memory_used / 1024 )
												) }
											</td>
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

export default Timing;
