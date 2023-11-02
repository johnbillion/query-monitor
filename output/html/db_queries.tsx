import {
	Caller,
	iPanelProps,
	Notice,
	Component,
	TabularPanel,
	Time,
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
			cols={ {
				i: {
					className: 'qm-num',
					heading: '#',
					render: ( row, i ) => ( i + 1 ),
				},
				sql: {
					heading: __( 'Query', 'query-monitor' ),
					render: ( row ) => (
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
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
						<>
							{ Utils.isWPError( row.result ) ? (
								<Warning>
									{ Utils.getErrorMessage( row.result ) }
								</Warning>
							) : (
								row.result
							) }
						</>
					),
				},
				time: {
					className: 'qm-num',
					heading: __( 'Time', 'query-monitor' ),
					render: ( row ) => ( <Time value={ row.ltime }/> ),
				},
			} }
			data={ data.rows }
		/>
	);
};
