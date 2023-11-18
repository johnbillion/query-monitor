import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	Utils,
	Warning,
	getComponentCol,
	getTimeCol,
	getCallerCol,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export const DBExpensive = ( { data }: PanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.expensive?.length ) {
		return null;
	}

	return <TabularPanel
		title={ __( 'Slow Database Queries', 'query-monitor' ) }
		cols={ {
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
			caller: getCallerCol( data.rows ),
			component: getComponentCol( data.rows, data.component_times ),
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
			time: getTimeCol( data.rows, () => true ),
		} }
		data={ data.rows.filter( ( row, i ) => data.expensive.includes( i ) ) }
		hasError={ ( row ) => Utils.isWPError( row.result ) }
	/>
};
