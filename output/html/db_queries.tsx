import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	Component,
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
} from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.rows?.length ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No queries! Nice work.', 'query-monitor' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
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
					<tr>
						<th className="qm-row-num qm-num" scope="row">
							{ 1 + i }
						</th>
						<td className="qm-row-sql qm-ltr qm-wrap">
							<code>
								{ Utils.formatSQL( row.sql ) }
							</code>
						</td>
						<Caller trace={ row.trace } />
						<Component component={ row.trace.component } />
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
			>
				<TotalTime rows={ data.rows }/>
			</PanelFooter>
		</Tabular>
	);
};
