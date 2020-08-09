import * as React from "react";
import { Notice, PanelFooter, Tabular, iPanelProps, Time, TotalTime } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

interface iDBQueriesProps extends iPanelProps {
	data: {
		rows: {
			sql: string;
			result: string;
			ltime: number;
		}[];
	};
}

class DBQueries extends React.Component<iDBQueriesProps, {}> {

	render() {
		const { data } = this.props;

		if ( ! data.rows || ! data.rows.length ) {
			return (
				<Notice id={this.props.id}>
					<p>
					{__( 'No queries! Nice work.', 'query-monitor' )}
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col" role="columnheader">
							#
						</th>
						<th scope="col">
							{__( 'Query', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Caller', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Component', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Rows', 'query-monitor' )}
						</th>
						<th scope="col" className="qm-num">
							{__( 'Time', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.rows.map((row,i)=>
						<tr key={i}>
							<th scope="row" className="qm-row-num qm-num">{1+i}</th>
							<td className="qm-row-sql qm-ltr qm-wrap">{row.sql}</td>
							{/* <Caller trace={row.filtered_trace} /> */}
							{/* <QMComponent component={row.component} /> */}
							<td>Caller</td>
							<td>Component</td>
							<td className="qm-row-result qm-num">{row.result}</td>
							<Time value={row.ltime}/>
						</tr>
					)}
				</tbody>
				<PanelFooter cols={5} label={__( 'Total:', 'Database query count', 'query-monitor' )} count={data.rows.length}>
				<TotalTime rows={data.rows}/>
				</PanelFooter>
			</Tabular>
		)
	}

}

export default DBQueries;
