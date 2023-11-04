import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
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

export default ( { data }: PanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.rows?.length ) {
		return <EmptyPanel>
			<p>
				{ __( 'No queries! Nice work.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	return <TabularPanel
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
					<>
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
						{ Utils.isWPError( row.result ) && (
							<>
								<br />
								<br />
								<Warning>
									{ Utils.getErrorMessage( row.result ) }
								</Warning>
							</>
						) }
					</>
				),
			},
			caller: {
				heading: __( 'Caller', 'query-monitor' ),
			},
			component: {
				heading: __( 'Component', 'query-monitor' ),
			},
			result: {
				className: 'qm-num',
				heading: __( 'Rows', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ ! Utils.isWPError( row.result ) && (
							row.result
						) }
					</>
				),
			},
			ltime: {
				className: 'qm-num',
				heading: __( 'Time', 'query-monitor' ),
			},
		} }
		data={ data.rows }
		hasError={ ( row ) => Utils.isWPError( row.result ) }
	/>
};
