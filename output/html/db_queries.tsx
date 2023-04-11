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
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface iDBQueriesProps extends iPanelProps {
	data: {
		component_times: {
			[component: string]: {
				component: string;
				ltime: number;
				types: {
					[type: string]: number;
				};
			};
		}
		dupes: {
			[sql: string]: number[];
		};
		times: {
			[caller: string]: {
				caller: string;
				ltime: number;
				types: {
					[type: string]: number;
				};
			};
		};
		total_qs: number;
		total_time: number;
		types: {
			[type: string]: number;
		}
		wpdb: {
			has_result: boolean;
			has_trace: boolean;
			rows: {
				caller: string;
				caller_name: string;
				is_main_query: boolean;
				sql: string;
				type: string;
				result: number;
				ltime: number;
				filtered_trace: any[];
				component: any;
			}[];
		};
	};
}

class DBQueries extends React.Component<iDBQueriesProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.wpdb?.rows?.length ) {
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
					{ data.wpdb.rows.map( ( row, i ) => (
						<tr key={ i }>
							<th className="qm-row-num qm-num" scope="row">
								{ 1 + i }
							</th>
							<td className="qm-row-sql qm-ltr qm-wrap">
								<code>
									{ Utils.formatSQL( row.sql ) }
								</code>
							</td>
							<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.filtered_trace } />
							<QMComponent component={ row.component } />
							<td className="qm-row-result qm-num">
								{ row.result }
							</td>
							<Time value={ row.ltime }/>
						</tr>
					) ) }
				</tbody>
				<PanelFooter
					cols={ 5 }
					count={ data.wpdb.rows.length }
					label={ _x( 'Total:', 'Database query count', 'query-monitor' ) }
				>
					<TotalTime rows={ data.wpdb.rows }/>
				</PanelFooter>
			</Tabular>
		);
	}

}

export default DBQueries;
