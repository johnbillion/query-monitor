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
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface iHTTPProps extends iPanelProps {
	data: DataTypes['HTTP'];
}

class HTTP extends React.Component<iHTTPProps, Record<string, unknown>> {

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
									{ 'response' in row.response ? row.response.response.code : __( 'Error', 'query-monitor' ) }
								</td>
								<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.filtered_trace } />
								<QMComponent component={ row.component } />
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

export default HTTP;
