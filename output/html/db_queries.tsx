import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	QMComponent,
	Tabular,
	TimeCell,
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
} from '@wordpress/i18n';

export default class DBQueries extends React.Component<iPanelProps<DataTypes['DB_Queries']>, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.rows?.length ) {
			return (
				<Notice id={ this.props.id }>
					<p>
						{ __( 'No queries! Nice work.', 'query-monitor' ) }
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th role="columnheader" scope="col">
							#
						</th>
						<th scope="col">
							{ __( 'Query', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Caller', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Component', 'query-monitor' ) }
						</th>
						<th className="qm-num" scope="col">
							{ __( 'Rows', 'query-monitor' ) }
						</th>
						<th className="qm-num" scope="col">
							{ __( 'Time', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ data.rows.map( ( row, i ) => (
						<tr key={ i }>
							<th className="qm-row-num qm-num" scope="row">
								{ 1 + i }
							</th>
							<td className="qm-row-sql qm-ltr qm-wrap">
								<code>
									{ Utils.formatSQL( row.sql ) }
								</code>
							</td>
							<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.trace } />
							<QMComponent component={ row.trace.component } />
							<td className="qm-row-result qm-num">
								{ Utils.isWPError( row.result ) ? (
									<Warning>
										{ Utils.getErrorMessage( row.result ) }
									</Warning>
								) : (
									row.result
								) }
							</td>
							<TimeCell value={ row.ltime }/>
						</tr>
					) ) }
				</tbody>
				<PanelFooter
					cols={ 5 }
					count={ data.rows.length }
					label={ _x( 'Total:', 'Database query count', 'query-monitor' ) }
				>
					<TotalTime rows={ data.rows }/>
				</PanelFooter>
			</Tabular>
		);
	}

}
