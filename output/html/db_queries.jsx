import React, { Component } from 'react';

import Caller from '../caller.jsx';
import Notice from '../notice.jsx';
import QMComponent from '../component.jsx';
import Tabular from '../tabular.jsx';
import PanelFooter from '../panel-footer.jsx';

const { __, _x, _n, sprintf } = wp.i18n;

class DBQueries extends Component {

	render() {
		const data = this.props.data;

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
						<th scope="col" class="qm-num">
							{__( 'Rows', 'query-monitor' )}
						</th>
						<th scope="col" class="qm-num">
							{__( 'Time', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.rows.map(function(row,i){
						return (
							<tr>
								<th scope="row" class="qm-row-num qm-num">{1+i}</th>
								<td class="qm-row-sql qm-ltr qm-wrap">{row.sql}</td>
								{/* <Caller trace={row.filtered_trace} /> */}
								{/* <QMComponent component={row.component} /> */}
								<td>Caller</td>
								<td>Component</td>
								<td class='qm-row-result qm-num'>{row.result}</td>
								<td class='qm-row-result qm-num'>{row.ltime}</td>
							</tr>
						)
					})}
				</tbody>
				<PanelFooter cols="6" label={__( 'Total:', 'Database query count', 'query-monitor' )} count={data.rows.length} />
			</Tabular>
		)
	}

}

export default DBQueries;
