import * as React from "react";
import { Notice, QMComponent, PanelFooter, Tabular, iPanelProps, Time, TotalTime } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class HTTP extends React.Component<iPanelProps, {}> {

	render() {
		const { data } = this.props;

		if ( ! data.http ) {
			return (
				<Notice id={this.props.id}>
					<p>
						{__( 'No HTTP API calls.', 'query-monitor' )}
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col">
							{__( 'Method', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'URL', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Status', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Component', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Timeout', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Time', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{Object.keys(data.http).map(key=>{
						const row = data.http[key];

						return (
							<tr key={key}>
								<td>{row.args.method}</td>
								<td>{row.url}</td>
								<td>{row.response.response && row.response.response.code || __('Error','query-monitor')}</td>
								<td>Caller</td>
								<QMComponent component={row.component} />
								<td className="qm-num">{row.args.timeout}</td>
								<Time value={row.ltime}/>
							</tr>
						)
					})}
				</tbody>
				<PanelFooter cols={6} label={_x( 'Total:', 'HTTP API calls', 'query-monitor' )} count={Object.keys(data.http).length}>
					<TotalTime rows={Object.values(data.http)}/>
				</PanelFooter>
			</Tabular>
		)
	}

}

export default HTTP;
