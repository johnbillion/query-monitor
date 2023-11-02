import {
	Caller,
	iPanelProps,
	Notice,
	Component,
	PanelTable,
	TabularPanel,
	TimeCell,
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
		<TabularPanel
			title={ __( 'Database Queries', 'query-monitor' ) }
		>
			<PanelTable
				cols={ {
					i: {
						className: 'qm-num',
						heading: '#',
						render: ( row, i ) => (
							<td className="qm-num">
								{ i + 1 }
							</td>
						),
					},
					sql: {
						heading: __( 'Query', 'query-monitor' ),
						render: ( row ) => (
							<td className="qm-row-sql qm-ltr qm-wrap">
								<code>
									{ Utils.formatSQL( row.sql ) }
								</code>
							</td>
						),
					},
					caller: {
						heading: __( 'Caller', 'query-monitor' ),
						render: ( row ) => <Caller trace={ row.trace } />,
					},
					component: {
						heading: __( 'Component', 'query-monitor' ),
						render: ( row ) => <Component component={ row.trace.component } />,
					},
					result: {
						className: 'qm-num',
						heading: __( 'Rows', 'query-monitor' ),
						render: ( row ) => (
							<td className="qm-num">
								{ Utils.isWPError( row.result ) ? (
									<Warning>
										{ Utils.getErrorMessage( row.result ) }
									</Warning>
								) : (
									row.result
								) }
							</td>
						),
					},
					time: {
						className: 'qm-num',
						heading: __( 'Time', 'query-monitor' ),
						render: ( row ) => ( <TimeCell value={ row.ltime }/> ),
					},
				} }
				data={ data.rows }
			/>
		</TabularPanel>
	);
};
