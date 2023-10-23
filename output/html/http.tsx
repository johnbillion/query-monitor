import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	QMComponent,
	Tabular,
	Time,
	TotalTime,
	Utils,
	Warning,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	_x,
	sprintf,
} from '@wordpress/i18n';

export default class HTTP extends React.Component<iPanelProps<DataTypes['HTTP']>, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.http ) {
			return (
				<Notice id={ this.props.id }>
					<p>
						{ __( 'No HTTP API calls.', 'query-monitor' ) }
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Method', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'URL', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Status', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Caller', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Component', 'query-monitor' ) }
						</th>
						<th className="qm-num" scope="col">
							{ __( 'Timeout', 'query-monitor' ) }
						</th>
						<th className="qm-num" scope="col">
							{ __( 'Time', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ Object.keys( data.http ).map( key => {
						const row = data.http[key];

						return (
							<tr key={ key }>
								<td>
									{ row.args.method }
								</td>
								<td>
									{ Utils.formatURL( row.url ) }
								</td>
								<td>
									{ Utils.isWPError( row.response ) ? (
										<>
											<Warning/>
											{ sprintf(
												__( 'Error: %s', 'query-monitor' ),
												Utils.getErrorMessage( row.response )
											) }
										</>
									) : (
										`${row.response.response.code} ${row.response.response.message}`
									) }
								</td>
								<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.trace } />
								<QMComponent component={ row.trace.component } />
								<td className="qm-num">
									{ row.args.timeout }
								</td>
								<Time value={ row.ltime }/>
							</tr>
						);
					} ) }
				</tbody>
				<PanelFooter
					cols={ 6 }
					count={ Object.keys( data.http ).length }
					label={ _x( 'Total:', 'HTTP API calls', 'query-monitor' ) }
				>
					<TotalTime rows={ Object.values( data.http ) }/>
				</PanelFooter>
			</Tabular>
		);
	}

}
