import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	Tabular,
	Time,
	TotalTime,
} from 'qmi';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface iDBQueriesProps extends iPanelProps {
	data: {
		rows: {
			sql: string;
			result: string;
			ltime: number;
			filtered_trace: any[];
		}[];
	};
}

class DBQueries extends React.Component<iDBQueriesProps, Record<string, unknown>> {

	formatSQL( sql: string ) {
		const formatted = ' ' + sql.replace( /[\r\n\t]+/g, ' ' ).trim();
		const lineRegex = ' (ADD|AFTER|ALTER|AND|BEGIN|COMMIT|CREATE|DELETE|DESCRIBE|DO|DROP|ELSE|END|EXCEPT|EXPLAIN|FROM|GROUP|HAVING|INNER|INSERT|INTERSECT|LEFT|LIMIT|ON|OR|ORDER|OUTER|RENAME|REPLACE|RIGHT|ROLLBACK|SELECT|SET|SHOW|START|THEN|TRUNCATE|UNION|UPDATE|USE|USING|VALUES|WHEN|WHERE|XOR) ';
		const lines = formatted.split( new RegExp( lineRegex ) );
		const collection: JSX.Element[] = [];
		let index = 0;

		formatted.replace( new RegExp( lineRegex, 'g' ), ( match, keyword ) => {
			index += 2;

			collection.push(
				<>
					{ index > 2 && (
						<br />
					) }
					<b>{ keyword }</b>
					{ ` ${ lines[ index ] }` }
				</>
			);

			return '';
		} );

		return collection;
	}

	render() {
		const { data } = this.props;

		if ( ! data.rows || ! data.rows.length ) {
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
									{ this.formatSQL( row.sql ) }
								</code>
							</td>
							<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.filtered_trace } />
							{ /* <QMComponent component={row.component} /> */ }
							<td>
								Component
							</td>
							<td className="qm-row-result qm-num">
								{ row.result }
							</td>
							<Time value={ row.ltime }/>
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

export default DBQueries;
